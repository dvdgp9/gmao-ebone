<?php

namespace App\Core;

use App\Models\Database;
use PDO;

abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    protected static function db(): PDO
    {
        return Database::getInstance();
    }

    public static function find(int $id): ?array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM `' . static::$table . '` WHERE `' . static::$primaryKey . '` = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function all(array $where = [], string $orderBy = '', int $limit = 0, int $offset = 0): array
    {
        $sql = 'SELECT * FROM `' . static::$table . '`';
        $params = [];

        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $col => $val) {
                $conditions[] = '`' . $col . '` = ?';
                $params[] = $val;
            }
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if ($orderBy) {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
            if ($offset > 0) {
                $sql .= ' OFFSET ' . $offset;
            }
        }

        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function count(array $where = []): int
    {
        $sql = 'SELECT COUNT(*) FROM `' . static::$table . '`';
        $params = [];

        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $col => $val) {
                $conditions[] = '`' . $col . '` = ?';
                $params[] = $val;
            }
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = 'INSERT INTO `' . static::$table . '` (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $placeholders) . ')';

        $stmt = static::db()->prepare($sql);
        $stmt->execute(array_values($data));
        return (int)static::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            $sets[] = '`' . $col . '` = ?';
            $params[] = $val;
        }
        $params[] = $id;

        $sql = 'UPDATE `' . static::$table . '` SET ' . implode(', ', $sets) . ' WHERE `' . static::$primaryKey . '` = ?';

        $stmt = static::db()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $stmt = static::db()->prepare(
            'DELETE FROM `' . static::$table . '` WHERE `' . static::$primaryKey . '` = ?'
        );
        return $stmt->execute([$id]);
    }

    public static function query(string $sql, array $params = []): array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function execute(string $sql, array $params = []): bool
    {
        $stmt = static::db()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function paginate(array $where = [], string $orderBy = 'id DESC', int $perPage = 25, int $page = 1): array
    {
        $total = static::count($where);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $items = static::all($where, $orderBy, $perPage, $offset);

        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
        ];
    }
}
