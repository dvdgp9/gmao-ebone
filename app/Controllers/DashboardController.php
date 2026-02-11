<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Database;
use App\Models\Equip;
use App\Models\TascaPla;
use App\Models\RegistreTasca;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $instalacioId = $this->currentInstalacioId();

        // Superadmin sense instal·lació seleccionada: vista global
        if (!$instalacioId && !empty($_SESSION['is_superadmin'])) {
            $this->globalDashboard();
            return;
        }

        $stats = [
            'equips_actius' => 0,
            'tasques_pla' => 0,
            'tasques_pendents' => 0,
            'tasques_vencudes' => 0,
            'grau_acompliment' => 0,
            'registres_mes' => 0,
            'properes_tasques' => [],
            'tasques_per_torn' => [],
            'tasques_per_sistema' => [],
        ];

        if ($instalacioId) {
            $db = Database::getInstance();

            $stats['equips_actius'] = Equip::countByInstalacio($instalacioId);
            $stats['tasques_pendents'] = TascaPla::tasquesPendents($instalacioId);
            $stats['tasques_vencudes'] = TascaPla::tasquesVençudes($instalacioId);

            $r = TascaPla::query(
                'SELECT COUNT(*) AS total FROM tasques_pla WHERE instalacio_id = ? AND en_curs = 1',
                [$instalacioId]
            );
            $stats['tasques_pla'] = (int)($r[0]['total'] ?? 0);

            $primerDiaMes = date('Y-m-01');
            $stats['grau_acompliment'] = RegistreTasca::grauAcompliment($instalacioId, $primerDiaMes);

            $r = RegistreTasca::query(
                'SELECT COUNT(*) AS total FROM registre_tasques WHERE instalacio_id = ? AND data_execucio >= ?',
                [$instalacioId, $primerDiaMes]
            );
            $stats['registres_mes'] = (int)($r[0]['total'] ?? 0);

            $stats['properes_tasques'] = TascaPla::query('
                SELECT tp.id, tp.data_propera_realitzacio, tc.codi AS tasca_codi, tc.nom AS tasca_nom,
                       es.nom AS espai_nom, t.nom AS torn_nom
                FROM tasques_pla tp
                JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
                LEFT JOIN espais es ON es.id = tp.espai_id
                LEFT JOIN torns t ON t.id = tp.torn_id
                WHERE tp.instalacio_id = ? AND tp.en_curs = 1
                  AND tp.data_propera_realitzacio IS NOT NULL
                ORDER BY tp.data_propera_realitzacio ASC
                LIMIT 10
            ', [$instalacioId]);

            $stats['tasques_per_torn'] = TascaPla::query('
                SELECT t.nom AS torn_nom, COUNT(*) AS total,
                       SUM(CASE WHEN tp.data_propera_realitzacio < CURDATE() THEN 1 ELSE 0 END) AS vencudes
                FROM tasques_pla tp
                LEFT JOIN torns t ON t.id = tp.torn_id
                WHERE tp.instalacio_id = ? AND tp.en_curs = 1
                GROUP BY tp.torn_id, t.nom
                ORDER BY t.nom
            ', [$instalacioId]);

            $stats['tasques_per_sistema'] = TascaPla::query('
                SELECT s.codi AS sistema_codi, s.nom AS sistema_nom, COUNT(*) AS total
                FROM tasques_pla tp
                JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
                LEFT JOIN sistemes s ON s.id = tc.sistema_id
                WHERE tp.instalacio_id = ? AND tp.en_curs = 1
                GROUP BY tc.sistema_id, s.codi, s.nom
                ORDER BY total DESC
                LIMIT 10
            ', [$instalacioId]);
        }

        $this->view('dashboard.index', [
            'flash' => $this->getFlash(),
            'stats' => $stats,
            'instalacioId' => $instalacioId,
        ]);
    }

    private function globalDashboard(): void
    {
        $db = Database::getInstance();

        $instalacions = $db->query('
            SELECT i.id, i.nom, i.adreca,
                   (SELECT COUNT(*) FROM equips e WHERE e.instalacio_id = i.id AND e.actiu = 1) AS equips,
                   (SELECT COUNT(*) FROM tasques_pla tp WHERE tp.instalacio_id = i.id AND tp.en_curs = 1) AS tasques_pla,
                   (SELECT COUNT(*) FROM tasques_pla tp WHERE tp.instalacio_id = i.id AND tp.en_curs = 1 AND tp.data_propera_realitzacio < CURDATE()) AS tasques_vencudes,
                   (SELECT COUNT(*) FROM tasques_pla tp WHERE tp.instalacio_id = i.id AND tp.en_curs = 1 AND tp.data_propera_realitzacio <= CURDATE()) AS tasques_pendents,
                   (SELECT COUNT(*) FROM espais es WHERE es.instalacio_id = i.id AND es.actiu = 1) AS espais
            FROM instalacions i
            WHERE i.activa = 1
            ORDER BY i.nom
        ')->fetchAll();

        $totals = [
            'instalacions' => count($instalacions),
            'equips' => array_sum(array_column($instalacions, 'equips')),
            'tasques_pla' => array_sum(array_column($instalacions, 'tasques_pla')),
            'tasques_vencudes' => array_sum(array_column($instalacions, 'tasques_vencudes')),
        ];

        $this->view('dashboard.global', [
            'flash' => $this->getFlash(),
            'instalacions' => $instalacions,
            'totals' => $totals,
        ]);
    }
}
