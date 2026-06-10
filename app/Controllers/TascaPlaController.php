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
