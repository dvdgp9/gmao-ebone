<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\TascaCataleg;
use App\Models\Sistema;
use App\Models\Periodicitat;
use App\Models\Normativa;
use App\Models\Database;

class TascaCatalegController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $search = trim($this->get('q', ''));
        $tasques = $search
            ? TascaCataleg::search($search)
            : TascaCataleg::allWithRelations();

        $this->view('tasques_cataleg.index', [
            'title' => 'Catàleg de Tasques',
            'tasques' => $tasques,
            'search' => $search,
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        $this->view('tasques_cataleg.form', [
            'title' => 'Nova Tasca',
            'tasca' => null,
            'sistemes' => Sistema::allOrdered(),
            'tipusEquip' => $this->getTipusEquip(),
            'periodicitats' => Periodicitat::allOrdered(),
            'normatives' => Normativa::allOrdered(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('tasques-cataleg');
        }

        TascaCataleg::create($this->getFormData());
        $this->setFlash('success', 'Tasca creada correctament.');
        $this->redirect('tasques-cataleg');
    }

    public function edit(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        $tasca = TascaCataleg::find((int)$id);
        if (!$tasca) {
            $this->setFlash('error', 'Tasca no trobada.');
            $this->redirect('tasques-cataleg');
        }

        $this->view('tasques_cataleg.form', [
            'title' => 'Editar Tasca',
            'tasca' => $tasca,
            'sistemes' => Sistema::allOrdered(),
            'tipusEquip' => $this->getTipusEquip(),
            'periodicitats' => Periodicitat::allOrdered(),
            'normatives' => Normativa::allOrdered(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('tasques-cataleg');
        }

        $tasca = TascaCataleg::find((int)$id);
        if (!$tasca) {
            $this->setFlash('error', 'Tasca no trobada.');
            $this->redirect('tasques-cataleg');
        }

        TascaCataleg::update((int)$id, $this->getFormData());
        $this->setFlash('success', 'Tasca actualitzada correctament.');
        $this->redirect('tasques-cataleg');
    }

    public function delete(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('tasques-cataleg');
        }

        TascaCataleg::update((int)$id, ['activa' => 0]);
        $this->setFlash('success', 'Tasca desactivada correctament.');
        $this->redirect('tasques-cataleg');
    }

    private function getFormData(): array
    {
        return [
            'codi' => trim($this->post('codi', '')) ?: null,
            'sistema_id' => $this->post('sistema_id') ?: null,
            'tipus_equip_id' => $this->post('tipus_equip_id') ?: null,
            'nom' => trim($this->post('nom', '')),
            'descripcio' => trim($this->post('descripcio', '')) ?: null,
            'periodicitat_normativa_id' => $this->post('periodicitat_normativa_id') ?: null,
            'normativa_id' => $this->post('normativa_id') ?: null,
            'empresa_responsable' => trim($this->post('empresa_responsable', '')) ?: null,
        ];
    }

    private function getTipusEquip(): array
    {
        return Database::getInstance()->query('SELECT * FROM tipus_equip ORDER BY codi ASC')->fetchAll();
    }
}
