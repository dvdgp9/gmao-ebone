<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Equip;
use App\Models\Sistema;
use App\Models\Espai;
use App\Models\Periodicitat;
use App\Models\Normativa;
use App\Models\Database;

class EquipController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            $this->setFlash('error', 'Selecciona una instal·lació.');
            $this->redirect('dashboard');
        }

        $page = max(1, (int)$this->get('page', 1));
        $search = trim($this->get('q', ''));
        $sistemaFilter = $this->get('sistema', '');

        if ($search || $sistemaFilter) {
            $equips = Equip::searchByInstalacio($instalacioId, $search, $sistemaFilter ? (int)$sistemaFilter : null);
            $data = ['items' => $equips, 'total' => count($equips), 'per_page' => count($equips), 'current_page' => 1, 'total_pages' => 1];
        } else {
            $data = Equip::paginateByInstalacio($instalacioId, 25, $page);
        }

        $this->view('equips.index', [
            'title' => 'Equips',
            'equips' => $data['items'],
            'pagination' => $data,
            'sistemes' => Sistema::allOrdered(),
            'search' => $search,
            'sistemaFilter' => $sistemaFilter,
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        $this->view('equips.form', [
            'title' => 'Nou Equip',
            'equip' => null,
            'sistemes' => Sistema::allOrdered(),
            'tipusEquip' => $this->getTipusEquip(),
            'espais' => Espai::allByInstalacio($this->currentInstalacioId()),
            'estats' => $this->getEstats(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('equips');
        }

        $data = $this->getFormData();
        $data['instalacio_id'] = $this->currentInstalacioId();

        Equip::create($data);
        $this->setFlash('success', 'Equip creat correctament.');
        $this->redirect('equips');
    }

    public function edit(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        $equip = Equip::find((int)$id);
        if (!$equip || $equip['instalacio_id'] != $this->currentInstalacioId()) {
            $this->setFlash('error', 'Equip no trobat.');
            $this->redirect('equips');
        }

        $this->view('equips.form', [
            'title' => 'Editar Equip',
            'equip' => $equip,
            'sistemes' => Sistema::allOrdered(),
            'tipusEquip' => $this->getTipusEquip(),
            'espais' => Espai::allByInstalacio($this->currentInstalacioId()),
            'estats' => $this->getEstats(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('equips');
        }

        $equip = Equip::find((int)$id);
        if (!$equip || $equip['instalacio_id'] != $this->currentInstalacioId()) {
            $this->setFlash('error', 'Equip no trobat.');
            $this->redirect('equips');
        }

        $data = $this->getFormData();
        Equip::update((int)$id, $data);
        $this->setFlash('success', 'Equip actualitzat correctament.');
        $this->redirect('equips');
    }

    public function delete(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('equips');
        }

        $equip = Equip::find((int)$id);
        if (!$equip || $equip['instalacio_id'] != $this->currentInstalacioId()) {
            $this->setFlash('error', 'Equip no trobat.');
            $this->redirect('equips');
        }

        Equip::delete((int)$id);
        $this->setFlash('success', 'Equip eliminat correctament.');
        $this->redirect('equips');
    }

    private function getFormData(): array
    {
        return [
            'sistema_id' => $this->post('sistema_id') ?: null,
            'tipus_equip_id' => $this->post('tipus_equip_id') ?: null,
            'numero' => $this->post('numero') ?: null,
            'nom_mn' => trim($this->post('nom_mn', '')),
            'nom_equip' => trim($this->post('nom_equip', '')),
            'notes' => trim($this->post('notes', '')) ?: null,
            'model' => trim($this->post('model', '')) ?: null,
            'dona_servei_a' => trim($this->post('dona_servei_a', '')) ?: null,
            'equipament' => trim($this->post('equipament', '')) ?: null,
            'espai_id' => $this->post('espai_id') ?: null,
            'planta' => trim($this->post('planta', '')) ?: null,
            'empresa_mantenedora' => trim($this->post('empresa_mantenedora', '')) ?: null,
            'data_installacio' => $this->post('data_installacio') ?: null,
            'fi_garantia' => $this->post('fi_garantia') ?: null,
            'estat_id' => $this->post('estat_id') ?: null,
        ];
    }

    private function getTipusEquip(): array
    {
        return Database::getInstance()->query('SELECT * FROM tipus_equip ORDER BY codi ASC')->fetchAll();
    }

    private function getEstats(): array
    {
        return Database::getInstance()->query('SELECT * FROM estats_equip ORDER BY ordre ASC')->fetchAll();
    }
}
