<?php

namespace App\Models;

use App\Core\Model;

class TascaPla extends Model
{
    protected static string $table = 'tasques_pla';

    public static function create(array $data): int
    {
        $id = parent::create($data);
        if (!empty($data['torn_id'])) {
            static::syncTorns($id, [(int)$data['torn_id']], (int)$data['instalacio_id']);
        }
        return $id;
    }

    public static function tornIds(int $tascaPlaId): array
    {
        $rows = static::query(
            'SELECT torn_id FROM tasca_pla_torn WHERE tasca_pla_id = ? ORDER BY id ASC',
            [$tascaPlaId]
        );
        if (!empty($rows)) {
            return array_values(array_map(static fn(array $row): int => (int)$row['torn_id'], $rows));
        }

        $tasca = static::find($tascaPlaId);
        return !empty($tasca['torn_id']) ? [(int)$tasca['torn_id']] : [];
    }

    public static function syncTorns(int $tascaPlaId, array $tornIds, int $instalacioId): void
    {
        $tornIds = array_values(array_unique(array_filter(array_map('intval', $tornIds))));
        if (!empty($tornIds)) {
            $placeholders = implode(',', array_fill(0, count($tornIds), '?'));
            $validRows = static::query(
                "SELECT id FROM torns WHERE instalacio_id = ? AND id IN ({$placeholders})",
                array_merge([$instalacioId], $tornIds)
            );
            $validIds = array_map(static fn(array $row): int => (int)$row['id'], $validRows);
            sort($tornIds);
            sort($validIds);
            if ($tornIds !== $validIds) {
                throw new \InvalidArgumentException('Hi ha torns que no pertanyen a la instal·lació.');
            }
        }

        static::execute('DELETE FROM tasca_pla_torn WHERE tasca_pla_id = ?', [$tascaPlaId]);
        foreach ($tornIds as $tornId) {
            static::execute(
                'INSERT INTO tasca_pla_torn (tasca_pla_id, torn_id) VALUES (?, ?)',
                [$tascaPlaId, $tornId]
            );
        }

        // Compatibilitat amb consultes/importacions antigues: el primer torn
        // continua disponible a la columna original.
        static::update($tascaPlaId, ['torn_id' => $tornIds[0] ?? null]);
    }

