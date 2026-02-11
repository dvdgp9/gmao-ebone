<?php

namespace App\Controllers;

use App\Core\Controller;
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

        Instalacio::create([
            'nom' => trim($this->post('nom', '')),
            'adreca' => trim($this->post('adreca', '')) ?: null,
            'telefon' => trim($this->post('telefon', '')) ?: null,
            'email' => trim($this->post('email', '')) ?: null,
            'activa' => $this->post('activa', 1) ? 1 : 0,
        ]);
        $this->setFlash('success', 'Instal·lació creada correctament.');
        $this->redirect('instalacions');
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
        $this->setFlash('success', 'Instal·lació actualitzada correctament.');
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
}
