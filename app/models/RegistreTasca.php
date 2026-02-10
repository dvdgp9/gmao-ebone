<?php

namespace App\Models;

use App\Core\Model;

class RegistreTasca extends Model
{
    protected static string $table = 'registre_tasques';

    public static function allByInstalacio(int $instalacioId, int $limit = 50, int $offset = 0): array
    {
        return static::query('
            SELECT rt.*, tc.codi AS tasca_codi, tc.nom AS tasca_nom,
                   es.nom AS espai_nom, u.nom AS usuari_nom,
                   t.nom AS torn_nom
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
