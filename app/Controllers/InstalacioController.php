<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Database;
use App\Models\Instalacio;

class InstalacioController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['superadmin']);
        $instalacions = Instalacio::all([], 'nom ASC');
        $this->view('instalacions.index', [
            'title' => 'Instal·lacions',
            'instalacions' => $instalacions,
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['superadmin']);
        $this->view('instalacions.form', [
            'title' => 'Nova Instal·lació',
            'instalacio' => null,
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['superadmin']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('instalacions');
        }

        $instalacioId = Instalacio::create([
            'nom' => trim($this->post('nom', '')),
            'adreca' => trim($this->post('adreca', '')) ?: null,
            'telefon' => trim($this->post('telefon', '')) ?: null,
            'email' => trim($this->post('email', '')) ?: null,
            'activa' => $this->post('activa', 1) ? 1 : 0,
        ]);
        $this->refreshSuperadminAssignacions();
        $this->switchSuperadminContext($instalacioId);
        $this->setFlash('success', 'Instal·lació creada correctament.');
        $this->redirect('instalacions/onboarding/' . $instalacioId);
    }

    public function onboarding(string $id): void
    {
        $this->requireRole(['superadmin']);

        $instalacio = Instalacio::find((int)$id);
        if (!$instalacio) {
            $this->setFlash('error', 'Instal·lació no trobada.');
            $this->redirect('instalacions');
        }

        $this->switchSuperadminContext((int)$id);

        $db = Database::getInstance();
        $stats = [
            'espais' => $this->countByInstalacio($db, 'SELECT COUNT(*) FROM espais WHERE instalacio_id = ?', (int)$id),
            'torns' => $this->countByInstalacio($db, 'SELECT COUNT(*) FROM torns WHERE instalacio_id = ?', (int)$id),
            'equips' => $this->countByInstalacio($db, 'SELECT COUNT(*) FROM equips WHERE instalacio_id = ?', (int)$id),
            'tasques_pla' => $this->countByInstalacio($db, 'SELECT COUNT(*) FROM tasques_pla WHERE instalacio_id = ?', (int)$id),
        ];

        $this->view('instalacions.onboarding', [
            'title' => 'Configurar Instal·lació',
            'instalacio' => $instalacio,
            'stats' => $stats,
            'flash' => $this->getFlash(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireRole(['superadmin']);
        $instalacio = Instalacio::find((int)$id);
        if (!$instalacio) {
            $this->setFlash('error', 'Instal·lació no trobada.');
            $this->redirect('instalacions');
        }

        $this->view('instalacions.form', [
            'title' => 'Editar Instal·lació',
            'instalacio' => $instalacio,
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireRole(['superadmin']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('instalacions');
        }

        Instalacio::update((int)$id, [
            'nom' => trim($this->post('nom', '')),
            'adreca' => trim($this->post('adreca', '')) ?: null,
            'telefon' => trim($this->post('telefon', '')) ?: null,
            'email' => trim($this->post('email', '')) ?: null,
            'activa' => $this->post('activa', 1) ? 1 : 0,
        ]);
        $this->refreshSuperadminAssignacions();
        $this->setFlash('success', 'Instal·lació actualitzada correctament.');
        $this->redirect('instalacions');
    }

    public function delete(string $id): void
    {
        $this->requireRole(['superadmin']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('instalacions');
        }

        $instalacioId = (int)$id;
        $instalacio = Instalacio::find($instalacioId);
        if (!$instalacio) {
            $this->setFlash('error', 'Instal·lació no trobada.');
            $this->redirect('instalacions');
        }

        $dependencias = $this->getDeletionDependencyCounts($instalacioId);
        $bloquejos = array_filter($dependencias, static fn(int $count): bool => $count > 0);

        if (!empty($bloquejos)) {
            $parts = [];
            foreach ($bloquejos as $label => $count) {
                $parts[] = $count . ' ' . $label;
            }
            $this->setFlash('error', 'No es pot eliminar la instal·lació perquè encara té dades relacionades: ' . implode(', ', $parts) . '.');
            $this->redirect('instalacions');
        }

        Instalacio::delete($instalacioId);
        $this->refreshSuperadminAssignacions();

        if ((int)($_SESSION['instalacio_id'] ?? 0) === $instalacioId) {
            $_SESSION['instalacio_id'] = null;
            $_SESSION['instalacio_nom'] = 'Totes les instal·lacions';
            $_SESSION['current_role'] = 'superadmin';
        }

        $this->setFlash('success', 'Instal·lació eliminada correctament.');
        $this->redirect('instalacions');
    }

    public function switchInstalacio(): void
    {
        $this->requireAuth();
        if (!verify_csrf()) {
            $this->redirect('dashboard');
        }

        $instalacioId = (int)$this->post('instalacio_id');

        // Superadmin: tornar a vista global
        if ($instalacioId === 0 && !empty($_SESSION['is_superadmin'])) {
            $_SESSION['instalacio_id'] = null;
            $_SESSION['instalacio_nom'] = 'Totes les instal·lacions';
            $_SESSION['current_role'] = 'superadmin';
            $this->redirect('dashboard');
        }

        if (!empty($_SESSION['is_superadmin'])) {
            $db = Database::getInstance();
            $stmt = $db->prepare('SELECT id, nom FROM instalacions WHERE id = ? AND activa = 1 LIMIT 1');
            $stmt->execute([$instalacioId]);
            $instalacio = $stmt->fetch();

            if ($instalacio) {
                $this->refreshSuperadminAssignacions();
                $_SESSION['instalacio_id'] = (int)$instalacio['id'];
                $_SESSION['instalacio_nom'] = $instalacio['nom'];
                $_SESSION['current_role'] = 'superadmin';
            }

            $this->redirect('dashboard');
        }

        $assignacions = $_SESSION['assignacions'] ?? [];

        foreach ($assignacions as $a) {
            if ((int)$a['instalacio_id'] === $instalacioId) {
                $_SESSION['instalacio_id'] = $instalacioId;
                $_SESSION['instalacio_nom'] = $a['instalacio_nom'];
                $_SESSION['current_role'] = $a['rol_nom'];
                break;
            }
        }

        $this->redirect('dashboard');
    }

    private function refreshSuperadminAssignacions(): void
    {
        if (empty($_SESSION['is_superadmin'])) {
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->query('SELECT id AS instalacio_id, nom AS instalacio_nom FROM instalacions WHERE activa = 1 ORDER BY nom');
        $allInstalacions = $stmt->fetchAll();

        $_SESSION['assignacions'] = array_map(static fn(array $inst): array => [
            'instalacio_id' => $inst['instalacio_id'],
            'instalacio_nom' => $inst['instalacio_nom'],
            'rol_nom' => 'superadmin',
        ], $allInstalacions);
    }

    private function switchSuperadminContext(int $instalacioId): void
    {
        if (empty($_SESSION['is_superadmin'])) {
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id, nom FROM instalacions WHERE id = ? LIMIT 1');
        $stmt->execute([$instalacioId]);
        $instalacio = $stmt->fetch();

        if ($instalacio) {
            $_SESSION['instalacio_id'] = (int)$instalacio['id'];
            $_SESSION['instalacio_nom'] = $instalacio['nom'];
            $_SESSION['current_role'] = 'superadmin';
        }
    }

    private function countByInstalacio($db, string $sql, int $instalacioId): int
    {
        $stmt = $db->prepare($sql);
        $stmt->execute([$instalacioId]);
        return (int)$stmt->fetchColumn();
    }

    private function getDeletionDependencyCounts(int $instalacioId): array
    {
        $db = Database::getInstance();

        $count = static function ($db, string $sql, int $instalacioId): int {
            $stmt = $db->prepare($sql);
            $stmt->execute([$instalacioId]);
            return (int)$stmt->fetchColumn();
        };

        return [
            'usuaris assignats' => $count($db, 'SELECT COUNT(*) FROM usuari_instalacio WHERE instalacio_id = ?', $instalacioId),
            'equips' => $count($db, 'SELECT COUNT(*) FROM equips WHERE instalacio_id = ?', $instalacioId),
            'espais' => $count($db, 'SELECT COUNT(*) FROM espais WHERE instalacio_id = ?', $instalacioId),
            'torns' => $count($db, 'SELECT COUNT(*) FROM torns WHERE instalacio_id = ?', $instalacioId),
            'tasques del pla' => $count($db, 'SELECT COUNT(*) FROM tasques_pla WHERE instalacio_id = ?', $instalacioId),
        ];
    }
}
