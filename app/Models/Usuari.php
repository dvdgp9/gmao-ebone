<?php

namespace App\Models;

use App\Core\Model;

class Usuari extends Model
{
    protected static string $table = 'usuaris';

    public static function findByEmail(string $email): ?array
    {
        $stmt = static::db()->prepare('SELECT * FROM usuaris WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function getAssignacions(int $usuariId): array
    {
        return static::query('
            SELECT ui.*, i.nom AS instalacio_nom, r.nom AS rol_nom
            FROM usuari_instalacio ui
            JOIN instalacions i ON i.id = ui.instalacio_id AND i.activa = 1
            JOIN rols r ON r.id = ui.rol_id
            WHERE ui.usuari_id = ?
            ORDER BY i.nom
        ', [$usuariId]);
    }

    public static function isSuperadmin(int $usuariId): bool
    {
        $result = static::query('
            SELECT 1 FROM usuari_instalacio ui
            JOIN rols r ON r.id = ui.rol_id
            WHERE ui.usuari_id = ? AND r.nom = "superadmin"
            LIMIT 1
        ', [$usuariId]);
        return !empty($result);
    }

    public static function assignInstalacio(int $usuariId, int $instalacioId, int $rolId): int
    {
        static::execute('
            INSERT INTO usuari_instalacio (usuari_id, instalacio_id, rol_id)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE rol_id = VALUES(rol_id)
        ', [$usuariId, $instalacioId, $rolId]);
        return (int)static::db()->lastInsertId();
    }

    public static function removeInstalacio(int $usuariId, int $instalacioId): bool
    {
        return static::execute(
            'DELETE FROM usuari_instalacio WHERE usuari_id = ? AND instalacio_id = ?',
            [$usuariId, $instalacioId]
        );
    }

    public static function allWithRoles(?int $instalacioId = null): array
    {
        if ($instalacioId) {
            return static::query('
                SELECT u.*, r.nom AS rol_nom, ui.instalacio_id
                FROM usuaris u
                JOIN usuari_instalacio ui ON ui.usuari_id = u.id AND ui.instalacio_id = ?
                JOIN rols r ON r.id = ui.rol_id
                ORDER BY u.nom
            ', [$instalacioId]);
        }
        return static::all([], 'nom ASC');
    }
}
