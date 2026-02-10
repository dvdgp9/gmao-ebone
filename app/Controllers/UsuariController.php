<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Usuari;
use App\Models\Instalacio;
use App\Models\Database;

class UsuariController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);

        if ($_SESSION['is_superadmin'] ?? false) {
            $usuaris = Usuari::allWithRoles();
        } else {
            $usuaris = Usuari::allWithRoles($this->currentInstalacioId());
        }

        $this->view('usuaris.index', [
            'title' => 'Usuaris',
            'usuaris' => $usuaris,
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);

        $this->view('usuaris.form', [
            'title' => 'Nou Usuari',
            'usuari' => null,
            'assignacions' => [],
            'instalacions' => $this->getInstalacionsDisponibles(),
            'rols' => $this->getRols(),
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

        if (empty($email) || empty($password)) {
            $this->setFlash('error', 'Email i contrasenya són obligatoris.');
            $this->redirect('usuaris/create');
        }

        if (Usuari::findByEmail($email)) {
            $this->setFlash('error', 'Ja existeix un usuari amb aquest email.');
            $this->redirect('usuaris/create');
        }

        $id = Usuari::create([
            'nom' => trim($this->post('nom', '')),
            'cognoms' => trim($this->post('cognoms', '')) ?: null,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'actiu' => $this->post('actiu', 1) ? 1 : 0,
        ]);

        $instalacioId = (int)$this->post('instalacio_id');
        $rolId = (int)$this->post('rol_id');
        if ($instalacioId && $rolId) {
            Usuari::assignInstalacio($id, $instalacioId, $rolId);
        }

        $this->setFlash('success', 'Usuari creat correctament.');
        $this->redirect('usuaris');
    }

    public function edit(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        $usuari = Usuari::find((int)$id);
        if (!$usuari) {
            $this->setFlash('error', 'Usuari no trobat.');
            $this->redirect('usuaris');
        }

        $assignacions = Usuari::getAssignacions((int)$id);

        $this->view('usuaris.form', [
            'title' => 'Editar Usuari',
            'usuari' => $usuari,
            'assignacions' => $assignacions,
            'instalacions' => $this->getInstalacionsDisponibles(),
            'rols' => $this->getRols(),
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

        $data = [
            'nom' => trim($this->post('nom', '')),
            'cognoms' => trim($this->post('cognoms', '')) ?: null,
            'email' => trim($this->post('email', '')),
            'actiu' => $this->post('actiu', 1) ? 1 : 0,
        ];

        $password = $this->post('password', '');
        if (!empty($password)) {
            $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        Usuari::update((int)$id, $data);

        $instalacioId = (int)$this->post('instalacio_id');
        $rolId = (int)$this->post('rol_id');
        if ($instalacioId && $rolId) {
            Usuari::assignInstalacio((int)$id, $instalacioId, $rolId);
        }

        $this->setFlash('success', 'Usuari actualitzat correctament.');
        $this->redirect('usuaris');
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
}
