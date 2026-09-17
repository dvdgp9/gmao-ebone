<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Usuari;
use App\Models\Instalacio;
use App\Models\Torn;
use App\Models\UsuariToken;
use App\Models\Database;

class UsuariController extends Controller
{
    private const MISSATGE_COMPTE_COMPARTIT = 'Aquest usuari també treballa en altres instal·lacions: només un superadmin en pot canviar les dades del compte, l\'estat o l\'accés.';

    public function index(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);

        $comptesBloquejats = [];
        if ($_SESSION['is_superadmin'] ?? false) {
            $usuaris = Usuari::allWithRoles();
        } else {
            $usuaris = Usuari::allWithRoles($this->currentInstalacioId());
            $instalacionsPerUsuari = Usuari::instalacioIdsPerUsuaris(array_column($usuaris, 'id'));
            $instalacionsAdmin = Usuari::instalacioIdsAmbRol($this->currentUserId(), 'admin_instalacio');
            foreach ($usuaris as $u) {
                if (!Usuari::compteGestionable(false, $this->currentUserId(), $u, $instalacionsPerUsuari[(int)$u['id']] ?? [], $instalacionsAdmin)) {
                    $comptesBloquejats[(int)$u['id']] = true;
                }
            }
        }

        $this->view('usuaris.index', [
            'title' => 'Usuaris',
            'usuaris' => $usuaris,
            'comptesBloquejats' => $comptesBloquejats,
            'estatsActivacio' => UsuariToken::estatsActivacio(array_column($usuaris, 'id')),
            'potGenerarEnllac' => UsuariToken::supported(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);

        $instalacions = $this->getInstalacionsDisponibles();

        $this->view('usuaris.form', [
            'title' => 'Nou Usuari',
            'usuari' => null,
            'assignacions' => [],
            'instalacions' => $instalacions,
            'rols' => $this->getRols(),
            'tornsPerInstalacio' => $this->getTornsPerInstalacio($instalacions),
            'tornsAssignats' => [],
            'tornsAssignatsPerInst' => [],
            'isSuperadmin' => (bool)($_SESSION['is_superadmin'] ?? false),
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('usuaris');
        }

        $email = trim($this->post('email', ''));
        $password = $this->post('password', '');
        // Sense contrasenya es genera un enllaç perquè la persona la triï.
        $ambEnllac = $password === '';

        if (empty($email)) {
            $this->setFlash('error', 'L\'email és obligatori.');
            $this->redirect('usuaris/create');
        }
        if ($ambEnllac && !UsuariToken::supported()) {
            $this->setFlash('error', 'Cal indicar una contrasenya: els enllaços d\'accés encara no estan activats al servidor.');
            $this->redirect('usuaris/create');
        }

        if (Usuari::findByEmail($email)) {
            $this->setFlash('error', 'Ja existeix un usuari amb aquest email.');
            $this->redirect('usuaris/create');
        }

        $instalacioId = (int)$this->post('instalacio_id');
        $rolId = (int)$this->post('rol_id');
        if (empty($_SESSION['is_superadmin']) && (!$instalacioId || !$rolId)) {
            $this->setFlash('error', 'Cal assignar el nou usuari a la instal·lació activa amb un rol vàlid.');
            $this->redirect('usuaris/create');
        }
        if ($instalacioId && $rolId && (!$this->canAssignInstalacio($instalacioId) || !$this->canAssignRole($rolId))) {
            $this->setFlash('error', 'Assignació no permesa per al teu rol.');
            $this->redirect('usuaris/create');
        }

        $id = Usuari::create([
            'nom' => trim($this->post('nom', '')),
            'cognoms' => trim($this->post('cognoms', '')) ?: null,
            'email' => $email,
            'password_hash' => $ambEnllac ? UsuariToken::hashInutilitzable() : password_hash($password, PASSWORD_BCRYPT),
            'actiu' => $this->post('actiu', '1') === '1' ? 1 : 0,
        ]);

        if ($_SESSION['is_superadmin'] ?? false) {
            $this->syncAssignacionsSuperadmin($id);
        } elseif ($instalacioId && $rolId) {
            Usuari::assignInstalacio($id, $instalacioId, $rolId);
            Torn::syncTornsForUsuari($id, $instalacioId, $this->postedTorns());
        }

        if ($ambEnllac) {
            $this->flashEnllac($id, UsuariToken::TIPUS_ACTIVACIO, 'Usuari creat. Envia-li aquest enllaç perquè triï la seva contrasenya.');
        } else {
            $this->setFlash('success', 'Usuari creat correctament.');
        }
        $this->redirect('usuaris');
    }

    /**
     * Genera un enllaç d'accés nou (activació o canvi de contrasenya) i invalida els anteriors.
     */
    public function enllac(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('usuaris');
        }
        if (!UsuariToken::supported()) {
            $this->setFlash('error', 'Els enllaços d\'accés encara no estan activats al servidor (falta la migració).');
            $this->redirect('usuaris');
        }

        $usuari = Usuari::find((int)$id);
        if (!$usuari) {
            $this->setFlash('error', 'Usuari no trobat.');
            $this->redirect('usuaris');
        }
        if (!$this->canManageUser((int)$id)) {
            $this->setFlash('error', 'No tens permís per gestionar aquest usuari.');
            $this->redirect('usuaris');
        }
        // Un enllaç dona accés al compte sencer.
        if (!$this->potGestionarCompte($usuari)) {
            $this->setFlash('error', self::MISSATGE_COMPTE_COMPARTIT);
            $this->redirect('usuaris');
        }
        if (!$usuari['actiu']) {
            $this->setFlash('error', 'L\'usuari està desactivat. Activa\'l abans de generar-li un enllaç.');
            $this->redirect('usuaris');
        }

        $tipus = UsuariToken::tipusPerNouEnllac((int)$id);
        $this->flashEnllac(
            (int)$id,
            $tipus,
            $tipus === UsuariToken::TIPUS_ACTIVACIO
                ? 'Enllaç d\'activació nou. L\'anterior ja no funciona.'
                : 'Enllaç per canviar la contrasenya. La contrasenya actual continua funcionant fins que el faci servir.'
        );
        $this->redirect('usuaris');
    }

    private function flashEnllac(int $usuariId, string $tipus, string $missatge): void
    {
        $usuari = Usuari::find($usuariId);
        $token = UsuariToken::crear($usuariId, $tipus, $this->currentUserId());
        $nom = trim(($usuari['nom'] ?? '') . ' ' . ($usuari['cognoms'] ?? ''));

        $this->setFlash('success', $missatge, [
            'enllac' => [
                'url' => url('acces/' . $token['token']),
                'nom' => $nom,
                'email' => $usuari['email'] ?? '',
                'caduca' => date('d/m/Y H:i', strtotime($token['expires_at'])),
            ],
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        $usuari = Usuari::find((int)$id);
        if (!$usuari) {
            $this->setFlash('error', 'Usuari no trobat.');
            $this->redirect('usuaris');
        }
        if (!$this->canManageUser((int)$id)) {
            $this->setFlash('error', 'No tens permís per gestionar aquest usuari.');
            $this->redirect('usuaris');
        }

        $assignacions = Usuari::getAssignacions((int)$id);
        if (empty($_SESSION['is_superadmin'])) {
            // Un admin d'instal·lació només veu l'assignació de la seva instal·lació.
            $instalacioActiva = (int)$this->currentInstalacioId();
            $assignacions = array_values(array_filter($assignacions, static fn($a) => (int)$a['instalacio_id'] === $instalacioActiva));
        }
        $instalacions = $this->getInstalacionsDisponibles();

        $tornsAssignats = [];
        $tornsAssignatsPerInst = [];
        foreach ($instalacions as $inst) {
            $ids = Torn::tornIdsByUsuariInstalacio((int)$id, (int)$inst['id']);
            $tornsAssignatsPerInst[(int)$inst['id']] = $ids;
            $tornsAssignats = array_merge($tornsAssignats, $ids);
        }

        $this->view('usuaris.form', [
            'title' => 'Editar Usuari',
            'usuari' => $usuari,
            'compteEditable' => $this->potGestionarCompte($usuari),
            'assignacions' => $assignacions,
            'instalacions' => $instalacions,
            'rols' => $this->getRols(),
            'tornsPerInstalacio' => $this->getTornsPerInstalacio($instalacions),
            'tornsAssignats' => $tornsAssignats,
            'tornsAssignatsPerInst' => $tornsAssignatsPerInst,
            'isSuperadmin' => (bool)($_SESSION['is_superadmin'] ?? false),
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('usuaris');
        }

        $usuari = Usuari::find((int)$id);
        if (!$usuari) {
            $this->setFlash('error', 'Usuari no trobat.');
            $this->redirect('usuaris');
        }
        if (!$this->canManageUser((int)$id)) {
            $this->setFlash('error', 'No tens permís per gestionar aquest usuari.');
            $this->redirect('usuaris');
        }

        // Dades del compte: només si és gestionable. Si no, d'aquest usuari només es canvien rol i torns.
        if ($this->potGestionarCompte($usuari)) {
            $data = [
                'nom' => trim($this->post('nom', '')),
                'cognoms' => trim($this->post('cognoms', '')) ?: null,
                'email' => trim($this->post('email', '')),
                // Si el camp no arriba (petició sense formulari), es conserva l'estat actual.
                'actiu' => $this->post('actiu') === null ? (int)$usuari['actiu'] : ($this->post('actiu') === '1' ? 1 : 0),
            ];

            if ((int)$id === $this->currentUserId() && $data['actiu'] === 0 && (int)$usuari['actiu'] === 1) {
                $this->setFlash('error', 'No pots desactivar el teu propi compte.');
                $this->redirect('usuaris/edit/' . (int)$id);
            }

            if ($data['email'] === '') {
                $this->setFlash('error', 'L\'email és obligatori.');
                $this->redirect('usuaris/edit/' . (int)$id);
            }
            $ambAquestEmail = Usuari::findByEmail($data['email']);
            if ($ambAquestEmail && (int)$ambAquestEmail['id'] !== (int)$id) {
                $this->setFlash('error', 'Ja existeix un altre usuari amb aquest email.');
                $this->redirect('usuaris/edit/' . (int)$id);
            }

            $password = $this->post('password', '');
            if (!empty($password)) {
                $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
            }

            Usuari::update((int)$id, $data);
        }

        if ($_SESSION['is_superadmin'] ?? false) {
            $this->syncAssignacionsSuperadmin((int)$id);
        } else {
            $instalacioId = (int)$this->post('instalacio_id');
            $rolId = (int)$this->post('rol_id');
            if ($instalacioId && $rolId) {
                if (!$this->canAssignInstalacio($instalacioId) || !$this->canAssignRole($rolId)) {
                    $this->setFlash('error', 'Assignació no permesa per al teu rol.');
                    $this->redirect('usuaris/edit/' . (int)$id);
                }

                Usuari::assignInstalacio((int)$id, $instalacioId, $rolId);
                Torn::syncTornsForUsuari((int)$id, $instalacioId, $this->postedTorns());
            }
        }

        $this->setFlash('success', 'Usuari actualitzat correctament.');
        $this->redirect('usuaris');
    }

    public function toggle(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('usuaris');
        }

        $usuari = Usuari::find((int)$id);
        if (!$usuari) {
            $this->setFlash('error', 'Usuari no trobat.');
            $this->redirect('usuaris');
        }
        if (!$this->canManageUser((int)$id)) {
            $this->setFlash('error', 'No tens permís per gestionar aquest usuari.');
            $this->redirect('usuaris');
        }

        // Cannot deactivate yourself
        if ((int)$id === (int)$_SESSION['user_id']) {
            $this->setFlash('error', 'No pots desactivar el teu propi compte.');
            $this->redirect('usuaris');
        }
        if (!$this->potGestionarCompte($usuari)) {
            $this->setFlash('error', self::MISSATGE_COMPTE_COMPARTIT);
            $this->redirect('usuaris');
        }

        Usuari::update((int)$id, ['actiu' => $usuari['actiu'] ? 0 : 1]);

        $this->setFlash('success', $usuari['actiu'] ? 'Usuari desactivat.' : 'Usuari activat.');
        $this->redirect('usuaris');
    }

    private function getTornsPerInstalacio(array $instalacions): array
    {
        if (!Torn::supportsUsuariTorn()) {
            return [];
        }

        $map = [];
        foreach ($instalacions as $inst) {
            $torns = Torn::all(['instalacio_id' => (int)$inst['id'], 'actiu' => 1], 'nom ASC');
            if (!empty($torns)) {
                $map[(int)$inst['id']] = $torns;
            }
        }

        return $map;
    }

    private function postedTorns(): array
    {
        $torns = $this->post('torns', []);
        return is_array($torns) ? $torns : [];
    }

    /**
     * Sincronitza les assignacions instal·lació→rol (i torns) d'un usuari quan
     * qui edita és superadmin. Format del POST:
     *   assign[<instalacioId>][rol]      = <rolId>   (0 o buit = desassignar)
     *   assign[<instalacioId>][torns][]  = <tornId>
     * Recorre TOTES les instal·lacions actives: les que tenen rol s'assignen,
     * la resta es desassignen (idempotent).
     */
    private function syncAssignacionsSuperadmin(int $usuariId): void
    {
        $assign = $this->post('assign', []);
        if (!is_array($assign)) {
            $assign = [];
        }

        foreach (Instalacio::actives() as $inst) {
            $instId = (int)$inst['id'];
            $row = $assign[$instId] ?? null;
            $rolId = is_array($row) ? (int)($row['rol'] ?? 0) : 0;

            if ($rolId > 0 && $this->canAssignRole($rolId)) {
                Usuari::assignInstalacio($usuariId, $instId, $rolId);
                $torns = (is_array($row) && isset($row['torns']) && is_array($row['torns'])) ? $row['torns'] : [];
                Torn::syncTornsForUsuari($usuariId, $instId, $torns);
            } else {
                // Desassignar: neteja també els torns d'aquesta instal·lació.
                Torn::syncTornsForUsuari($usuariId, $instId, []);
                Usuari::removeInstalacio($usuariId, $instId);
            }
        }
    }

    private function getInstalacionsDisponibles(): array
    {
        if ($_SESSION['is_superadmin'] ?? false) {
            return Instalacio::actives();
        }
        $instalacioId = $this->currentInstalacioId();
        $inst = Instalacio::find($instalacioId);
        return $inst ? [$inst] : [];
    }

    private function getRols(): array
    {
        $db = Database::getInstance();
        if ($_SESSION['is_superadmin'] ?? false) {
            return $db->query('SELECT * FROM rols ORDER BY id')->fetchAll();
        }
        return $db->query('SELECT * FROM rols WHERE nom NOT IN ("superadmin") ORDER BY id')->fetchAll();
    }

    private function canManageUser(int $usuariId): bool
    {
        if ($_SESSION['is_superadmin'] ?? false) {
            return true;
        }
        if (Usuari::isSuperadmin($usuariId)) {
            return false;
        }

        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            return false;
        }

        return Usuari::belongsToInstalacio($usuariId, (int)$instalacioId);
    }

    /**
     * Nom, email, contrasenya, estat i enllaços d'accés. Vegeu Usuari::compteGestionable().
     */
    private function potGestionarCompte(array $usuari): bool
    {
        if ($_SESSION['is_superadmin'] ?? false) {
            return true;
        }

        $usuariId = (int)$usuari['id'];

        return Usuari::compteGestionable(
            false,
            $this->currentUserId(),
            $usuari,
            Usuari::instalacioIdsPerUsuaris([$usuariId])[$usuariId] ?? [],
            Usuari::instalacioIdsAmbRol($this->currentUserId(), 'admin_instalacio')
        );
    }

    private function canAssignInstalacio(int $instalacioId): bool
    {
        if ($_SESSION['is_superadmin'] ?? false) {
            return Instalacio::find($instalacioId) !== null;
        }

        return $instalacioId === (int)$this->currentInstalacioId();
    }

    private function canAssignRole(int $rolId): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT nom FROM rols WHERE id = ? LIMIT 1');
        $stmt->execute([$rolId]);
        $role = $stmt->fetch();

        if (!$role) {
            return false;
        }

        if ($_SESSION['is_superadmin'] ?? false) {
            return true;
        }

        return $role['nom'] !== 'superadmin';
    }
}
