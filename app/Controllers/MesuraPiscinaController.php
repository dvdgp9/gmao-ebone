<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Instalacio;
use App\Models\MesuraPiscina;
use App\Models\RegistreTasca;
use App\Models\TascaCataleg;
use App\Models\TascaPla;
use App\Services\ParametresExcel;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Paràmetres de l'aigua de piscina: el formulari de la Vista Diària desa la
 * mesura de cada moment del dia i la pestanya «Paràmetres piscina» en mostra l'historial.
 */
class MesuraPiscinaController extends Controller
{
    public function store(): void
    {
        $this->requireRole(['superadmin', 'admin_instalacio', 'cap_manteniment', 'tecnic']);
        $redirect = (string)$this->post('redirect', 'dia');
        if (!str_starts_with($redirect, 'dia')) {
            $redirect = 'dia';
        }
        if (!verify_csrf()) {
            $this->setFlash('error', 'Token de seguretat invàlid.');
            $this->redirect($redirect);
        }

        $instalacioId = (int)$this->currentInstalacioId();
        $tascaPlaId = (int)$this->post('tasca_pla_id');
        $tasca = TascaPla::find($tascaPlaId);
        $cataleg = $tasca ? TascaCataleg::find((int)$tasca['tasca_cataleg_id']) : null;
        if (!$tasca || (int)$tasca['instalacio_id'] !== $instalacioId || empty($cataleg['mesura_piscina'])) {
            $this->setFlash('error', 'Tasca de mesura no trobada.');
            $this->redirect($redirect);
        }

        // Es mesura sempre el dia d'avui, com quan es marca una tasca com a feta.
        $data = date('Y-m-d');
        $moment = (string)$this->post('moment', '');
        if (!isset(MesuraPiscina::MOMENTS[$moment])) {
            $this->setFlash('error', 'Tria el moment de la mesura (inici, 14 h o final).');
            $this->redirect($redirect);
        }

        // Els paràmetres d'un cop al dia es desen en el primer moment que els porta;
        // si ja són en un altre moment, aquí no es tornen a demanar ni a tocar.
        $mesuresAbans = MesuraPiscina::delDia($instalacioId, [$tascaPlaId], $data)[$tascaPlaId] ?? [];
        $ambDiaris = MesuraPiscina::momentAmbDiaris($mesuresAbans, $moment) === null;

        [$valors, $errors] = MesuraPiscina::valorsDesDePost($_POST, $ambDiaris);
        if ($errors !== []) {
            $this->setFlash('error', "No s'ha desat la mesura. Revisa: " . implode('; ', $errors) . '.');
            $this->redirect($redirect);
        }
        if (!MesuraPiscina::teAlgunValor($valors)) {
            $this->setFlash('error', 'Escriu almenys un paràmetre per desar la mesura.');
            $this->redirect($redirect);
        }
        $observacions = trim((string)$this->post('observacions', '')) ?: null;

        MesuraPiscina::desar($instalacioId, $tascaPlaId, $data, $moment, $this->currentUserId(), $valors, $observacions);

        // La primera mesura del dia (sigui del moment que sigui) deixa la tasca feta.
        // La tasca continua a la Vista Diària la resta del dia per a les altres mesures.
        $pendent = !empty($tasca['data_propera_realitzacio']) && $tasca['data_propera_realitzacio'] <= $data;
        if ($pendent) {
            RegistreTasca::registrar(
                $instalacioId,
                $tascaPlaId,
                $this->currentUserId(),
                TascaPla::dataExecucioProgramada($tascaPlaId, $data),
                true,
                "Paràmetres de l'aigua registrats."
            );
        }

        $estats = MesuraPiscina::avaluar($valors);
        $tancar = MesuraPiscina::nomsAmbEstat($estats, MesuraPiscina::TANCAR);
        $fora = MesuraPiscina::nomsAmbEstat($estats, MesuraPiscina::FORA);
        $desat = 'Paràmetres desats (' . MesuraPiscina::MOMENTS[$moment] . ', ' . trim((string)$cataleg['nom']) . ').';

        if ($tancar !== []) {
            $this->setFlash('error', $desat . ' ATENCIÓ: cal tancar el vas (' . implode(', ', $tancar) . ').'
                . ($fora !== [] ? ' També fora de norma: ' . implode(', ', $fora) . '.' : '')
                . " Anota l'actuació a les observacions.");
        } elseif ($fora !== []) {
            $this->setFlash('warning', $desat . ' Fora de norma: ' . implode(', ', $fora) . ". Anota l'actuació a les observacions.");
        } else {
            $this->setFlash('success', $desat);
        }

        $this->redirect($redirect);
    }