    public static function tornNamesSql(string $taskAlias = 'tp'): string
    {
        return "COALESCE(NULLIF((
                    SELECT GROUP_CONCAT(t_multi.nom ORDER BY t_multi.nom SEPARATOR ' · ')
                    FROM tasca_pla_torn tpt_multi
                    JOIN torns t_multi ON t_multi.id = tpt_multi.torn_id
                    WHERE tpt_multi.tasca_pla_id = {$taskAlias}.id
                ), ''), t.nom)";
    }

    public static function allByInstalacio(
        int $instalacioId,
        string $orderBy = 'data_propera_realitzacio ASC',
        int|array|null $torn = null
    ): array
    {
        $params = [$instalacioId];
        $tornClause = static::tornFilterClause($torn, $params);

        return static::query('
            SELECT tp.*, COALESCE(NULLIF(tp.codi, \'\'), tc.codi) AS tasca_codi, tc.nom AS tasca_nom,
                   eq.nom_mn AS equip_nom, es.nom AS espai_nom,
                   es.actiu AS espai_actiu,
                   ' . static::tornNamesSql() . ' AS torn_nom, p.nom AS periodicitat_nom,
                   n.nom AS normativa_nom
            FROM tasques_pla tp
            JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
            LEFT JOIN equips eq ON eq.id = tp.equip_id
            LEFT JOIN espais es ON es.id = tp.espai_id
            LEFT JOIN torns t ON t.id = tp.torn_id
            LEFT JOIN periodicitats p ON p.id = tp.periodicitat_id
            LEFT JOIN normatives n ON n.id = tp.normativa_id
            WHERE tp.instalacio_id = ? AND tp.en_curs = 1' . $tornClause . '
            ORDER BY ' . $orderBy,
            $params
        );
    }

    public static function searchByInstalacio(
        int $instalacioId,
        string $search = '',
        string $orderBy = 'data_propera_realitzacio ASC',
        int|array|null $torn = null
    ): array
    {
        $sql = '
            SELECT tp.*, COALESCE(NULLIF(tp.codi, \'\'), tc.codi) AS tasca_codi, tc.nom AS tasca_nom,
                   eq.nom_mn AS equip_nom, es.nom AS espai_nom,
                   es.actiu AS espai_actiu,
                   ' . static::tornNamesSql() . ' AS torn_nom, p.nom AS periodicitat_nom,
                   n.nom AS normativa_nom
            FROM tasques_pla tp
            JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
            LEFT JOIN equips eq ON eq.id = tp.equip_id
            LEFT JOIN espais es ON es.id = tp.espai_id
            LEFT JOIN torns t ON t.id = tp.torn_id
            LEFT JOIN periodicitats p ON p.id = tp.periodicitat_id
            LEFT JOIN normatives n ON n.id = tp.normativa_id
            WHERE tp.instalacio_id = ? AND tp.en_curs = 1';
        $params = [$instalacioId];
        $sql .= static::tornFilterClause($torn, $params);

        if ($search !== '') {
            $sql .= ' AND (
                tp.codi LIKE ?
                OR tc.codi LIKE ?
                OR tc.nom LIKE ?
                OR eq.nom_mn LIKE ?
                OR es.nom LIKE ?
                OR t.nom LIKE ?
                OR EXISTS (
                    SELECT 1 FROM tasca_pla_torn tpt_search
                    JOIN torns t_search ON t_search.id = tpt_search.torn_id
                    WHERE tpt_search.tasca_pla_id = tp.id AND t_search.nom LIKE ?
                )
                OR p.nom LIKE ?
            )';
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like, $like, $like, $like, $like]);
        }

        $sql .= ' ORDER BY ' . $orderBy;

        return static::query($sql, $params);
    }

    /**
     * Construeix la condició de filtre per torn.
     * - int: només aquest torn (filtre explícit).
     * - array d'ids: qualsevol d'aquests torns (cas tècnic: només els seus torns).
     * - array buit: cap tasca (filtre segur per a tècnics sense torn).
     * - null: sense filtre.
     */
    private static function tornFilterClause(int|array|null $torn, array &$params): string
    {
        if (is_int($torn) && $torn > 0) {
            array_push($params, $torn, $torn);
            return ' AND (
                EXISTS (SELECT 1 FROM tasca_pla_torn tpt_filter WHERE tpt_filter.tasca_pla_id = tp.id AND tpt_filter.torn_id = ?)
                OR (NOT EXISTS (SELECT 1 FROM tasca_pla_torn tpt_any WHERE tpt_any.tasca_pla_id = tp.id) AND tp.torn_id = ?)
            )';
        }

        if (is_array($torn)) {
            $ids = array_values(array_filter(array_map('intval', $torn)));
            if (empty($ids)) {
                return ' AND 1 = 0';
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge($params, $ids, $ids);
            return " AND (
                EXISTS (SELECT 1 FROM tasca_pla_torn tpt_filter WHERE tpt_filter.tasca_pla_id = tp.id AND tpt_filter.torn_id IN ({$placeholders}))
                OR (NOT EXISTS (SELECT 1 FROM tasca_pla_torn tpt_any WHERE tpt_any.tasca_pla_id = tp.id)
                    AND tp.torn_id IN ({$placeholders}))
            )";
        }

        return '';
    }

    public static function getSetmana(int $instalacioId, string $dilluns, string $diumenge, ?int $tornId = null): array
    {
        $sql = '
            SELECT tp.*, COALESCE(NULLIF(tp.codi, \'\'), tc.codi) AS tasca_codi, tc.nom AS tasca_nom,
                   eq.nom_mn AS equip_nom, es.nom AS espai_nom,
                   ' . static::tornNamesSql() . ' AS torn_nom, p.nom AS periodicitat_nom,
                   p.dies_interval
            FROM tasques_pla tp
            JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
            LEFT JOIN equips eq ON eq.id = tp.equip_id
            LEFT JOIN espais es ON es.id = tp.espai_id
            LEFT JOIN torns t ON t.id = tp.torn_id
            LEFT JOIN periodicitats p ON p.id = tp.periodicitat_id
            WHERE tp.instalacio_id = ? AND tp.en_curs = 1
              AND (tp.espai_id IS NULL OR es.actiu = 1)
              AND tp.data_propera_realitzacio IS NOT NULL
              AND tp.data_propera_realitzacio <= ?';
        $params = [$instalacioId, $diumenge];

        $sql .= static::tornFilterClause($tornId, $params);

        $sql .= " ORDER BY tp.data_propera_realitzacio ASC, es.nom ASC, COALESCE(NULLIF(tp.codi, ''), tc.codi) ASC";

        return static::query($sql, $params);
    }

    public static function getDia(int $instalacioId, string $data, int|array|null $torn = null, string $search = ''): array
    {
        $sql = '
            SELECT tp.*, COALESCE(NULLIF(tp.codi, \'\'), tc.codi) AS tasca_codi, tc.nom AS tasca_nom,
                   eq.nom_mn AS equip_nom, es.nom AS espai_nom,
                   ' . static::tornNamesSql() . ' AS torn_nom, p.nom AS periodicitat_nom,
                   p.dies_interval
            FROM tasques_pla tp
            JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
            LEFT JOIN equips eq ON eq.id = tp.equip_id
            LEFT JOIN espais es ON es.id = tp.espai_id
            LEFT JOIN torns t ON t.id = tp.torn_id
            LEFT JOIN periodicitats p ON p.id = tp.periodicitat_id
            WHERE tp.instalacio_id = ? AND tp.en_curs = 1
              AND (tp.espai_id IS NULL OR es.actiu = 1)
              AND tp.data_propera_realitzacio IS NOT NULL
              AND tp.data_propera_realitzacio <= ?';
        $params = [$instalacioId, $data];

        $sql .= static::tornFilterClause($torn, $params);

        if ($search !== '') {
            $sql .= ' AND (
                tp.codi LIKE ?
                OR tc.codi LIKE ?
                OR tc.nom LIKE ?
                OR eq.nom_mn LIKE ?
                OR es.nom LIKE ?
            )';
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like, $like]);
        }

        $sql .= " ORDER BY tp.data_propera_realitzacio ASC, es.nom ASC, COALESCE(NULLIF(tp.codi, ''), tc.codi) ASC";

        return static::query($sql, $params);
    }

    public static function getSetmanaSearch(int $instalacioId, string $dilluns, string $diumenge, int|array|null $torn = null, string $search = ''): array
    {
        $sql = '
            SELECT tp.*, COALESCE(NULLIF(tp.codi, \'\'), tc.codi) AS tasca_codi, tc.nom AS tasca_nom,
                   eq.nom_mn AS equip_nom, es.nom AS espai_nom,
                   ' . static::tornNamesSql() . ' AS torn_nom, p.nom AS periodicitat_nom,
                   p.dies_interval
            FROM tasques_pla tp
            JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
            LEFT JOIN equips eq ON eq.id = tp.equip_id
            LEFT JOIN espais es ON es.id = tp.espai_id
            LEFT JOIN torns t ON t.id = tp.torn_id
            LEFT JOIN periodicitats p ON p.id = tp.periodicitat_id
            WHERE tp.instalacio_id = ? AND tp.en_curs = 1
              AND (tp.espai_id IS NULL OR es.actiu = 1)
              AND tp.data_propera_realitzacio IS NOT NULL
              AND tp.data_propera_realitzacio <= ?';
        $params = [$instalacioId, $diumenge];

        $sql .= static::tornFilterClause($torn, $params);

        if ($search !== '') {
            $sql .= ' AND (
                tp.codi LIKE ?
                OR tc.codi LIKE ?
                OR tc.nom LIKE ?
                OR eq.nom_mn LIKE ?
                OR es.nom LIKE ?
            )';
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like, $like]);
        }

        $sql .= " ORDER BY tp.data_propera_realitzacio ASC, es.nom ASC, COALESCE(NULLIF(tp.codi, ''), tc.codi) ASC";

        return static::query($sql, $params);
    }

    public static function tasquesPendents(int $instalacioId, int|array|null $torn = null): int
    {
        $params = [$instalacioId];
        $tornClause = static::tornFilterClause($torn, $params);
        $result = static::query(
            'SELECT COUNT(*) AS total
             FROM tasques_pla tp
             LEFT JOIN espais es ON es.id = tp.espai_id
             WHERE tp.instalacio_id = ? AND tp.en_curs = 1
               AND (tp.espai_id IS NULL OR es.actiu = 1)
               AND tp.data_propera_realitzacio <= CURDATE()' . $tornClause,
            $params
        );
        return (int)($result[0]['total'] ?? 0);
    }

    public static function properesByInstalacio(
        int $instalacioId,
        int|array|null $torn = null,
        int $limit = 15
    ): array
    {
        $params = [$instalacioId];
        $tornClause = static::tornFilterClause($torn, $params);
        $limit = max(1, min(100, $limit));

        return static::query('
            SELECT tp.id, tp.data_propera_realitzacio,
                   COALESCE(NULLIF(tp.codi, \'\'), tc.codi) AS tasca_codi,
                   tc.nom AS tasca_nom, es.nom AS espai_nom,
                   ' . static::tornNamesSql() . ' AS torn_nom
            FROM tasques_pla tp
            JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
            LEFT JOIN espais es ON es.id = tp.espai_id
            LEFT JOIN torns t ON t.id = tp.torn_id
            WHERE tp.instalacio_id = ? AND tp.en_curs = 1
              AND (tp.espai_id IS NULL OR es.actiu = 1)
              AND tp.data_propera_realitzacio IS NOT NULL' . $tornClause . '
            ORDER BY tp.data_propera_realitzacio ASC, tc.nom ASC
            LIMIT ' . $limit,
            $params
        );
    }

    public static function tasquesVençudes(int $instalacioId, int|array|null $torn = null): int
    {
        $params = [$instalacioId];
        $tornClause = static::tornFilterClause($torn, $params);
        $result = static::query(
            'SELECT COUNT(*) AS total
             FROM tasques_pla tp
             LEFT JOIN espais es ON es.id = tp.espai_id
             WHERE tp.instalacio_id = ? AND tp.en_curs = 1
               AND (tp.espai_id IS NULL OR es.actiu = 1)
               AND tp.data_propera_realitzacio < CURDATE()' . $tornClause,
            $params
        );
        return (int)($result[0]['total'] ?? 0);
    }

    public static function grauAcomplimentActual(int $instalacioId): float
    {
        $result = static::query(
            'SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN tp.data_propera_realitzacio <= CURDATE() THEN 1 ELSE 0 END) AS pendents
             FROM tasques_pla tp
             LEFT JOIN espais es ON es.id = tp.espai_id
             WHERE tp.instalacio_id = ? AND tp.en_curs = 1
               AND (tp.espai_id IS NULL OR es.actiu = 1)',
            [$instalacioId]
        );

        $total = (int)($result[0]['total'] ?? 0);
        $pendents = (int)($result[0]['pendents'] ?? 0);

        return $total > 0 ? round((($total - $pendents) / $total) * 100, 2) : 0;
    }

    /**
     * Assigna la mateixa data de propera realització a diverses tasques del pla.
     * El filtre per instal·lació va dins de l'UPDATE: mai es toca una tasca d'una altra instal·lació.
     * Retorna quantes tasques de la llista pertanyen realment a la instal·lació.
     */
    public static function programarPropera(array $ids, string $data, int $instalacioId): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        static::execute(
            'UPDATE tasques_pla SET data_propera_realitzacio = ?
             WHERE instalacio_id = ? AND id IN (' . $placeholders . ')',
            array_merge([$data, $instalacioId], $ids)
        );

        $result = static::query(
            'SELECT COUNT(*) AS total FROM tasques_pla
             WHERE instalacio_id = ? AND id IN (' . $placeholders . ')',
            array_merge([$instalacioId], $ids)
        );

        return (int)($result[0]['total'] ?? 0);
    }

    public static function recalcularPropera(int $id): void
    {
        static::execute('
            UPDATE tasques_pla tp
            JOIN periodicitats p ON p.id = tp.periodicitat_id
            SET tp.data_propera_realitzacio = DATE_ADD(tp.data_darrera_realitzacio, INTERVAL p.dies_interval DAY)
            WHERE tp.id = ? AND tp.data_darrera_realitzacio IS NOT NULL
        ', [$id]);
    }

    public static function dataExecucioProgramada(int $id, string $dataAccio): string
    {
        $result = static::query('
            SELECT tp.data_propera_realitzacio, p.dies_interval
            FROM tasques_pla tp
            LEFT JOIN periodicitats p ON p.id = tp.periodicitat_id
            WHERE tp.id = ?
            LIMIT 1
        ', [$id]);

        $dataProgramada = $result[0]['data_propera_realitzacio'] ?? null;
        $interval = (int)($result[0]['dies_interval'] ?? 0);

        if (!$dataProgramada || $interval <= 0) {
            return $dataAccio;
        }

        $programada = new \DateTimeImmutable($dataProgramada);
        $accio = new \DateTimeImmutable($dataAccio);

        if ($accio < $programada) {
            return $dataProgramada;
        }

        $diesRetard = (int)$programada->diff($accio)->format('%a');
        $intervalsPassats = intdiv($diesRetard, $interval);

        return $programada
            ->modify('+' . ($intervalsPassats * $interval) . ' days')
            ->format('Y-m-d');
    }
}
