<?php

namespace App\Models;

use App\Core\Model;

class RegistreTasca extends Model
{
    protected static string $table = 'registre_tasques';

    public static function allByInstalacio(int $instalacioId, int $limit = 50, int $offset = 0): array
    {
        return static::query('
            SELECT rt.*, COALESCE(NULLIF(tp.codi, \'\'), tc.codi) AS tasca_codi, tc.nom AS tasca_nom,
                   es.nom AS espai_nom, u.nom AS usuari_nom,
                   ' . TascaPla::tornNamesSql() . ' AS torn_nom
            FROM registre_tasques rt
            JOIN tasques_pla tp ON tp.id = rt.tasca_pla_id
            JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
            LEFT JOIN espais es ON es.id = tp.espai_id
            LEFT JOIN usuaris u ON u.id = rt.usuari_id
            LEFT JOIN torns t ON t.id = tp.torn_id
            WHERE rt.instalacio_id = ?
            ORDER BY rt.data_execucio DESC, rt.created_at DESC
            LIMIT ' . $limit . ' OFFSET ' . $offset,
            [$instalacioId]
        );
    }

    public static function filterByInstalacio(int $instalacioId, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        [$where, $params] = static::buildFilterWhere($instalacioId, $filters);

        return static::query('
            SELECT rt.*, COALESCE(NULLIF(tp.codi, \'\'), tc.codi) AS tasca_codi, tc.nom AS tasca_nom,
                   es.nom AS espai_nom, u.nom AS usuari_nom,
                   ' . TascaPla::tornNamesSql() . ' AS torn_nom
            FROM registre_tasques rt
            JOIN tasques_pla tp ON tp.id = rt.tasca_pla_id
            JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
            LEFT JOIN espais es ON es.id = tp.espai_id
            LEFT JOIN usuaris u ON u.id = rt.usuari_id
            LEFT JOIN torns t ON t.id = tp.torn_id
            WHERE ' . $where . '
            ORDER BY rt.data_execucio DESC, rt.created_at DESC
            LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );
    }

    public static function registrar(int $instalacioId, int $tascaPlaId, ?int $usuariId, string $dataExecucio, bool $realitzada, ?string $comentaris): int
    {
        $id = static::create([
            'instalacio_id' => $instalacioId,
            'tasca_pla_id' => $tascaPlaId,
            'usuari_id' => $usuariId,
            'data_execucio' => $dataExecucio,
            'realitzada' => $realitzada ? 1 : 0,
            'comentaris' => $comentaris,
        ]);

        if ($realitzada) {
            TascaPla::update($tascaPlaId, [
                'data_darrera_realitzacio' => $dataExecucio,
            ]);
            TascaPla::recalcularPropera($tascaPlaId);
        } else {
            TascaPla::update($tascaPlaId, [
                'data_darrera_no_realitzacio' => $dataExecucio,
            ]);
        }

        return $id;
    }

    public static function countByInstalacio(int $instalacioId): int
    {
        $result = static::query(
            'SELECT COUNT(*) AS total FROM registre_tasques WHERE instalacio_id = ?',
            [$instalacioId]
        );
        return (int)($result[0]['total'] ?? 0);
    }

    public static function countFilteredByInstalacio(int $instalacioId, array $filters = []): int
    {
        [$where, $params] = static::buildFilterWhere($instalacioId, $filters);

        $result = static::query('
            SELECT COUNT(*) AS total
            FROM registre_tasques rt
            JOIN tasques_pla tp ON tp.id = rt.tasca_pla_id
            JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
            LEFT JOIN espais es ON es.id = tp.espai_id
            LEFT JOIN usuaris u ON u.id = rt.usuari_id
            LEFT JOIN torns t ON t.id = tp.torn_id
            WHERE ' . $where,
            $params
        );

        return (int)($result[0]['total'] ?? 0);
    }

    public static function filterOptionsByInstalacio(int $instalacioId, ?int $usuariId = null): array
    {
        $scopeSql = 'rt.instalacio_id = ?';
        $params = [$instalacioId];
        if ($usuariId !== null) {
            $scopeSql .= ' AND rt.usuari_id = ?';
            $params[] = $usuariId;
        }

        return [
            'tasques' => static::query('
                SELECT DISTINCT tp.id, COALESCE(NULLIF(tp.codi, \'\'), tc.codi) AS tasca_codi, tc.nom AS tasca_nom
                FROM registre_tasques rt
                JOIN tasques_pla tp ON tp.id = rt.tasca_pla_id
                JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
                WHERE ' . $scopeSql . '
                ORDER BY tasca_codi ASC, tc.nom ASC
            ', $params),
            'espais' => static::query('
                SELECT DISTINCT es.id, es.nom
                FROM registre_tasques rt
                JOIN tasques_pla tp ON tp.id = rt.tasca_pla_id
                JOIN espais es ON es.id = tp.espai_id
                WHERE ' . $scopeSql . '
                ORDER BY es.nom ASC
            ', $params),
            'torns' => static::query('
                SELECT DISTINCT t.id, t.nom
                FROM registre_tasques rt
                JOIN tasques_pla tp ON tp.id = rt.tasca_pla_id
                LEFT JOIN tasca_pla_torn tpt ON tpt.tasca_pla_id = tp.id
                JOIN torns t ON t.id = COALESCE(tpt.torn_id, tp.torn_id)
                WHERE ' . $scopeSql . '
                ORDER BY t.nom ASC
            ', $params),
        ];
    }

    private static function buildFilterWhere(int $instalacioId, array $filters): array
    {
        $conditions = ['rt.instalacio_id = ?'];
        $params = [$instalacioId];

        if (!empty($filters['usuari_id'])) {
            $conditions[] = 'rt.usuari_id = ?';
            $params[] = (int)$filters['usuari_id'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = 'rt.data_execucio >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'rt.data_execucio <= ?';
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['tasca_pla_id'])) {
            $conditions[] = 'rt.tasca_pla_id = ?';
            $params[] = (int)$filters['tasca_pla_id'];
        }

        if (!empty($filters['espai_id'])) {
            $conditions[] = 'tp.espai_id = ?';
            $params[] = (int)$filters['espai_id'];
        }

        if (!empty($filters['torn_id'])) {
            $conditions[] = '(
                EXISTS (SELECT 1 FROM tasca_pla_torn tpt_filter WHERE tpt_filter.tasca_pla_id = tp.id AND tpt_filter.torn_id = ?)
                OR (NOT EXISTS (SELECT 1 FROM tasca_pla_torn tpt_any WHERE tpt_any.tasca_pla_id = tp.id) AND tp.torn_id = ?)
            )';
            $tornId = (int)$filters['torn_id'];
            array_push($params, $tornId, $tornId);
        } elseif (!empty($filters['torn_ids']) && is_array($filters['torn_ids'])) {
            $ids = array_values(array_filter(array_map('intval', $filters['torn_ids'])));
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $conditions[] = "(
                    EXISTS (SELECT 1 FROM tasca_pla_torn tpt_filter WHERE tpt_filter.tasca_pla_id = tp.id AND tpt_filter.torn_id IN ({$placeholders}))
                    OR (NOT EXISTS (SELECT 1 FROM tasca_pla_torn tpt_any WHERE tpt_any.tasca_pla_id = tp.id)
                        AND (tp.torn_id IN ({$placeholders}) OR tp.torn_id IS NULL))
                )";
                $params = array_merge($params, $ids, $ids);
            }
        }

        if (!empty($filters['q'])) {
            $conditions[] = '(
                tp.codi LIKE ?
                OR tc.codi LIKE ?
                OR tc.nom LIKE ?
                OR es.nom LIKE ?
                OR t.nom LIKE ?
                OR EXISTS (
                    SELECT 1 FROM tasca_pla_torn tpt_search
                    JOIN torns t_search ON t_search.id = tpt_search.torn_id
                    WHERE tpt_search.tasca_pla_id = tp.id AND t_search.nom LIKE ?
                )
                OR u.nom LIKE ?
                OR rt.comentaris LIKE ?
            )';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like, $like, $like, $like, $like, $like);
        }

        return [implode(' AND ', $conditions), $params];
    }

    public static function grauAcompliment(int $instalacioId, ?string $desde = null, ?string $fins = null): float
    {
        $sql = '
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN rt.realitzada = 1 THEN 1 ELSE 0 END) AS realitzades
            FROM registre_tasques rt
            WHERE rt.instalacio_id = ?';
        $params = [$instalacioId];

        if ($desde) {
            $sql .= ' AND rt.data_execucio >= ?';
            $params[] = $desde;
        }
        if ($fins) {
            $sql .= ' AND rt.data_execucio <= ?';
            $params[] = $fins;
        }

        $result = static::query($sql, $params);
        $total = (int)($result[0]['total'] ?? 0);
        $realitzades = (int)($result[0]['realitzades'] ?? 0);

        return $total > 0 ? round(($realitzades / $total) * 100, 2) : 0;
    }
}