    public function index(): void
    {
        $this->requireAuth();
        $instalacioId = $this->instalacioOrRedirect();
        [$desde, $fins, $tascaPlaId] = $this->filtres();

        $this->view('parametres.index', [
            'title' => 'Paràmetres piscina',
            'mesures' => MesuraPiscina::historial($instalacioId, $desde, $fins, $tascaPlaId),
            'vasos' => MesuraPiscina::tasquesDeMesura($instalacioId),
            'desde' => $desde,
            'fins' => $fins,
            'vasActual' => $tascaPlaId,
            'isTecnic' => $this->esTecnic(),
            'flash' => $this->getFlash(),
        ]);
    }

    /** Excel per a inspeccions: les mateixes mesures que la pantalla amb els mateixos filtres, un full per vas. */
    public function excel(): void
    {
        $this->requireAuth();
        $instalacioId = $this->instalacioOrRedirect();
        [$desde, $fins, $tascaPlaId] = $this->filtres();

        $files = MesuraPiscina::agrupar(MesuraPiscina::historial($instalacioId, $desde, $fins, $tascaPlaId));
        $instalacio = Instalacio::find($instalacioId);
        $spreadsheet = ParametresExcel::build($files, [
            'instalacio' => $instalacio['nom'] ?? '',
            'desde' => $desde,
            'fins' => $fins,
        ], !$this->esTecnic());

        $nom = 'parametres-piscina-' . $desde . '-a-' . $fins;
        if ($tascaPlaId && $files) {
            $nom .= '-' . (trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(strtr($files[0]['vas'], ['à' => 'a', 'á' => 'a', 'è' => 'e', 'é' => 'e', 'í' => 'i', 'ï' => 'i', 'ò' => 'o', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n', 'À' => 'a', 'È' => 'e', 'É' => 'e', 'Í' => 'i', 'Ò' => 'o', 'Ó' => 'o', 'Ú' => 'u', 'Ç' => 'c', 'Ñ' => 'n', '·' => '']))), '-') ?: 'vas');
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nom . '.xlsx"');
        header('Cache-Control: no-store');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    private function instalacioOrRedirect(): int
    {
        $instalacioId = $this->currentInstalacioId();
        if (!$instalacioId) {
            $this->setFlash('error', 'Selecciona una instal·lació.');
            $this->redirect('dashboard');
        }

        return (int)$instalacioId;
    }

    /** Filtres de la pantalla: [desde, fins, tasca_pla_id del vas o null]. Per defecte, els últims 30 dies. */
    private function filtres(): array
    {
        $valida = static function (string $d): ?string {
            $dt = \DateTime::createFromFormat('Y-m-d', $d);
            return $dt && $dt->format('Y-m-d') === $d ? $d : null;
        };
        $fins = $valida((string)$this->get('fins', '')) ?? date('Y-m-d');
        $desde = $valida((string)$this->get('desde', '')) ?? date('Y-m-d', strtotime($fins . ' -30 days'));
        if ($desde > $fins) {
            [$desde, $fins] = [$fins, $desde];
        }

        return [$desde, $fins, (int)$this->get('vas', 0) ?: null];
    }

    private function esTecnic(): bool
    {
        return empty($_SESSION['is_superadmin']) && $this->currentRole() === 'tecnic';
    }
}
