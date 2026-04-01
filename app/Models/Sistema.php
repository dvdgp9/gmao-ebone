<?php

namespace App\Models;

use App\Core\Model;

class Sistema extends Model
{
    protected static string $table = 'sistemes';

    public static function allOrdered(): array
    {
        return static::all([], 'codi ASC');
    }

    public static function allWithUsage(string $search = ''): array
    {
        $sql = '
            SELECT s.*,
                   (SELECT COUNT(*) FROM equips e WHERE e.sistema_id = s.id) AS equips_count,
                   (SELECT COUNT(*) FROM tasques_cataleg tc WHERE tc.sistema_id = s.id AND tc.activa = 1) AS tasques_count
            FROM sistemes s';
        $params = [];

        if ($search !== '') {
            $sql .= ' WHERE s.codi LIKE ? OR s.nom LIKE ? OR s.descripcio LIKE ?';
            $like = "%{$search}%";
            $params = [$like, $like, $like];
        }

        $sql .= ' ORDER BY s.codi ASC';

        return static::query($sql, $params);
    }

    public static function canDelete(int $id): bool
    {
        $result = static::query(
            'SELECT
                (SELECT COUNT(*) FROM equips WHERE sistema_id = ?) AS equips_count,
                (SELECT COUNT(*) FROM tasques_cataleg WHERE sistema_id = ? AND activa = 1) AS tasques_count',
            [$id, $id]
        );

        $equipsCount = (int)($result[0]['equips_count'] ?? 0);
        $tasquesCount = (int)($result[0]['tasques_count'] ?? 0);

        return $equipsCount === 0 && $tasquesCount === 0;
    }
}
