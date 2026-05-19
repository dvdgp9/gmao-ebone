<?php

namespace App\Models;

use App\Core\Model;

class IncidenciaTasca extends Model
{
    protected static string $table = 'incidencies_tasques';

    public static function obertesByInstalacio(int $instalacioId): array
    {
        return static::query('
            SELECT it.*, tc.codi AS tasca_codi, tc.nom AS tasca_nom,
                   es.nom AS espai_nom, t.nom AS torn_nom,
                   u.nom AS usuari_nom
            FROM incidencies_tasques it
            JOIN tasques_pla tp ON tp.id = it.tasca_pla_id
            JOIN tasques_cataleg tc ON tc.id = tp.tasca_cataleg_id
            LEFT JOIN espais es ON es.id = tp.espai_id
            LEFT JOIN torns t ON t.id = tp.torn_id
            LEFT JOIN usuaris u ON u.id = it.usuari_id
            WHERE it.instalacio_id = ? AND it.vista = 0
            ORDER BY it.created_at DESC
        ', [$instalacioId]);
    }

    public static function countObertesByInstalacio(int $instalacioId): int
    {
        $result = static::query(
            'SELECT COUNT(*) AS total FROM incidencies_tasques WHERE instalacio_id = ? AND vista = 0',
            [$instalacioId]
        );

        return (int)($result[0]['total'] ?? 0);
    }

    public static function marcarVista(int $id, int $usuariId): bool
    {
        return static::update($id, [
            'vista' => 1,
            'vista_per' => $usuariId,
            'vista_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
