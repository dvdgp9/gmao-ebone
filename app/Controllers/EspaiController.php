<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Espai;

class EspaiController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            $this->setFlash('error', 'Selecciona una instal·lació.');
            $this->redirect('dashboard');
        }

        $espais = Espai::allByInstalacio($instalacioId);
        $this->view('espais.index', [
            'title' => 'Espais',
            'espais' => $espais,
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        $this->view('espais.form', [
            'title' => 'Nou Espai',
            'espai' => null,
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('espais');
        }

        Espai::create([
            'instalacio_id' => $this->currentInstalacioId(),
            'codi' => trim($this->post('codi', '')) ?: null,
            'nom' => trim($this->post('nom', '')),
            'planta' => trim($this->post('planta', '')) ?: null,
            'zona' => trim($this->post('zona', '')) ?: null,
        ]);
        $this->setFlash('success', 'Espai creat correctament.');
        $this->redirect('espais');
    }

    public function edit(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        $espai = Espai::find((int)$id);
        if (!$espai || $espai['instalacio_id'] != $this->currentInstalacioId()) {
            $this->setFlash('error', 'Espai no trobat.');
            $this->redirect('espais');
        }

        $this->view('espais.form', [
            'title' => 'Editar Espai',
            'espai' => $espai,
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('espais');
        }

        $espai = Espai::find((int)$id);
        if (!$espai || $espai['instalacio_id'] != $this->currentInstalacioId()) {
            $this->setFlash('error', 'Espai no trobat.');
            $this->redirect('espais');
        }

        Espai::update((int)$id, [
            'codi' => trim($this->post('codi', '')) ?: null,
            'nom' => trim($this->post('nom', '')),
            'planta' => trim($this->post('planta', '')) ?: null,
            'zona' => trim($this->post('zona', '')) ?: null,
        ]);
        $this->setFlash('success', 'Espai actualitzat correctament.');
        $this->redirect('espais');
    }

    public function delete(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('espais');
        }

        $espai = Espai::find((int)$id);
        if (!$espai || $espai['instalacio_id'] != $this->currentInstalacioId()) {
            $this->setFlash('error', 'Espai no trobat.');
            $this->redirect('espais');
        }

        Espai::delete((int)$id);
        $this->setFlash('success', 'Espai eliminat correctament.');
        $this->redirect('espais');
    }
}
