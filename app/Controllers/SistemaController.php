<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Sistema;

class SistemaController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $search = trim($this->get('q', ''));

        $this->view('sistemes.index', [
            'title' => 'Sistemes',
            'sistemes' => Sistema::allWithUsage($search),
            'search' => $search,
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);

        $this->view('sistemes.form', [
            'title' => 'Nou Sistema',
            'sistema' => null,
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('sistemes');
        }

        $data = $this->getFormData();
        if ($data['codi'] === '' || $data['nom'] === '') {
            $this->setFlash('error', 'El codi i el nom són obligatoris.');
            $this->redirect('sistemes/create');
        }

        try {
            Sistema::create($data);
            $this->setFlash('success', 'Sistema creat correctament.');
            $this->redirect('sistemes');
        } catch (\Throwable $e) {
            $this->setFlash('error', 'No s\'ha pogut crear el sistema. Revisa que el codi no estigui duplicat.');
            $this->redirect('sistemes/create');
        }
    }

    public function edit(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        $sistema = Sistema::find((int)$id);
        if (!$sistema) {
            $this->setFlash('error', 'Sistema no trobat.');
            $this->redirect('sistemes');
        }

        $this->view('sistemes.form', [
            'title' => 'Editar Sistema',
            'sistema' => $sistema,
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('sistemes');
        }

        $sistema = Sistema::find((int)$id);
        if (!$sistema) {
            $this->setFlash('error', 'Sistema no trobat.');
            $this->redirect('sistemes');
        }

        $data = $this->getFormData();
        if ($data['codi'] === '' || $data['nom'] === '') {
            $this->setFlash('error', 'El codi i el nom són obligatoris.');
            $this->redirect('sistemes/edit/' . (int)$id);
        }

        try {
            Sistema::update((int)$id, $data);
            $this->setFlash('success', 'Sistema actualitzat correctament.');
            $this->redirect('sistemes');
        } catch (\Throwable $e) {
            $this->setFlash('error', 'No s\'ha pogut actualitzar el sistema. Revisa que el codi no estigui duplicat.');
            $this->redirect('sistemes/edit/' . (int)$id);
        }
    }

    public function delete(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('sistemes');
        }

        $sistema = Sistema::find((int)$id);
        if (!$sistema) {
            $this->setFlash('error', 'Sistema no trobat.');
            $this->redirect('sistemes');
        }

        if (!Sistema::canDelete((int)$id)) {
            $this->setFlash('error', 'No es pot eliminar aquest sistema perquè està assignat a equips o tasques actives.');
            $this->redirect('sistemes');
        }

        Sistema::delete((int)$id);
        $this->setFlash('success', 'Sistema eliminat correctament.');
        $this->redirect('sistemes');
    }

    private function getFormData(): array
    {
        return [
            'codi' => mb_strtoupper(trim((string)$this->post('codi', ''))),
            'nom' => trim((string)$this->post('nom', '')),
            'descripcio' => trim((string)$this->post('descripcio', '')) ?: null,
        ];
    }
}
