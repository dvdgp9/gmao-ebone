<?php

namespace App\Models;

use App\Core\Model;

class TascaCataleg extends Model
{
    protected static string $table = 'tasques_cataleg';

    public static function allWithRelations(string $orderBy = 'codi ASC, nom ASC'): array
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
            WHERE tc.activa = 1
            ORDER BY ' . $orderBy
        );
    }

    public static function search(string $term): array
    {
        return static::query('
            SELECT tc.*, s.codi AS sistema_codi, s.nom AS sistema_nom
            FROM tasques_cataleg tc
            LEFT JOIN sistemes s ON s.id = tc.sistema_id
            WHERE tc.activa = 1 AND (tc.nom LIKE ? OR tc.codi LIKE ? OR s.codi LIKE ?)
            ORDER BY tc.codi ASC, tc.nom ASC
        ', ["%{$term}%", "%{$term}%", "%{$term}%"]);
    }
}
