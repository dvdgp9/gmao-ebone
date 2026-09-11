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
use App\Models\Instalacio;
use App\Models\Sistema;
use App\Models\Database;

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

        $isTecnic = empty($_SESSION['is_superadmin']) && $this->currentRole() === 'tecnic';
        $tornIdsTecnic = $isTecnic
            ? Torn::tornIdsByUsuariInstalacio($this->currentUserId(), $instalacioId)
            : null;
        $senseTornsAssignats = $isTecnic && empty($tornIdsTecnic);
        $tornsAssignats = $isTecnic
            ? array_values(array_filter(
                Torn::allByInstalacio($instalacioId),
                static fn(array $torn): bool => in_array((int)$torn['id'], $tornIdsTecnic, true)
            ))
            : [];

        $search = trim($this->get('q', ''));
        if ($senseTornsAssignats) {
            $tasques = [];
        } else {
            $tasques = $search !== ''
                ? TascaPla::searchByInstalacio($instalacioId, $search, 'data_propera_realitzacio ASC', $tornIdsTecnic)
                : TascaPla::allByInstalacio($instalacioId, 'data_propera_realitzacio ASC', $tornIdsTecnic);
        }

        $this->view('pla.index', [
            'title' => 'Pla de Manteniment',
            'tasques' => $tasques,
            'search' => $search,
            'isTecnic' => $isTecnic,
            'tornsAssignats' => $tornsAssignats,
            'senseTornsAssignats' => $senseTornsAssignats,
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

        $preselectedCatalogId = (int)$this->get('tasca_cataleg_id', 0);
        $selectedCatalog = $preselectedCatalogId > 0 ? TascaCataleg::find($preselectedCatalogId) : null;
        if ($selectedCatalog && (int)$selectedCatalog['instalacio_id'] !== $instalacioId) {
            $selectedCatalog = null;
            $preselectedCatalogId = 0;
        }

        $this->view('pla.form', [
            'title' => 'Nova tasca',
            'tasca' => null,
            'cataleg' => TascaCataleg::allWithRelations($instalacioId),
            'selectedCatalog' => $selectedCatalog,
            'preselectedCatalogId' => $preselectedCatalogId,
            'origen' => $this->get('origen') === 'cataleg' ? 'cataleg' : 'pla',
            'afegirAlPla' => $this->get('afegir') === '1' || $this->get('origen') !== 'cataleg',
            'sistemes' => Sistema::allOrdered(),
            'tipusEquip' => $this->getTipusEquip(),
            'equips' => Equip::allByInstalacio($instalacioId),
            'espais' => Espai::allByInstalacio($instalacioId),
            'torns' => Torn::allByInstalacio($instalacioId),
            'selectedTornIds' => [],
            'periodicitats' => Periodicitat::allOrdered(),
            'normatives' => Normativa::allOrdered(),
            'flash' => $this->getFlash(),
        ]);
    }

    /** Alta ràpida: diverses tasques (catàleg + pla) en una sola pantalla. */
    public function altaRapida(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            $this->setFlash('error', 'Selecciona una instal·lació abans d\'afegir tasques.');
            $this->redirect('dashboard');
        }

        $moduls = Instalacio::modulsActiusById($instalacioId);

        $this->view('pla.alta_rapida', [
            'title' => 'Alta ràpida de tasques',
            'periodicitats' => Periodicitat::allOrdered(),
            'espais' => in_array('espais', $moduls, true) ? Espai::allByInstalacio($instalacioId) : [],
            'torns' => in_array('torns', $moduls, true) ? Torn::allByInstalacio($instalacioId) : [],
            'returnTo' => $this->getReturnTo(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function altaRapidaStore(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('pla/alta-rapida');
        }

        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            $this->setFlash('error', 'Selecciona una instal·lació abans d\'afegir tasques.');
            $this->redirect('dashboard');
        }

        $noms = (array)$this->post('nom', []);
        $codis = (array)$this->post('codi', []);
        $periodicitats = (array)$this->post('periodicitat_id', []);
        $dates = (array)$this->post('data_primera', []);
        $espais = (array)$this->post('espai_id', []);
        $torns = (array)$this->post('torn_id', []);

        // Catàleg existent per reutilitzar tasques amb el mateix nom
        $catalegMap = [];
        foreach (TascaCataleg::query('SELECT id, nom FROM tasques_cataleg WHERE activa = 1 AND instalacio_id = ?', [$instalacioId]) as $r) {
            $catalegMap[mb_strtolower(trim($r['nom']))] = (int)$r['id'];
        }

        $creades = 0;
        $omeses = 0;
        $errors = [];

        foreach ($noms as $i => $nomRaw) {
            $nom = trim((string)$nomRaw);
            if ($nom === '') {
                continue;
            }

            $periodicitatId = (int)($periodicitats[$i] ?? 0);
            if ($periodicitatId <= 0) {
                $omeses++;
                if (count($errors) < 5) {
                    $errors[] = "\"{$nom}\": falta la periodicitat";
                }
                continue;
            }

            $espaiId = (int)($espais[$i] ?? 0) ?: null;
            $tornId = (int)($torns[$i] ?? 0) ?: null;
            if ($espaiId && !Espai::belongsToInstalacio($espaiId, $instalacioId)) {
                $espaiId = null;
            }
            if ($tornId && !Torn::belongsToInstalacio($tornId, $instalacioId)) {
                $tornId = null;
            }

            $dataPrimera = trim((string)($dates[$i] ?? ''));
            $dataValida = \DateTime::createFromFormat('Y-m-d', $dataPrimera);
            $dataPropera = ($dataValida && $dataValida->format('Y-m-d') === $dataPrimera) ? $dataPrimera : date('Y-m-d');

            $catalegId = $catalegMap[mb_strtolower($nom)] ?? null;
            if (!$catalegId) {
                $catalegId = TascaCataleg::create([
                    'instalacio_id' => $instalacioId,
                    'codi' => null,
                    'sistema_id' => null,
                    'tipus_equip_id' => null,
                    'nom' => $nom,
                    'descripcio' => null,
                    'periodicitat_normativa_id' => $periodicitatId,
                    'normativa_id' => null,
                    'empresa_responsable' => null,
                    'activa' => 1,
                ]);
                $catalegMap[mb_strtolower($nom)] = $catalegId;
            }

            TascaPla::create([
                'instalacio_id' => $instalacioId,
                'codi' => mb_substr(trim((string)($codis[$i] ?? '')), 0, 50) ?: null,
                'tasca_cataleg_id' => $catalegId,
                'equip_id' => null,
                'espai_id' => $espaiId,
                'torn_id' => $tornId,
                'periodicitat_id' => $periodicitatId,
                'periodicitat_normativa_id' => $periodicitatId,
                'normativa_id' => null,
                'observacions' => null,
                'data_darrera_realitzacio' => null,
                'data_propera_realitzacio' => $dataPropera,
                'data_darrera_no_realitzacio' => null,
                'en_curs' => 1,
                'comentaris' => null,
            ]);
            $creades++;
        }

        $msg = "{$creades} tasques afegides al pla.";
        if ($omeses > 0) {
            $msg .= " {$omeses} omeses (" . implode('; ', $errors) . ').';
        }
        $this->setFlash($creades > 0 ? 'success' : 'error', $msg);

        $returnTo = (string)$this->post('return_to', '');
        if ($returnTo !== '' && str_starts_with($returnTo, 'instalacions/onboarding/')) {
            $this->redirect($returnTo);
        }
        $this->redirect('pla');
    }

    private function getReturnTo(): string
    {
        $returnTo = (string)$this->get('return_to', '');
        if ($returnTo !== '' && str_starts_with($returnTo, 'instalacions/onboarding/')) {
            return $returnTo;
        }

        return '';
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
        $tornIds = $this->getTornIds();
        $planData = $this->getFormData($tornIds);
        $catalogData = $this->getCatalogFormData();
        $catalogId = (int)$this->post('tasca_cataleg_id', 0);
        $afegirAlPla = (bool)$this->post('afegir_al_pla', false);
        $origen = $this->post('origen') === 'cataleg' ? 'cataleg' : 'pla';

        // El codi pot identificar una assignació concreta del pla. En reutilitzar
        // una tasca no sobreescrivim el codi base del repositori.
        if ($catalogId > 0 && $afegirAlPla) {
            unset($catalogData['codi']);
        }

        if ($catalogId > 0 && !TascaCataleg::belongsToInstalacio($catalogId, $instalacioId)) {
            $this->setFlash('error', 'La tasca del repositori no és vàlida per a aquesta instal·lació.');
            $this->redirect('pla/create?origen=' . $origen);
        }
        if ($catalogId === 0 && $catalogData['nom'] === '') {
            $this->setFlash('error', 'Cal indicar el nom de la tasca.');
            $this->redirect('pla/create?origen=' . $origen);
        }
        if (!$afegirAlPla) {
            $catalogData['instalacio_id'] = $instalacioId;
            $catalogData['activa'] = 1;
            if ($catalogId > 0) {
                TascaCataleg::update($catalogId, $catalogData);
            } else {
                TascaCataleg::create($catalogData);
            }
            $this->setFlash('success', 'Tasca desada al repositori. La podràs activar al pla quan la necessitis.');
            $this->redirect('tasques-cataleg');
        }

        $planData['instalacio_id'] = $instalacioId;
        if (!$this->tornsBelongToCurrentInstalacio($tornIds, $instalacioId)) {
            $this->setFlash('error', 'Hi ha torns no vàlids per a aquesta instal·lació.');
            $this->redirect('pla/create');
        }
        if (!$this->espaiBelongsToCurrentInstalacio($planData['espai_id'], $instalacioId)) {
            $this->setFlash('error', 'Espai no vàlid per a aquesta instal·lació.');
            $this->redirect('pla/create');
        }
        if (!$this->equipBelongsToCurrentInstalacio($planData['equip_id'], $instalacioId)) {
            $this->setFlash('error', 'Equip no vàlid per a aquesta instal·lació.');
            $this->redirect('pla/create');
        }

        $db = Database::getInstance();
        try {
            $db->beginTransaction();
            $catalogData['instalacio_id'] = $instalacioId;
            $catalogData['activa'] = 1;
            if ($catalogId > 0) {
                TascaCataleg::update($catalogId, $catalogData);
            } else {
                $catalogId = TascaCataleg::create($catalogData);
            }

            $planData['tasca_cataleg_id'] = $catalogId;
            $id = TascaPla::create($planData);
            TascaPla::syncTorns($id, $tornIds, $instalacioId);

            if ($planData['data_darrera_realitzacio'] && $planData['periodicitat_id']) {
                TascaPla::recalcularPropera($id);
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->setFlash('error', 'No s\'ha pogut crear la tasca. Revisa les dades i torna-ho a provar.');
            $this->redirect('pla/create?origen=' . $origen);
        }

        $this->setFlash('success', 'Tasca creada i afegida al pla de manteniment.');
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
            'selectedCatalog' => TascaCataleg::find((int)$tasca['tasca_cataleg_id']),
            'preselectedCatalogId' => (int)$tasca['tasca_cataleg_id'],
            'origen' => 'pla',
            'sistemes' => Sistema::allOrdered(),
            'tipusEquip' => $this->getTipusEquip(),
            'equips' => Equip::allByInstalacio($instalacioId),
            'espais' => Espai::allByInstalacio($instalacioId),
            'torns' => Torn::allByInstalacio($instalacioId),
            'selectedTornIds' => TascaPla::tornIds((int)$id),
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

        $tornIds = $this->getTornIds();
        $data = $this->getFormData($tornIds);
        if (!$this->tornsBelongToCurrentInstalacio($tornIds, (int)$tasca['instalacio_id'])) {
            $this->setFlash('error', 'Hi ha torns no vàlids per a aquesta instal·lació.');
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

        $catalogId = (int)$data['tasca_cataleg_id'];
        if (!TascaCataleg::belongsToInstalacio($catalogId, (int)$tasca['instalacio_id'])) {
            $this->setFlash('error', 'La tasca del repositori no és vàlida per a aquesta instal·lació.');
            $this->redirect('pla/edit/' . (int)$id);
        }

        $db = Database::getInstance();
        try {
            $db->beginTransaction();
            $catalogData = $this->getCatalogFormData();
            unset($catalogData['codi']);
            TascaCataleg::update($catalogId, $catalogData);
            TascaPla::update((int)$id, $data);
            TascaPla::syncTorns((int)$id, $tornIds, (int)$tasca['instalacio_id']);

            if ($data['data_darrera_realitzacio'] && $data['periodicitat_id']) {
                TascaPla::recalcularPropera((int)$id);
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->setFlash('error', 'No s\'ha pogut actualitzar la tasca.');
            $this->redirect('pla/edit/' . (int)$id);
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

        TascaPla::update((int)$id, ['en_curs' => 0]);
        $this->setFlash('success', 'Tasca desactivada del pla. La pots recuperar des del repositori.');
        $this->redirect('pla');
    }

    public function reactivate(string $id): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('tasques-cataleg');
        }

        $tasca = TascaPla::find((int)$id);
        if (!$tasca || $tasca['instalacio_id'] != $this->currentInstalacioId()) {
            $this->setFlash('error', 'Tasca no trobada.');
            $this->redirect('tasques-cataleg');
        }

        TascaPla::update((int)$id, ['en_curs' => 1]);
        TascaCataleg::update((int)$tasca['tasca_cataleg_id'], ['activa' => 1]);
        $this->setFlash('success', 'Tasca reactivada al pla de manteniment.');
        $this->redirect('pla');
    }

    /**
     * Assignació massiva de la data de propera realització a les tasques seleccionades.
     * Pensat per a instal·lacions noves, on l'Excel arriba sense dates i posar-les
     * una a una és inviable.
     */
    public function programar(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment']);
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect('pla');
        }

        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            $this->setFlash('error', 'Selecciona una instal·lació.');
            $this->redirect('dashboard');
        }

        // Es torna al mateix filtre des d'on s'ha programat.
        $search = trim((string)$this->post('q', ''));
        $tornarA = 'pla' . ($search !== '' ? '?q=' . urlencode($search) : '');

        $ids = (array)$this->post('ids', []);
        if (empty($ids)) {
            $this->setFlash('error', 'No has seleccionat cap tasca.');
            $this->redirect($tornarA);
        }

        $data = trim((string)$this->post('data_propera_realitzacio', ''));
        $dataValida = \DateTime::createFromFormat('Y-m-d', $data);
        if (!$dataValida || $dataValida->format('Y-m-d') !== $data) {
            $this->setFlash('error', 'Cal indicar una data vàlida.');
            $this->redirect($tornarA);
        }

        $afectades = TascaPla::programarPropera($ids, $data, $instalacioId);
        if ($afectades === 0) {
            $this->setFlash('error', 'No s\'ha pogut programar cap tasca de les seleccionades.');
            $this->redirect($tornarA);
        }

        $this->setFlash('success', $afectades === 1
            ? 'Tasca programada per al ' . format_date($data) . '.'
            : "{$afectades} tasques programades per al " . format_date($data) . '.');
        $this->redirect($tornarA);
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
     * sense filtre explícit es mostren tots els seus torns.
     * Retorna [torns visibles, tornId seleccionat, filtre per la consulta, avís o null].
     */
    private function resolveTornScope(int $instalacioId, ?int $tornId): array
    {
        $torns = Torn::allByInstalacio($instalacioId);

        $isTecnic = empty($_SESSION['is_superadmin'])
            && $this->currentRole() === 'tecnic';

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

    private function getFormData(array $tornIds = []): array
    {
        return [
            'codi' => mb_substr(trim($this->post('codi', '')), 0, 50) ?: null,
            'tasca_cataleg_id' => (int)$this->post('tasca_cataleg_id'),
            'equip_id' => $this->post('equip_id') ?: null,
            'espai_id' => $this->post('espai_id') ?: null,
            'torn_id' => $tornIds[0] ?? null,
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

    private function getTornIds(): array
    {
        $ids = (array)$this->post('torn_ids', []);
        if (empty($ids) && $this->post('torn_id')) {
            $ids = [$this->post('torn_id')];
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    private function getCatalogFormData(): array
    {
        return [
            'codi' => mb_substr(trim($this->post('codi', '')), 0, 50) ?: null,
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

    private function tornsBelongToCurrentInstalacio(array $tornIds, ?int $instalacioId): bool
    {
        if (empty($tornIds)) {
            return true;
        }

        if ($instalacioId === null) {
            return false;
        }

        foreach ($tornIds as $tornId) {
            if (!Torn::belongsToInstalacio((int)$tornId, $instalacioId)) {
                return false;
            }
        }

        return true;
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
