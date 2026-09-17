<?php

namespace App\Models;

use App\Core\Model;

class Usuari extends Model
{
    protected static string $table = 'usuaris';

    public const ROL_ETIQUETES = [
        'superadmin' => 'Superadmin',
        'admin_instalacio' => 'Admin. instal·lació',
        'cap_manteniment' => 'Cap de manteniment',
        'tecnic' => 'Tècnic',
        'lectura' => 'Lectura',
    ];

    public static function etiquetaRol(string $rolNom): string
    {
        return self::ROL_ETIQUETES[$rolNom] ?? ucfirst(str_replace('_', ' ', $rolNom));
    }

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
        $result = static::query('SELECT is_superadmin FROM usuaris WHERE id = ? LIMIT 1', [$usuariId]);
        return !empty($result) && !empty($result[0]['is_superadmin']);
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
            $rows = static::query('
                SELECT u.*, r.nom AS rol_nom, ui.instalacio_id, i.nom AS instalacio_nom
                FROM usuaris u
                JOIN usuari_instalacio ui ON ui.usuari_id = u.id AND ui.instalacio_id = ?
                JOIN rols r ON r.id = ui.rol_id
                JOIN instalacions i ON i.id = ui.instalacio_id
                WHERE u.is_superadmin = 0
                ORDER BY u.nom
            ', [$instalacioId]);

            foreach ($rows as &$row) {
                $row['assignacions'] = [[
                    'instalacio_id' => $row['instalacio_id'],
                    'instalacio_nom' => $row['instalacio_nom'],
                    'rol_nom' => $row['rol_nom'],
                ]];
            }
            unset($row);

            return $rows;
        }

        // Sense filtre (superadmin): la consulta retorna una fila per assignació;
        // les agrupem per usuari en PHP per evitar duplicats a la llista.
        $rows = static::query('
            SELECT u.*, r.nom AS rol_nom, ui.instalacio_id, i.nom AS instalacio_nom
            FROM usuaris u
            LEFT JOIN usuari_instalacio ui ON ui.usuari_id = u.id
            LEFT JOIN rols r ON r.id = ui.rol_id
            LEFT JOIN instalacions i ON i.id = ui.instalacio_id
            ORDER BY u.nom, i.nom
        ');

        $usuaris = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            if (!isset($usuaris[$id])) {
                $usuaris[$id] = $row;
                $usuaris[$id]['assignacions'] = [];
            }
            if (!empty($row['instalacio_id'])) {
                $usuaris[$id]['assignacions'][] = [
                    'instalacio_id' => $row['instalacio_id'],
                    'instalacio_nom' => $row['instalacio_nom'],
                    'rol_nom' => $row['rol_nom'],
                ];
            }
        }

        return array_values($usuaris);
    }

    /**
     * Usuaris per id amb les seves instal·lacions (accions en bloc).
     *
     * @return list<array{id: int, nom: string, cognoms: ?string, email: string, actiu: int, is_superadmin: int, instalacio_ids: list<int>}>
     */
    public static function perIdsAmbInstalacions(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = static::query("
            SELECT u.id, u.nom, u.cognoms, u.email, u.actiu, u.is_superadmin, GROUP_CONCAT(ui.instalacio_id) AS instalacio_ids
            FROM usuaris u
            LEFT JOIN usuari_instalacio ui ON ui.usuari_id = u.id
            WHERE u.id IN ({$placeholders})
            GROUP BY u.id, u.nom, u.cognoms, u.email, u.actiu, u.is_superadmin
            ORDER BY u.nom, u.cognoms
        ", $ids);

        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['instalacio_ids'] = $row['instalacio_ids'] ? array_map('intval', explode(',', $row['instalacio_ids'])) : [];
        }
        unset($row);

        return $rows;
    }

    public static function activeByInstalacio(int $instalacioId): array
    {
        return static::query('
            SELECT u.id, u.nom, u.cognoms, r.nom AS rol_nom
            FROM usuaris u
            JOIN usuari_instalacio ui ON ui.usuari_id = u.id AND ui.instalacio_id = ?
            JOIN rols r ON r.id = ui.rol_id
            WHERE u.actiu = 1
            ORDER BY u.nom, u.cognoms
        ', [$instalacioId]);
    }

    public static function belongsToInstalacio(int $usuariId, int $instalacioId): bool
    {
        $stmt = static::db()->prepare(
            'SELECT COUNT(*) FROM usuari_instalacio WHERE usuari_id = ? AND instalacio_id = ?'
        );
        $stmt->execute([$usuariId, $instalacioId]);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Pot qui edita canviar les dades del compte (nom, email, contrasenya, estat, enllaços d'accés)?
     * Un admin d'instal·lació només pot si totes les instal·lacions de l'usuari són seves;
     * si no, d'aquell usuari només en pot canviar el rol i els torns a la seva instal·lació.
     */
    public static function compteGestionable(
        bool $editorSuperadmin,
        int $editorId,
        array $usuari,
        array $instalacionsUsuari,
        array $instalacionsAdminEditor
    ): bool {
        if ($editorSuperadmin) {
            return true;
        }
        if (!empty($usuari['is_superadmin'])) {
            return false;
        }
        if ((int)$usuari['id'] === $editorId) {
            return true;
        }

        $instalacionsUsuari = array_map('intval', $instalacionsUsuari);

        return $instalacionsUsuari !== []
            && array_diff($instalacionsUsuari, array_map('intval', $instalacionsAdminEditor)) === [];
    }

    /**
     * @return array<int, list<int>> usuariId => ids de les seves instal·lacions
     */
    public static function instalacioIdsPerUsuaris(array $usuariIds): array
    {
        $usuariIds = array_values(array_unique(array_filter(array_map('intval', $usuariIds))));
        if ($usuariIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($usuariIds), '?'));
        $rows = static::query(
            "SELECT usuari_id, instalacio_id FROM usuari_instalacio WHERE usuari_id IN ({$placeholders})",
            $usuariIds
        );

        $perUsuari = array_fill_keys($usuariIds, []);
        foreach ($rows as $row) {
            $perUsuari[(int)$row['usuari_id']][] = (int)$row['instalacio_id'];
        }

        return $perUsuari;
    }

    /**
     * @return list<int> instal·lacions on l'usuari té aquest rol
     */
    public static function instalacioIdsAmbRol(int $usuariId, string $rolNom): array
    {
        $rows = static::query('
            SELECT ui.instalacio_id
            FROM usuari_instalacio ui
            JOIN rols r ON r.id = ui.rol_id
            WHERE ui.usuari_id = ? AND r.nom = ?
        ', [$usuariId, $rolNom]);

        return array_map(static fn($row) => (int)$row['instalacio_id'], $rows);
    }
}
