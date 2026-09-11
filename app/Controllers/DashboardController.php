<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Database;
use App\Models\Equip;
use App\Models\TascaPla;
use App\Models\RegistreTasca;
use App\Models\Torn;

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

        // Admin d'instal·lació buida: portar-lo a l'onboarding guiat
        if (
            $instalacioId
            && empty($_SESSION['is_superadmin'])
            && $this->currentRole() === 'admin_instalacio'
            && TascaPla::count(['instalacio_id' => $instalacioId]) === 0
        ) {
            $this->redirect('instalacions/onboarding/' . $instalacioId);
        }

        if (empty($_SESSION['is_superadmin']) && $this->currentRole() === 'tecnic') {
            $this->tecnicDashboard($instalacioId);
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
            $stats['grau_acompliment'] = TascaPla::grauAcomplimentActual($instalacioId);

            $r = RegistreTasca::query(
                'SELECT COUNT(*) AS total FROM registre_tasques WHERE instalacio_id = ? AND data_execucio >= ?',
                [$instalacioId, $primerDiaMes]
            );
            $stats['registres_mes'] = (int)($r[0]['total'] ?? 0);

            $stats['properes_tasques'] = TascaPla::query('
                SELECT tp.id, tp.data_propera_realitzacio, COALESCE(NULLIF(tp.codi, \'\'), tc.codi) AS tasca_codi, tc.nom AS tasca_nom,
                       es.nom AS espai_nom, ' . TascaPla::tornNamesSql() . ' AS torn_nom
                FROM tasques_pla tp
                JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
                LEFT JOIN espais es ON es.id = tp.espai_id
                LEFT JOIN torns t ON t.id = tp.torn_id
                WHERE tp.instalacio_id = ? AND tp.en_curs = 1
                  AND (tp.espai_id IS NULL OR es.actiu = 1)
                  AND tp.data_propera_realitzacio IS NOT NULL
                ORDER BY tp.data_propera_realitzacio ASC
                LIMIT 15
            ', [$instalacioId]);

            $stats['tasques_per_torn'] = TascaPla::query('
                SELECT t.nom AS torn_nom, COUNT(DISTINCT tp.id) AS total,
                       SUM(CASE WHEN tp.data_propera_realitzacio < CURDATE() THEN 1 ELSE 0 END) AS vencudes
                FROM tasques_pla tp
                LEFT JOIN espais es ON es.id = tp.espai_id
                LEFT JOIN tasca_pla_torn tpt ON tpt.tasca_pla_id = tp.id
                LEFT JOIN torns t ON t.id = COALESCE(tpt.torn_id, tp.torn_id)
                WHERE tp.instalacio_id = ? AND tp.en_curs = 1
                  AND (tp.espai_id IS NULL OR es.actiu = 1)
                GROUP BY t.id, t.nom
                ORDER BY t.nom
            ', [$instalacioId]);

            $stats['tasques_per_sistema'] = TascaPla::query('
                SELECT s.codi AS sistema_codi, s.nom AS sistema_nom, COUNT(*) AS total
                FROM tasques_pla tp
                JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
                LEFT JOIN espais es ON es.id = tp.espai_id
                LEFT JOIN sistemes s ON s.id = tc.sistema_id
                WHERE tp.instalacio_id = ? AND tp.en_curs = 1
                  AND (tp.espai_id IS NULL OR es.actiu = 1)
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

    private function tecnicDashboard(?int $instalacioId): void
    {
        $stats = [
            'pendents_avui' => 0,
            'vencudes' => 0,
            'fetes_avui' => 0,
            'fetes_mes' => 0,
            'properes_tasques' => [],
        ];
        $tornsAssignats = [];
        $senseTornsAssignats = false;

        if ($instalacioId) {
            $tornIds = Torn::tornIdsByUsuariInstalacio($this->currentUserId(), $instalacioId);
            $senseTornsAssignats = empty($tornIds);

            if (!$senseTornsAssignats) {
                $tornsAssignats = array_values(array_filter(
                    Torn::allByInstalacio($instalacioId),
                    static fn(array $torn): bool => in_array((int)$torn['id'], $tornIds, true)
                ));
                $stats['pendents_avui'] = TascaPla::tasquesPendents($instalacioId, $tornIds);
                $stats['vencudes'] = TascaPla::tasquesVençudes($instalacioId, $tornIds);
                $stats['properes_tasques'] = TascaPla::properesByInstalacio($instalacioId, $tornIds);

                $primerDiaMes = date('Y-m-01');
                $avui = date('Y-m-d');
                $resum = RegistreTasca::query(
                    'SELECT
                        SUM(CASE WHEN data_execucio = ? THEN 1 ELSE 0 END) AS fetes_avui,
                        COUNT(*) AS fetes_mes
                     FROM registre_tasques
                     WHERE instalacio_id = ? AND usuari_id = ? AND realitzada = 1
                       AND data_execucio BETWEEN ? AND ?',
                    [$avui, $instalacioId, $this->currentUserId(), $primerDiaMes, $avui]
                );
                $stats['fetes_avui'] = (int)($resum[0]['fetes_avui'] ?? 0);
                $stats['fetes_mes'] = (int)($resum[0]['fetes_mes'] ?? 0);
            }
        }

        $this->view('dashboard.tecnic', [
            'title' => 'La meva jornada',
            'flash' => $this->getFlash(),
            'stats' => $stats,
            'instalacioId' => $instalacioId,
            'tornsAssignats' => $tornsAssignats,
            'senseTornsAssignats' => $senseTornsAssignats,
        ]);
    }

    private function globalDashboard(): void
    {
        $db = Database::getInstance();

        $instalacions = $db->query('
            SELECT i.id, i.nom, i.adreca,
                   (SELECT COUNT(*) FROM equips e WHERE e.instalacio_id = i.id AND e.actiu = 1) AS equips,
                   (SELECT COUNT(*) FROM tasques_pla tp WHERE tp.instalacio_id = i.id AND tp.en_curs = 1) AS tasques_pla,
                   (SELECT COUNT(*)
                    FROM tasques_pla tp
                    LEFT JOIN espais es ON es.id = tp.espai_id
                    WHERE tp.instalacio_id = i.id
                      AND tp.en_curs = 1
                      AND (tp.espai_id IS NULL OR es.actiu = 1)
                      AND tp.data_propera_realitzacio < CURDATE()) AS tasques_vencudes,
                   (SELECT COUNT(*)
                    FROM tasques_pla tp
                    LEFT JOIN espais es ON es.id = tp.espai_id
                    WHERE tp.instalacio_id = i.id
                      AND tp.en_curs = 1
                      AND (tp.espai_id IS NULL OR es.actiu = 1)
                      AND tp.data_propera_realitzacio <= CURDATE()) AS tasques_pendents,
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
