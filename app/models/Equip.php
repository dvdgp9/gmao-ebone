<?php

namespace App\Models;

use App\Core\Model;

class Equip extends Model
{
    protected static string $table = 'equips';

    public static function allByInstalacio(int $instalacioId, string $orderBy = 'nom_mn ASC', int $limit = 0, int $offset = 0): array
    {
        $sql = '
            SELECT e.*, s.codi AS sistema_codi, s.nom AS sistema_nom,
                   te.codi AS tipus_codi, te.nom AS tipus_nom,
                   es.nom AS espai_nom, ee.nom AS estat_nom
            FROM equips e
            LEFT JOIN sistemes s ON s.id = e.sistema_id
            LEFT JOIN tipus_equip te ON te.id = e.tipus_equip_id
            LEFT JOIN espais es ON es.id = e.espai_id
            LEFT JOIN estats_equip ee ON ee.id = e.estat_id
            WHERE e.instalacio_id = ?
            ORDER BY ' . $orderBy;

        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        }

        return static::query($sql, [$instalacioId]);
    }

    public static function countByInstalacio(int $instalacioId): int
    {
        $result = static::query(
            'SELECT COUNT(*) AS total FROM equips WHERE instalacio_id = ?',
            [$instalacioId]
        );
        return (int)($result[0]['total'] ?? 0);
    }

    public static function searchByInstalacio(int $instalacioId, string $search = '', ?int $sistemaId = null): array
    {
        $sql = '
            SELECT e.*, s.codi AS sistema_codi, s.nom AS sistema_nom,
                   te.codi AS tipus_codi, te.nom AS tipus_nom,
                   es.nom AS espai_nom, ee.nom AS estat_nom
            FROM equips e
            LEFT JOIN sistemes s ON s.id = e.sistema_id
            LEFT JOIN tipus_equip te ON te.id = e.tipus_equip_id
            LEFT JOIN espais es ON es.id = e.espai_id
            LEFT JOIN estats_equip ee ON ee.id = e.estat_id
            WHERE e.instalacio_id = ?';
        $params = [$instalacioId];

        if ($search) {
            $sql .= ' AND (e.nom_mn LIKE ? OR e.nom_equip LIKE ? OR e.model LIKE ? OR s.codi LIKE ?)';
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($sistemaId) {
            $sql .= ' AND e.sistema_id = ?';
            $params[] = $sistemaId;
        }

        $sql .= ' ORDER BY e.nom_mn ASC';
        return static::query($sql, $params);
    }

    public static function paginateByInstalacio(int $instalacioId, int $perPage = 25, int $page = 1, string $orderBy = 'nom_mn ASC'): array
    {
        $total = static::countByInstalacio($instalacioId);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $items = static::allByInstalacio($instalacioId, $orderBy, $perPage, $offset);

        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
        ];
    }
}
