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

        $search = trim($this->get('q', ''));
        $tasques = $search !== ''
            ? TascaPla::searchByInstalacio($instalacioId, $search)
            : TascaPla::allByInstalacio($instalacioId);

        $this->view('pla.index', [
            'title' => 'Pla de Manteniment',
            'tasques' => $tasques,
            'search' => $search,
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            $this->setFlash('error', 'Selecciona una instal·lació abans de crear tasques del pla.');
            $this->redirect('dashboard');
        }

        $this->view('pla.form', [
            'title' => 'Afegir Tasca al Pla',
            'tasca' => null,
            'cataleg' => TascaCataleg::allWithRelations($instalacioId),
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
        if (!$instalacioId) {
            $this->setFlash('error', 'Selecciona una instal·lació abans de crear tasques del pla.');
            $this->redirect('dashboard');
        }
        $data = $this->getFormData();
        $data['instalacio_id'] = $instalacioId;
        if (!$this->tornBelongsToCurrentInstalacio($data['torn_id'], $instalacioId)) {
            $this->setFlash('error', 'Torn no vàlid per a aquesta instal·lació.');
            $this->redirect('pla/create');
        }
        if (!$this->espaiBelongsToCurrentInstalacio($data['espai_id'], $instalacioId)) {
            $this->setFlash('error', 'Espai no vàlid per a aquesta instal·lació.');
            $this->redirect('pla/create');
        }
        if (!$this->equipBelongsToCurrentInstalacio($data['equip_id'], $instalacioId)) {
            $this->setFlash('error', 'Equip no vàlid per a aquesta instal·lació.');
            $this->redirect('pla/create');
        }

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
            'cataleg' => TascaCataleg::allWithRelations($instalacioId),
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
        if (!$this->tornBelongsToCurrentInstalacio($data['torn_id'], (int)$tasca['instalacio_id'])) {
            $this->setFlash('error', 'Torn no vàlid per a aquesta instal·lació.');
            $this->redirect('pla/edit/' . (int)$id);
        }
        if (!$this->espaiBelongsToCurrentInstalacio($data['espai_id'], (int)$tasca['instalacio_id'])) {
            $this->setFlash('error', 'Espai no vàlid per a aquesta instal·lació.');
            $this->redirect('pla/edit/' . (int)$id);
        }
        if (!$this->equipBelongsToCurrentInstalacio($data['equip_id'], (int)$tasca['instalacio_id'])) {
            $this->setFlash('error', 'Equip no vàlid per a aquesta instal·lació.');
            $this->redirect('pla/edit/' . (int)$id);
        }

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
        $search = trim($this->get('q', ''));

        $dilluns = new \DateTime('monday this week');
        $dilluns->modify("{$setmanaOffset} weeks");
        $diumenge = clone $dilluns;
        $diumenge->modify('+6 days');

        [$torns, $tornId, $tornFilter, $avisTecnic] = $this->resolveTornScope($instalacioId, $tornId);

        $tasques = $avisTecnic ? [] : TascaPla::getSetmanaSearch(
            $instalacioId,
            $dilluns->format('Y-m-d'),
            $diumenge->format('Y-m-d'),
            $tornFilter,
            $search
        );

        $this->view('setmana.index', [
            'title' => 'Vista Setmanal',
            'tasques' => $tasques,
            'torns' => $torns,
            'tornActual' => $tornId,
            'dilluns' => $dilluns,
            'diumenge' => $diumenge,
            'setmanaOffset' => $setmanaOffset,
            'setmanaNum' => (int)$dilluns->format('W'),
            'search' => $search,
            'flash' => $this->getFlash() ?: ($avisTecnic ? ['type' => 'error', 'message' => $avisTecnic] : null),
        ]);
    }

    public function dia(): void
    {
        $this->requireAuth();
        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            $this->setFlash('error', 'Selecciona una instal·lació.');
            $this->redirect('dashboard');
        }

        $tornId = $this->get('torn') ? (int)$this->get('torn') : null;
        $dataParam = $this->get('data', date('Y-m-d'));
        $dataSeleccionada = \DateTime::createFromFormat('Y-m-d', $dataParam) ?: new \DateTime();
        $search = trim($this->get('q', ''));

        [$torns, $tornId, $tornFilter, $avisTecnic] = $this->resolveTornScope($instalacioId, $tornId);

        $tasques = $avisTecnic ? [] : TascaPla::getDia(
            $instalacioId,
            $dataSeleccionada->format('Y-m-d'),
            $tornFilter,
            $search
        );

        $this->view('dia.index', [
            'title' => 'Vista Diària',
            'tasques' => $tasques,
            'torns' => $torns,
            'tornActual' => $tornId,
            'dataSeleccionada' => $dataSeleccionada,
            'search' => $search,
            'flash' => $this->getFlash() ?: ($avisTecnic ? ['type' => 'error', 'message' => $avisTecnic] : null),
        ]);
    }

    /**
     * Determina l'àmbit de torns visible per a l'usuari actual.
     * Per al rol tecnic: només els seus torns assignats; un ?torn= aliè s'ignora;
     * sense filtre explícit es mostren tots els seus torns (+ tasques sense torn).
     * Retorna [torns visibles, tornId seleccionat, filtre per la consulta, avís o null].
     */
    private function resolveTornScope(int $instalacioId, ?int $tornId): array
    {
        $torns = Torn::allByInstalacio($instalacioId);

        $isTecnic = empty($_SESSION['is_superadmin'])
            && $this->currentRole() === 'tecnic'
            && Torn::supportsUsuariTorn();

        if (!$isTecnic) {
            return [$torns, $tornId, $tornId, null];
        }

        $allowedIds = Torn::tornIdsByUsuariInstalacio($this->currentUserId(), $instalacioId);

        if (empty($allowedIds)) {
            return [[], null, null, "No tens cap torn assignat. Contacta amb l'administrador."];
        }

        $torns = array_values(array_filter($torns, function (array $t) use ($allowedIds) {
            return in_array((int)$t['id'], $allowedIds);
        }));

        if ($tornId !== null && !in_array($tornId, $allowedIds)) {
            $tornId = null;
        }

        return [$torns, $tornId, $tornId ?: $allowedIds, null];
    }

    private function getFormData(): array
    {
        return [
            'tasca_cataleg_id' => (int)$this->post('tasca_cataleg_id'),
            'equip_id' => $this->post('equip_id') ?: null,
            'espai_id' => $this->post('espai_id') ?: null,
            'torn_id' => $this->post('torn_id') ? (int)$this->post('torn_id') : null,
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

    private function tornBelongsToCurrentInstalacio(?int $tornId, ?int $instalacioId): bool
    {
        if ($tornId === null) {
            return true;
        }

        if ($instalacioId === null) {
            return false;
        }

        return Torn::belongsToInstalacio($tornId, $instalacioId);
    }

    private function espaiBelongsToCurrentInstalacio(mixed $espaiId, ?int $instalacioId): bool
    {
        if (empty($espaiId)) {
            return true;
        }

        if ($instalacioId === null) {
            return false;
        }

        return Espai::belongsToInstalacio((int)$espaiId, $instalacioId);
    }

    private function equipBelongsToCurrentInstalacio(mixed $equipId, ?int $instalacioId): bool
    {
        if (empty($equipId)) {
            return true;
        }

        if ($instalacioId === null) {
            return false;
        }

        return Equip::belongsToInstalacio((int)$equipId, $instalacioId);
    }
}
