<?php

namespace App\Models;

use App\Core\Model;

class TascaCataleg extends Model
{
    protected static string $table = 'tasques_cataleg';

    public static function allWithRelations(int $instalacioId, string $orderBy = 'codi ASC, nom ASC'): array
    {
        return static::query('
            SELECT tc.*, s.codi AS sistema_codi, s.nom AS sistema_nom,
                   te.codi AS tipus_codi, te.nom AS tipus_nom,
                   p.nom AS periodicitat_nom, n.nom AS normativa_nom
            FROM tasques_cataleg tc
            LEFT JOIN sistemes s ON s.id = tc.sistema_id
            LEFT JOIN tipus_equip te ON te.id = tc.tipus_equip_id
            LEFT JOIN periodicitats p ON p.id = tc.periodicitat_normativa_id
            LEFT JOIN normatives n ON n.id = tc.normativa_id
            WHERE tc.activa = 1 AND tc.instalacio_id = ?
            ORDER BY ' . $orderBy,
            [$instalacioId]
        );
    }

    public static function search(int $instalacioId, string $term): array
    {
        return static::query('
            SELECT tc.*, s.codi AS sistema_codi, s.nom AS sistema_nom
            FROM tasques_cataleg tc
            LEFT JOIN sistemes s ON s.id = tc.sistema_id
            WHERE tc.activa = 1 AND tc.instalacio_id = ?
              AND (tc.nom LIKE ? OR tc.codi LIKE ? OR s.codi LIKE ?)
            ORDER BY tc.codi ASC, tc.nom ASC
        ', [$instalacioId, "%{$term}%", "%{$term}%", "%{$term}%"]);
    }

    /**
     * Repositori complet de tasques, incloses les arxivades i les que ja no
     * formen part del pla. Els comptadors permeten oferir l'acció adequada
     * sense perdre l'històric de programació.
     */
    public static function repository(int $instalacioId, string $term = ''): array
    {
        $sql = '
            SELECT tc.*, s.codi AS sistema_codi, s.nom AS sistema_nom,
                   te.codi AS tipus_codi, te.nom AS tipus_nom,
                   p.nom AS periodicitat_nom, n.nom AS normativa_nom,
                   (SELECT COUNT(*) FROM tasques_pla tp
                    WHERE tp.tasca_cataleg_id = tc.id AND tp.instalacio_id = tc.instalacio_id
                      AND tp.en_curs = 1) AS active_plan_count,
                   (SELECT COUNT(*) FROM tasques_pla tp
                    WHERE tp.tasca_cataleg_id = tc.id AND tp.instalacio_id = tc.instalacio_id
                      AND tp.en_curs = 0) AS inactive_plan_count,
                   (SELECT MAX(tp.id) FROM tasques_pla tp
                    WHERE tp.tasca_cataleg_id = tc.id AND tp.instalacio_id = tc.instalacio_id
                      AND tp.en_curs = 0) AS reactivable_plan_id
            FROM tasques_cataleg tc
            LEFT JOIN sistemes s ON s.id = tc.sistema_id
            LEFT JOIN tipus_equip te ON te.id = tc.tipus_equip_id
            LEFT JOIN periodicitats p ON p.id = tc.periodicitat_normativa_id
            LEFT JOIN normatives n ON n.id = tc.normativa_id
            WHERE tc.instalacio_id = ?';
        $params = [$instalacioId];

        if ($term !== '') {
            $sql .= ' AND (tc.nom LIKE ? OR tc.codi LIKE ? OR s.codi LIKE ?)';
            $like = "%{$term}%";
            array_push($params, $like, $like, $like);
        }

        $sql .= ' ORDER BY (active_plan_count > 0) DESC, tc.activa DESC, tc.codi ASC, tc.nom ASC';

        return static::query($sql, $params);
    }

    public static function belongsToInstalacio(int $id, int $instalacioId): bool
    {
        $rows = static::query(
            'SELECT 1 FROM tasques_cataleg WHERE id = ? AND instalacio_id = ? LIMIT 1',
            [$id, $instalacioId]
        );
        return !empty($rows);
    }

    public static function hasActivePlan(int $id, int $instalacioId): bool
    {
        $rows = static::query(
            'SELECT 1 FROM tasques_pla WHERE tasca_cataleg_id = ? AND instalacio_id = ? AND en_curs = 1 LIMIT 1',
            [$id, $instalacioId]
        );
        return !empty($rows);
    }
}
