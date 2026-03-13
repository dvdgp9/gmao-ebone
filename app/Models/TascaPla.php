<?php

namespace App\Models;

use App\Core\Model;

class TascaPla extends Model
{
    protected static string $table = 'tasques_pla';

    public static function allByInstalacio(int $instalacioId, string $orderBy = 'data_propera_realitzacio ASC'): array
    {
        return static::query('
            SELECT tp.*, tc.codi AS tasca_codi, tc.nom AS tasca_nom,
                   eq.nom_mn AS equip_nom, es.nom AS espai_nom,
                   t.nom AS torn_nom, p.nom AS periodicitat_nom,
                   n.nom AS normativa_nom
            FROM tasques_pla tp
            JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
            LEFT JOIN equips eq ON eq.id = tp.equip_id
            LEFT JOIN espais es ON es.id = tp.espai_id
            LEFT JOIN torns t ON t.id = tp.torn_id
            LEFT JOIN periodicitats p ON p.id = tp.periodicitat_id
            LEFT JOIN normatives n ON n.id = tp.normativa_id
            WHERE tp.instalacio_id = ? AND tp.en_curs = 1
            ORDER BY ' . $orderBy,
            [$instalacioId]
        );
    }

    public static function searchByInstalacio(int $instalacioId, string $search = '', string $orderBy = 'data_propera_realitzacio ASC'): array
    {
        $sql = '
            SELECT tp.*, tc.codi AS tasca_codi, tc.nom AS tasca_nom,
                   eq.nom_mn AS equip_nom, es.nom AS espai_nom,
                   t.nom AS torn_nom, p.nom AS periodicitat_nom,
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

        if ($search !== '') {
            $sql .= ' AND (
                tc.codi LIKE ?
                OR tc.nom LIKE ?
                OR eq.nom_mn LIKE ?
                OR es.nom LIKE ?
                OR t.nom LIKE ?
            )';
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like, $like]);
        }

        $sql .= ' ORDER BY ' . $orderBy;

        return static::query($sql, $params);
    }

    public static function getSetmana(int $instalacioId, string $dilluns, string $diumenge, ?int $tornId = null): array
    {
        $sql = '
            SELECT tp.*, tc.codi AS tasca_codi, tc.nom AS tasca_nom,
                   eq.nom_mn AS equip_nom, es.nom AS espai_nom,
                   t.nom AS torn_nom, p.nom AS periodicitat_nom,
                   p.dies_interval
            FROM tasques_pla tp
            JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
            LEFT JOIN equips eq ON eq.id = tp.equip_id
            LEFT JOIN espais es ON es.id = tp.espai_id
            LEFT JOIN torns t ON t.id = tp.torn_id
            LEFT JOIN periodicitats p ON p.id = tp.periodicitat_id
            WHERE tp.instalacio_id = ? AND tp.en_curs = 1
              AND tp.data_propera_realitzacio IS NOT NULL
              AND tp.data_propera_realitzacio <= ?';
        $params = [$instalacioId, $diumenge];

        if ($tornId) {
            $sql .= ' AND tp.torn_id = ?';
            $params[] = $tornId;
        }

        $sql .= ' ORDER BY tp.data_propera_realitzacio ASC, es.nom ASC, tc.codi ASC';

        return static::query($sql, $params);
    }

    public static function getDia(int $instalacioId, string $data, ?int $tornId = null, string $search = ''): array
    {
        $sql = '
            SELECT tp.*, tc.codi AS tasca_codi, tc.nom AS tasca_nom,
                   eq.nom_mn AS equip_nom, es.nom AS espai_nom,
                   t.nom AS torn_nom, p.nom AS periodicitat_nom,
                   p.dies_interval
            FROM tasques_pla tp
            JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
            LEFT JOIN equips eq ON eq.id = tp.equip_id
            LEFT JOIN espais es ON es.id = tp.espai_id
            LEFT JOIN torns t ON t.id = tp.torn_id
            LEFT JOIN periodicitats p ON p.id = tp.periodicitat_id
            WHERE tp.instalacio_id = ? AND tp.en_curs = 1
              AND tp.data_propera_realitzacio IS NOT NULL
              AND tp.data_propera_realitzacio <= ?';
        $params = [$instalacioId, $data];

        if ($tornId) {
            $sql .= ' AND tp.torn_id = ?';
            $params[] = $tornId;
        }

        if ($search !== '') {
            $sql .= ' AND (
                tc.codi LIKE ?
                OR tc.nom LIKE ?
                OR eq.nom_mn LIKE ?
                OR es.nom LIKE ?
            )';
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        $sql .= ' ORDER BY tp.data_propera_realitzacio ASC, es.nom ASC, tc.codi ASC';

        return static::query($sql, $params);
    }

    public static function getSetmanaSearch(int $instalacioId, string $dilluns, string $diumenge, ?int $tornId = null, string $search = ''): array
    {
        $sql = '
            SELECT tp.*, tc.codi AS tasca_codi, tc.nom AS tasca_nom,
                   eq.nom_mn AS equip_nom, es.nom AS espai_nom,
                   t.nom AS torn_nom, p.nom AS periodicitat_nom,
                   p.dies_interval
            FROM tasques_pla tp
            JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
            LEFT JOIN equips eq ON eq.id = tp.equip_id
            LEFT JOIN espais es ON es.id = tp.espai_id
            LEFT JOIN torns t ON t.id = tp.torn_id
            LEFT JOIN periodicitats p ON p.id = tp.periodicitat_id
            WHERE tp.instalacio_id = ? AND tp.en_curs = 1
              AND tp.data_propera_realitzacio IS NOT NULL
              AND tp.data_propera_realitzacio <= ?';
        $params = [$instalacioId, $diumenge];

        if ($tornId) {
            $sql .= ' AND tp.torn_id = ?';
            $params[] = $tornId;
        }

        if ($search !== '') {
            $sql .= ' AND (
                tc.codi LIKE ?
                OR tc.nom LIKE ?
                OR eq.nom_mn LIKE ?
                OR es.nom LIKE ?
            )';
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        $sql .= ' ORDER BY tp.data_propera_realitzacio ASC, es.nom ASC, tc.codi ASC';

        return static::query($sql, $params);
    }

    public static function tasquesPendents(int $instalacioId): int
    {
        $result = static::query(
            'SELECT COUNT(*) AS total FROM tasques_pla
             WHERE instalacio_id = ? AND en_curs = 1 AND data_propera_realitzacio <= CURDATE()',
            [$instalacioId]
        );
        return (int)($result[0]['total'] ?? 0);
    }

    public static function tasquesVençudes(int $instalacioId): int
    {
        $result = static::query(
            'SELECT COUNT(*) AS total FROM tasques_pla
             WHERE instalacio_id = ? AND en_curs = 1 AND data_propera_realitzacio < CURDATE()',
            [$instalacioId]
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
}
