<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Database;
use App\Models\Instalacio;
use App\Models\Torn;
use App\Models\Usuari;
use App\Models\UsuariToken;
use App\Services\UsuariImportParser;
use App\Services\UsuariImportResolver;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

/**
 * Panell d'importació d'usuaris: enganxar text o columnes, pujar un fitxer, ajustar amb clics
 * i crear els comptes amb un enllaç d'accés d'un sol ús per a cadascú.
 */
class UsuariImportController extends Controller
{
    public const SESSIO_RESULTAT = 'usuaris_import_resultat';
    private const MAX_FITXER_BYTES = 5 * 1024 * 1024;

    public function index(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        $ctx = $this->context();

        $this->view('usuaris.importar', [
            'title' => 'Importar usuaris',
            'config' => [
                'urls' => [
                    'analitzar' => url('usuaris/importar/analitzar'),
                    'validar' => url('usuaris/importar/validar'),
                    'confirmar' => url('usuaris/importar/confirmar'),
                ],
                'csrf' => csrf_token(),
                'instalacions' => $ctx['instalacions'],
                'rols' => array_map(static fn($r) => $r + ['etiqueta' => Usuari::etiquetaRol($r['nom'])], $ctx['rols']),
                'torns' => (object)$ctx['torns'],
                'defaultInstalacioId' => $ctx['default_instalacio_id'],
                'defaultRolId' => $ctx['default_rol_id'],
                'maxFiles' => UsuariImportParser::MAX_FILES,
            ],
            'potTriarInstalacio' => !empty($_SESSION['is_superadmin']),
            'tokensDisponibles' => UsuariToken::supported(),
            'flash' => $this->getFlash(),
        ]);
    }

