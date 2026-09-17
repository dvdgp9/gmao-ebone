<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Database;
use App\Models\Instalacio;
use App\Models\Torn;
use App\Models\Usuari;
use App\Models\UsuariToken;
use App\Services\UsuariLlista;
use App\Services\UsuariMassiu;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

/**
 * Accions en bloc del panell d'usuaris (només superadmin).
 */
class UsuariMassiuController extends Controller
{
    public function executar(): void
    {
        $this->requireSuperadminPost();

        $accio = (string)$this->post('accio', '');
        $ids = UsuariMassiu::parseIds((string)$this->post('ids', ''));
        if ($ids === [] || !in_array($accio, UsuariMassiu::ACCIONS, true)) {
            $this->setFlash('error', 'Tria almenys un usuari i una acció.');
            $this->redirect('usuaris');
        }

        $instalacio = null;
        if (in_array($accio, ['assignar', 'treure'], true)) {
            $instalacio = Instalacio::find((int)$this->post('instalacio_id'));
            if (!$instalacio || empty($instalacio['activa'])) {
                $this->setFlash('error', 'Tria una instal·lació activa.');
                $this->redirect('usuaris');
            }
        }

        $rolId = null;
        if ($accio === 'assignar') {
            $stmt = Database::getInstance()->prepare("SELECT id FROM rols WHERE id = ? AND nom <> 'superadmin' LIMIT 1");
            $stmt->execute([(int)$this->post('rol_id')]);
            $rolId = $stmt->fetchColumn() ?: null;
            if (!$rolId) {
                $this->setFlash('error', 'Tria un rol per assignar.');
                $this->redirect('usuaris');
            }
        }

        if ($accio === 'enllacos' && !UsuariToken::supported()) {
            $this->setFlash('error', 'Els enllaços d\'accés encara no estan activats al servidor (falta la migració).');
            $this->redirect('usuaris');
        }

        $usuaris = Usuari::perIdsAmbInstalacions($ids);
        $pla = UsuariMassiu::planificar($accio, $usuaris, $this->currentUserId(), $instalacio ? (int)$instalacio['id'] : null);

        // Fora de la transacció: fan SHOW TABLES i queden en memòria.
        Torn::supportsUsuariTorn();
        UsuariToken::supported();

        $db = Database::getInstance();
        $enllacos = [];
        $expiresAt = null;

        try {
            $db->beginTransaction();

            if ($pla['aplicar'] !== []) {
                switch ($accio) {
                    case 'activar':
                    case 'desactivar':
                        $placeholders = implode(',', array_fill(0, count($pla['aplicar']), '?'));
                        $db->prepare("UPDATE usuaris SET actiu = ? WHERE id IN ({$placeholders})")
                            ->execute(array_merge([$accio === 'activar' ? 1 : 0], $pla['aplicar']));
                        break;

                    case 'assignar':
                        $tornIds = UsuariMassiu::parseIds((string)$this->post('torn_ids', ''));
                        foreach ($pla['aplicar'] as $usuariId) {
                            Usuari::assignInstalacio($usuariId, (int)$instalacio['id'], (int)$rolId);
                            // syncTornsForUsuari descarta els torns que no són d'aquesta instal·lació.
                            Torn::syncTornsForUsuari($usuariId, (int)$instalacio['id'], $tornIds);
                        }
                        break;

                    case 'treure':
                        foreach ($pla['aplicar'] as $usuariId) {
                            Torn::syncTornsForUsuari($usuariId, (int)$instalacio['id'], []);
                            Usuari::removeInstalacio($usuariId, (int)$instalacio['id']);
                        }
                        break;

                    case 'enllacos':
                        $perId = array_column(Usuari::allWithRoles(), null, 'id');
                        foreach ($pla['aplicar'] as $usuariId) {
                            $token = UsuariToken::crear($usuariId, UsuariToken::tipusPerNouEnllac($usuariId), $this->currentUserId());
                            $expiresAt = $token['expires_at'];
                            $usuari = $perId[$usuariId] ?? [];
                            $assignacions = $usuari['assignacions'] ?? [];
                            $enllacos[] = [
                                'nom' => trim(($usuari['nom'] ?? '') . ' ' . ($usuari['cognoms'] ?? '')),
                                'email' => $usuari['email'] ?? '',
                                'instalacio' => implode(', ', array_column($assignacions, 'instalacio_nom')),
                                'rol' => implode(', ', array_unique(array_map([Usuari::class, 'etiquetaRol'], array_column($assignacions, 'rol_nom')))),
                                'torns' => '',
                                'accio' => null,
                                'enllac' => url('acces/' . $token['token']),
                            ];
                        }
                        break;
                }
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[usuaris/massiu] ' . $accio . ': ' . $e->getMessage());
            $detall = ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? ' Detall: ' . $e->getMessage() : '';
            $this->setFlash('error', 'No s\'ha pogut completar l\'acció i no s\'ha canviat res.' . $detall);
            $this->redirect('usuaris');
        }

        $missatge = UsuariMassiu::missatge($accio, $pla, $instalacio['nom'] ?? '');

        if ($accio === 'enllacos' && $enllacos !== []) {
            // Mateixa pàgina de resultat que la importació: copiar, descarregar Excel.
            $_SESSION[UsuariImportController::SESSIO_RESULTAT] = [
                'origen' => 'enllacos',
                'creat_at' => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt,
                'files' => $enllacos,
            ];
            $this->setFlash('success', $missatge);
            $this->redirect('usuaris/importar/resultat');
        }

        $this->setFlash($pla['aplicar'] !== [] ? 'success' : 'info', $missatge);
        $this->redirect('usuaris');
    }

    public function exportar(): void
    {
        $this->requireSuperadminPost();

        $ids = array_flip(UsuariMassiu::parseIds((string)$this->post('ids', '')));
        if ($ids === []) {
            $this->setFlash('error', 'No hi ha cap usuari per exportar.');
            $this->redirect('usuaris');
        }

        $usuaris = array_values(array_filter(Usuari::allWithRoles(), static fn($u) => isset($ids[(int)$u['id']])));
        $files = UsuariLlista::files(
            $usuaris,
            Torn::nomsPerUsuari(),
            UsuariToken::estatsActivacio(array_column($usuaris, 'id')),
            [],
            $this->currentUserId()
        );

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Usuaris');
        $sheet->fromArray(['Nom', 'Cognoms', 'Email', 'Estat', 'Instal·lacions i rols', 'Torns', 'Alta'], null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $fila = 2;
        foreach ($files as $u) {
            $estat = !$u['actiu'] ? 'Inactiu' : match ($u['activacio']) {
                'pendent' => 'Pendent d\'activar',
                'caducat' => 'Enllaç caducat',
                default => 'Actiu',
            };
            $rols = $u['superadmin']
                ? 'Superadmin'
                : implode('; ', array_map(static fn($a) => $a['instalacio'] . ' · ' . $a['rol_etiqueta'], $u['assignacions']));
            $torns = implode('; ', array_filter(array_map(
                static fn($a) => $a['torns'] ? $a['instalacio'] . ': ' . implode(', ', $a['torns']) : '',
                $u['assignacions']
            )));

            $sheet->fromArray([
                $u['nom'],
                $u['cognoms'],
                $u['email'],
                $estat,
                $rols,
                $torns,
                $u['creat'] ? date('d/m/Y', strtotime($u['creat'])) : '',
            ], null, 'A' . $fila);
            $fila++;
        }
        foreach (range('A', 'G') as $columna) {
            $sheet->getColumnDimension($columna)->setAutoSize(true);
        }
        $sheet->setAutoFilter('A1:G' . max(1, $fila - 1));

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="usuaris-' . date('Ymd-Hi') . '.xlsx"');
        header('Cache-Control: no-store');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    private function requireSuperadminPost(): void
    {
        $this->requireAuth();
        if (empty($_SESSION['is_superadmin'])) {
            http_response_code(403);
            $this->view('errors.403');
            exit;
        }
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid. Recarrega la pàgina i torna-ho a provar.');
            $this->redirect('usuaris');
        }
    }
}
