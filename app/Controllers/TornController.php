<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Torn;

class TornController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            $this->setFlash('error', 'Selecciona una instal·lació.');
            $this->redirect('dashboard');
        }

        $torns = Torn::allByInstalacio($instalacioId);
        $this->view('torns.index', [
            'title' => 'Torns',
            'torns' => $torns,
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        $this->view('torns.form', [
            'title' => 'Nou Torn',
            'torn' => null,
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('torns');
        }

        Torn::create([
            'instalacio_id' => $this->currentInstalacioId(),
            'nom' => trim($this->post('nom', '')),
            'dies_setmana' => $this->buildDiesJson(),
            'hora_inici' => $this->post('hora_inici') ?: null,
            'hora_fi' => $this->post('hora_fi') ?: null,
            'actiu' => $this->post('actiu', 1) ? 1 : 0,
        ]);
        $this->setFlash('success', 'Torn creat correctament.');
        $this->redirect('torns');
    }

    public function edit(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        $torn = Torn::find((int)$id);
        if (!$torn || $torn['instalacio_id'] != $this->currentInstalacioId()) {
            $this->setFlash('error', 'Torn no trobat.');
            $this->redirect('torns');
        }

        $this->view('torns.form', [
            'title' => 'Editar Torn',
            'torn' => $torn,
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('torns');
        }

        $torn = Torn::find((int)$id);
        if (!$torn || $torn['instalacio_id'] != $this->currentInstalacioId()) {
            $this->setFlash('error', 'Torn no trobat.');
            $this->redirect('torns');
        }

        Torn::update((int)$id, [
            'nom' => trim($this->post('nom', '')),
            'dies_setmana' => $this->buildDiesJson(),
            'hora_inici' => $this->post('hora_inici') ?: null,
            'hora_fi' => $this->post('hora_fi') ?: null,
            'actiu' => $this->post('actiu', 1) ? 1 : 0,
        ]);
        $this->setFlash('success', 'Torn actualitzat correctament.');
        $this->redirect('torns');
    }

    public function delete(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('torns');
        }

        $torn = Torn::find((int)$id);
        if (!$torn || $torn['instalacio_id'] != $this->currentInstalacioId()) {
            $this->setFlash('error', 'Torn no trobat.');
            $this->redirect('torns');
        }

        Torn::update((int)$id, ['actiu' => 0]);
        $this->setFlash('success', 'Torn desactivat correctament.');
        $this->redirect('torns');
    }

    private function buildDiesJson(): string
    {
        $dies = $this->post('dies', []);
        if (!is_array($dies)) $dies = [];
        return json_encode(array_values($dies));
    }
}