    /** Text enganxat o fitxer → files resoltes + validació. */
    public function analitzar(): void
    {
        $this->requireJsonAccess();

        $fitxer = $_FILES['fitxer'] ?? null;
        if ($fitxer && ($fitxer['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $parsed = $this->parseFitxer($fitxer);
        } else {
            $parsed = UsuariImportParser::parseText((string)$this->post('text', ''));
        }

        $ctx = $this->context();
        $files = UsuariImportResolver::resolve($parsed['files'], $ctx);

        $this->json([
            'ok' => true,
            'files' => $files,
            'avisos' => $parsed['avisos'],
            'validacio' => $this->validacio($files, $ctx),
        ]);
    }

    public function validar(): void
    {
        $this->requireJsonAccess();
        $files = $this->filesDelFormulari();
        $ctx = $this->context();

        $this->json(['ok' => true, 'validacio' => $this->validacio($files, $ctx)]);
    }

    public function confirmar(): void
    {
        $this->requireJsonAccess();

        if (!UsuariToken::supported()) {
            $this->json(['ok' => false, 'error' => 'Falta executar la migració 2026-09-17_usuari_tokens.sql al servidor.'], 409);
        }

        $files = $this->filesDelFormulari();
        if ($files === []) {
            $this->json(['ok' => false, 'error' => 'No hi ha cap fila per importar.'], 422);
        }

        $ctx = $this->context();
        $validacio = $this->validacio($files, $ctx);
        if ($validacio['resum']['errors'] > 0) {
            $this->json(['ok' => false, 'error' => 'Hi ha files amb errors. Corregeix-les abans de confirmar.', 'validacio' => $validacio], 422);
        }

        $noms = [
            'instalacions' => array_column($ctx['instalacions'], 'nom', 'id'),
            'rols' => array_column($ctx['rols'], 'nom', 'id'),
            'torns' => [],
        ];
        foreach ($ctx['torns'] as $torns) {
            $noms['torns'] += array_column($torns, 'nom', 'id');
        }

        Torn::supportsUsuariTorn(); // Fora de la transacció: fa un SHOW TABLES i queda en memòria.
        $db = Database::getInstance();
        $creats = [];
        $resultat = [];
        $expiresAt = null;

        try {
            $db->beginTransaction();

            foreach ($files as $index => $fila) {
                $email = mb_strtolower(trim($fila['email']), 'UTF-8');
                $instalacioId = (int)$fila['instalacio_id'];
                $rolId = (int)$fila['rol_id'];
                $existent = $ctx['existing'][$email] ?? null;
                $enllac = null;

                if ($existent !== null) {
                    $usuariId = (int)$existent['id'];
                } elseif (isset($creats[$email])) {
                    $usuariId = $creats[$email];
                } else {
                    $usuariId = Usuari::create([
                        'nom' => trim($fila['nom']),
                        'cognoms' => trim($fila['cognoms']) ?: null,
                        'email' => $email,
                        'password_hash' => UsuariToken::hashInutilitzable(),
                        'actiu' => 1,
                    ]);
                    $creats[$email] = $usuariId;
                    $token = UsuariToken::crear($usuariId, UsuariToken::TIPUS_ACTIVACIO, $this->currentUserId());
                    $enllac = url('acces/' . $token['token']);
                    $expiresAt = $token['expires_at'];
                }

                Usuari::assignInstalacio($usuariId, $instalacioId, $rolId);
                Torn::syncTornsForUsuari($usuariId, $instalacioId, $fila['torn_ids']);

                $resultat[] = [
                    'nom' => $existent !== null ? trim(($existent['nom'] ?? '') . ' ' . ($existent['cognoms'] ?? '')) : trim($fila['nom'] . ' ' . $fila['cognoms']),
                    'email' => $email,
                    'instalacio' => $noms['instalacions'][$instalacioId] ?? '',
                    'rol' => Usuari::etiquetaRol($noms['rols'][$rolId] ?? ''),
                    'torns' => implode(', ', array_map(static fn($id) => $noms['torns'][$id] ?? '', $fila['torn_ids'])),
                    'accio' => $validacio['files'][$index]['accio'],
                    'enllac' => $enllac,
                ];
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[usuaris/importar] ' . $e->getMessage());
            $detall = ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? ' Detall: ' . $e->getMessage() : '';
            $this->json(['ok' => false, 'error' => 'No s\'ha pogut completar la importació i no s\'ha creat cap usuari.' . $detall], 500);
        }

        $_SESSION[self::SESSIO_RESULTAT] = [
            'creat_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
            'files' => $resultat,
        ];

        $this->json(['ok' => true, 'redirect' => url('usuaris/importar/resultat')]);
    }

    public function resultat(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        $resultat = $_SESSION[self::SESSIO_RESULTAT] ?? null;
        if (!$resultat) {
            $this->setFlash('error', 'No hi ha cap importació recent per mostrar.');
            $this->redirect('usuaris');
        }

        $this->view('usuaris.importar_resultat', [
            'title' => ($resultat['origen'] ?? '') === 'enllacos' ? 'Enllaços d\'accés generats' : 'Usuaris importats',
            'resultat' => $resultat,
        ]);
    }

    public function excel(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        $resultat = $_SESSION[self::SESSIO_RESULTAT] ?? null;
        if (!$resultat) {
            $this->setFlash('error', 'No hi ha cap importació recent per descarregar.');
            $this->redirect('usuaris');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Accessos');
        $sheet->fromArray(['Nom', 'Email', 'Instal·lació', 'Rol', 'Torns', 'Enllaç d\'accés', 'Caduca'], null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $caduca = $resultat['expires_at'] ? date('d/m/Y H:i', strtotime($resultat['expires_at'])) : '';
        $fila = 2;
        foreach ($resultat['files'] as $r) {
            $sheet->fromArray([
                $r['nom'],
                $r['email'],
                $r['instalacio'],
                $r['rol'],
                $r['torns'],
                $r['enllac'] ?? 'Ja tenia compte: fa servir la seva contrasenya',
                $r['enllac'] ? $caduca : '',
            ], null, 'A' . $fila);
            $fila++;
        }
        foreach (range('A', 'G') as $columna) {
            $sheet->getColumnDimension($columna)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="usuaris-accessos-' . date('Ymd-Hi', strtotime($resultat['creat_at'])) . '.xlsx"');
        header('Cache-Control: no-store');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    /**
     * Context per al resolver: què pot fer servir qui importa.
     */
    private function context(): array
    {
        $isSuperadmin = !empty($_SESSION['is_superadmin']);
        $currentInstalacioId = $this->currentInstalacioId();

        if ($isSuperadmin) {
            $instalacions = Instalacio::actives();
        } else {
            $instalacio = $currentInstalacioId ? Instalacio::find($currentInstalacioId) : null;
            $instalacions = $instalacio ? [$instalacio] : [];
        }
        $instalacions = array_map(static fn($i) => ['id' => (int)$i['id'], 'nom' => $i['nom']], $instalacions);

        $rols = Database::getInstance()
            ->query("SELECT id, nom FROM rols WHERE nom <> 'superadmin' ORDER BY id")
            ->fetchAll();
        $rols = array_map(static fn($r) => ['id' => (int)$r['id'], 'nom' => $r['nom']], $rols);

        $torns = [];
        if (Torn::supportsUsuariTorn()) {
            foreach ($instalacions as $instalacio) {
                $torns[$instalacio['id']] = array_map(
                    static fn($t) => ['id' => (int)$t['id'], 'nom' => $t['nom']],
                    Torn::all(['instalacio_id' => $instalacio['id'], 'actiu' => 1], 'nom ASC')
                );
            }
        }

        $idsInstalacions = array_column($instalacions, 'id');
        $defaultInstalacioId = in_array((int)$currentInstalacioId, $idsInstalacions, true) ? (int)$currentInstalacioId : null;
        if ($defaultInstalacioId === null && count($instalacions) === 1) {
            $defaultInstalacioId = $instalacions[0]['id'];
        }

        $tecnic = array_values(array_filter($rols, static fn($r) => $r['nom'] === 'tecnic'))[0] ?? null;

        return [
            'instalacions' => $instalacions,
            'rols' => $rols,
            'torns' => $torns,
            'default_instalacio_id' => $defaultInstalacioId,
            'default_rol_id' => $tecnic['id'] ?? null,
            'is_superadmin' => $isSuperadmin,
            'current_instalacio_id' => $currentInstalacioId,
            'existing' => [],
        ];
    }

    private function validacio(array $files, array &$ctx): array
    {
        $ctx['existing'] = $this->usuarisExistents(array_column($files, 'email'));

        return UsuariImportResolver::validate($files, $ctx);
    }

    /**
     * @return array<string, array{id: int, nom: string, cognoms: ?string, actiu: int, is_superadmin: int, instalacio_ids: list<int>}>
     */
    private function usuarisExistents(array $emails): array
    {
        $emails = array_values(array_unique(array_filter(array_map(
            static fn($e) => mb_strtolower(trim((string)$e), 'UTF-8'),
            $emails
        ))));
        if ($emails === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($emails), '?'));
        $rows = Usuari::query("
            SELECT u.id, u.nom, u.cognoms, u.email, u.actiu, u.is_superadmin, GROUP_CONCAT(ui.instalacio_id) AS instalacio_ids
            FROM usuaris u
            LEFT JOIN usuari_instalacio ui ON ui.usuari_id = u.id
            WHERE u.email IN ({$placeholders})
            GROUP BY u.id, u.nom, u.cognoms, u.email, u.actiu, u.is_superadmin
        ", $emails);

        $existents = [];
        foreach ($rows as $row) {
            $row['instalacio_ids'] = $row['instalacio_ids'] ? array_map('intval', explode(',', $row['instalacio_ids'])) : [];
            $existents[mb_strtolower($row['email'], 'UTF-8')] = $row;
        }

        return $existents;
    }

    /**
     * Les files arriben com a JSON dins d'un camp del formulari (així el CSRF funciona igual).
     */
    private function filesDelFormulari(): array
    {
        $files = json_decode((string)$this->post('files', '[]'), true);
        if (!is_array($files)) {
            $this->json(['ok' => false, 'error' => 'Dades no vàlides.'], 400);
        }

        return array_map(static fn($f) => [
            'nom' => trim((string)($f['nom'] ?? '')),
            'cognoms' => trim((string)($f['cognoms'] ?? '')),
            'email' => trim((string)($f['email'] ?? '')),
            'instalacio_id' => isset($f['instalacio_id']) && $f['instalacio_id'] !== '' ? (int)$f['instalacio_id'] : null,
            'rol_id' => isset($f['rol_id']) && $f['rol_id'] !== '' ? (int)$f['rol_id'] : null,
            'torn_ids' => array_values(array_unique(array_map('intval', (array)($f['torn_ids'] ?? [])))),
        ], array_slice(array_values(array_filter($files, 'is_array')), 0, UsuariImportParser::MAX_FILES));
    }

    /**
     * @return array{files: list<array>, avisos: list<string>}
     */
    private function parseFitxer(array $fitxer): array
    {
        if (($fitxer['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($fitxer['tmp_name'])) {
            $this->json(['ok' => false, 'error' => 'No s\'ha pogut pujar el fitxer.'], 400);
        }
        if ($fitxer['size'] > self::MAX_FITXER_BYTES) {
            $this->json(['ok' => false, 'error' => 'El fitxer és massa gran (màxim 5 MB).'], 400);
        }

        $extensio = strtolower(pathinfo($fitxer['name'] ?? '', PATHINFO_EXTENSION));
        if (in_array($extensio, ['csv', 'txt', 'tsv'], true)) {
            return UsuariImportParser::parseText((string)file_get_contents($fitxer['tmp_name']));
        }
        if (!in_array($extensio, ['xlsx', 'xls', 'ods'], true)) {
            $this->json(['ok' => false, 'error' => 'Format no admès. Puja un Excel (.xlsx, .xls), .ods o .csv.'], 400);
        }

        try {
            $reader = IOFactory::createReaderForFile($fitxer['tmp_name']);
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            $spreadsheet = $reader->load($fitxer['tmp_name']);
        } catch (Throwable $e) {
            error_log('[usuaris/importar] ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'No s\'ha pogut llegir el fitxer. Comprova que és un Excel vàlid.'], 400);
        }

        // Si hi ha un full d'usuaris es fa servir aquest; si no, el primer.
        $sheet = $spreadsheet->getSheet(0);
        foreach ($spreadsheet->getWorksheetIterator() as $full) {
            if (in_array(UsuariImportParser::normalize($full->getTitle()), ['usuaris', 'usuarios', 'users', 'personal', 'treballadors', 'trabajadores'], true)) {
                $sheet = $full;
                break;
            }
        }

        $taula = $sheet->toArray('', true, false, false);
        $spreadsheet->disconnectWorksheets();

        return UsuariImportParser::parseTable($taula);
    }

    /**
     * Com requireRole, però responent JSON: el panell treballa amb fetch.
     */
    private function requireJsonAccess(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->json(['ok' => false, 'error' => 'La sessió ha caducat. Torna a entrar i recarrega la pàgina.'], 401);
        }
        if (empty($_SESSION['is_superadmin']) && ($_SESSION['current_role'] ?? '') !== 'admin_instalacio') {
            $this->json(['ok' => false, 'error' => 'No tens permís per importar usuaris.'], 403);
        }
        if (empty($_SESSION['is_superadmin']) && !$this->currentInstalacioId()) {
            $this->json(['ok' => false, 'error' => 'Tria primer una instal·lació.'], 403);
        }
        if (!verify_csrf()) {
            $this->json(['ok' => false, 'error' => 'Token de seguretat invàlid. Recarrega la pàgina.'], 419);
        }
    }
}
