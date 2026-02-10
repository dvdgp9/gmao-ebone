<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\TascaPla;
use App\Models\TascaCataleg;
use App\Models\Equip;
use App\Models\Espai;
use App\Models\Torn;
use App\Models\Periodicitat;
use App\Models\Normativa;

class TascaPlaController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            $this->setFlash('error', 'Selecciona una instal·lació.');
            $this->redirect('dashboard');
        }

        $tasques = TascaPla::allByInstalacio($instalacioId);
        $this->view('pla.index', [
            'title' => 'Pla de Manteniment',
            'tasques' => $tasques,
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        $instalacioId = $this->currentInstalacioId();

        $this->view('pla.form', [
            'title' => 'Afegir Tasca al Pla',
            'tasca' => null,
            'cataleg' => TascaCataleg::allWithRelations(),
            'equips' => Equip::allByInstalacio($instalacioId),
            'espais' => Espai::allByInstalacio($instalacioId),
            'torns' => Torn::allByInstalacio($instalacioId),
            'periodicitats' => Periodicitat::allOrdered(),
            'normatives' => Normativa::allOrdered(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('pla');
        }

        $instalacioId = $this->currentInstalacioId();
        $data = $this->getFormData();
        $data['instalacio_id'] = $instalacioId;

        $id = TascaPla::create($data);

        if ($data['data_darrera_realitzacio'] && $data['periodicitat_id']) {
            TascaPla::recalcularPropera($id);
        }

        $this->setFlash('success', 'Tasca afegida al pla correctament.');
        $this->redirect('pla');
    }

    public function edit(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        $tasca = TascaPla::find((int)$id);
        $instalacioId = $this->currentInstalacioId();

        if (!$tasca || $tasca['instalacio_id'] != $instalacioId) {
            $this->setFlash('error', 'Tasca no trobada.');
            $this->redirect('pla');
        }

        $this->view('pla.form', [
            'title' => 'Editar Tasca del Pla',
            'tasca' => $tasca,
            'cataleg' => TascaCataleg::allWithRelations(),
            'equips' => Equip::allByInstalacio($instalacioId),
            'espais' => Espai::allByInstalacio($instalacioId),
            'torns' => Torn::allByInstalacio($instalacioId),
            'periodicitats' => Periodicitat::allOrdered(),
            'normatives' => Normativa::allOrdered(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('pla');
        }

        $tasca = TascaPla::find((int)$id);
        if (!$tasca || $tasca['instalacio_id'] != $this->currentInstalacioId()) {
            $this->setFlash('error', 'Tasca no trobada.');
            $this->redirect('pla');
        }

        $data = $this->getFormData();
        TascaPla::update((int)$id, $data);

        if ($data['data_darrera_realitzacio'] && $data['periodicitat_id']) {
            TascaPla::recalcularPropera((int)$id);
        }

        $this->setFlash('success', 'Tasca del pla actualitzada correctament.');
        $this->redirect('pla');
    }

    public function delete(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('pla');
        }

        $tasca = TascaPla::find((int)$id);
        if (!$tasca || $tasca['instalacio_id'] != $this->currentInstalacioId()) {
            $this->setFlash('error', 'Tasca no trobada.');
            $this->redirect('pla');
        }

        TascaPla::delete((int)$id);
        $this->setFlash('success', 'Tasca eliminada del pla.');
        $this->redirect('pla');
    }

    public function setmana(): void
    {
        $this->requireAuth();
        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            $this->setFlash('error', 'Selecciona una instal·lació.');
            $this->redirect('dashboard');
        }

        $tornId = $this->get('torn') ? (int)$this->get('torn') : null;
        $setmanaOffset = (int)$this->get('setmana', 0);

        $dilluns = new \DateTime('monday this week');
        $dilluns->modify("{$setmanaOffset} weeks");
        $diumenge = clone $dilluns;
        $diumenge->modify('+6 days');

        $tasques = TascaPla::getSetmana(
            $instalacioId,
            $dilluns->format('Y-m-d'),
            $diumenge->format('Y-m-d'),
            $tornId
        );

        $torns = Torn::allByInstalacio($instalacioId);

        $this->view('setmana.index', [
            'title' => 'Vista Setmanal',
            'tasques' => $tasques,
            'torns' => $torns,
            'tornActual' => $tornId,
            'dilluns' => $dilluns,
            'diumenge' => $diumenge,
            'setmanaOffset' => $setmanaOffset,
            'setmanaNum' => (int)$dilluns->format('W'),
            'flash' => $this->getFlash(),
        ]);
    }

    private function getFormData(): array
    {
        return [
            'tasca_cataleg_id' => (int)$this->post('tasca_cataleg_id'),
            'equip_id' => $this->post('equip_id') ?: null,
            'espai_id' => $this->post('espai_id') ?: null,
            'torn_id' => $this->post('torn_id') ?: null,
            'periodicitat_id' => $this->post('periodicitat_id') ?: null,
            'periodicitat_normativa_id' => $this->post('periodicitat_normativa_id') ?: null,
            'normativa_id' => $this->post('normativa_id') ?: null,
            'observacions' => trim($this->post('observacions', '')) ?: null,
            'data_darrera_realitzacio' => $this->post('data_darrera_realitzacio') ?: null,
            'data_propera_realitzacio' => $this->post('data_propera_realitzacio') ?: null,
            'en_curs' => $this->post('en_curs', 1) ? 1 : 0,
            'comentaris' => trim($this->post('comentaris', '')) ?: null,
        ];
    }
}
