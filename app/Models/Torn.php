<?php

namespace App\Models;

use App\Core\Model;
use Throwable;

class Torn extends Model
{
    protected static string $table = 'torns';

    public static function allByInstalacio(int $instalacioId): array
    {
        return static::all(['instalacio_id' => $instalacioId], 'nom ASC');
    }

    public static function belongsToInstalacio(int $id, int $instalacioId): bool
    {
        $stmt = static::db()->prepare(
            'SELECT COUNT(*) FROM `torns` WHERE `id` = ? AND `instalacio_id` = ?'
        );
        $stmt->execute([$id, $instalacioId]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public static function supportsHourRange(): bool
    {
        static $supportsHourRange = null;

        if ($supportsHourRange !== null) {
            return $supportsHourRange;
        }

        try {
            $stmt = static::db()->query("SHOW COLUMNS FROM `torns` LIKE 'hora_inici'");
            $supportsHourRange = (bool)$stmt->fetch();
        } catch (Throwable $e) {
            $supportsHourRange = false;
        }

        return $supportsHourRange;
    }

    public static function sanitizeWriteData(array $data): array
    {
        if (!static::supportsHourRange()) {
            unset($data['hora_inici'], $data['hora_fi']);
        }

        return $data;
    }

    public static function supportsUsuariTorn(): bool
    {
        static $supportsUsuariTorn = null;

        if ($supportsUsuariTorn !== null) {
            return $supportsUsuariTorn;
        }

        try {
            $stmt = static::db()->query("SHOW TABLES LIKE 'usuari_torn'");
            $supportsUsuariTorn = (bool)$stmt->fetch();
        } catch (Throwable $e) {
            $supportsUsuariTorn = false;
        }

        return $supportsUsuariTorn;
    }

    public static function usuarisAssignatsByInstalacio(int $instalacioId): array
    {
        if (!static::supportsUsuariTorn()) {
            return [];
        }

        $rows = static::query('
            SELECT ut.torn_id, u.nom, u.cognoms
            FROM `usuari_torn` ut
            JOIN `torns` t ON t.id = ut.torn_id
            JOIN `usuaris` u ON u.id = ut.usuari_id AND u.actiu = 1
            WHERE t.instalacio_id = ?
            ORDER BY u.nom, u.cognoms
        ', [$instalacioId]);

        $perTorn = [];
        foreach ($rows as $row) {
            $perTorn[(int)$row['torn_id']][] = trim($row['nom'] . ' ' . ($row['cognoms'] ?? ''));
        }

        return $perTorn;
    }

    public static function usuariIdsByTorn(int $tornId): array
    {
        if (!static::supportsUsuariTorn()) {
            return [];
        }

        $stmt = static::db()->prepare('SELECT usuari_id FROM `usuari_torn` WHERE torn_id = ?');
        $stmt->execute([$tornId]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public static function tornIdsByUsuariInstalacio(int $usuariId, int $instalacioId): array
    {
        if (!static::supportsUsuariTorn()) {
            return [];
        }

        $stmt = static::db()->prepare('
            SELECT ut.torn_id
            FROM `usuari_torn` ut
            JOIN `torns` t ON t.id = ut.torn_id
            WHERE ut.usuari_id = ? AND t.instalacio_id = ?
        ');
        $stmt->execute([$usuariId, $instalacioId]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Reemplaça els usuaris assignats a un torn. Només accepta usuaris
     * que pertanyin a la instal·lació del torn (validació backend).
     */
    public static function syncUsuarisForTorn(int $tornId, int $instalacioId, array $usuariIds): void
    {
        if (!static::supportsUsuariTorn()) {
            return;
        }

        $usuariIds = static::filterUsuarisOfInstalacio($usuariIds, $instalacioId);

        $db = static::db();
        $db->prepare('DELETE FROM `usuari_torn` WHERE torn_id = ?')->execute([$tornId]);

        if (!empty($usuariIds)) {
            $insert = $db->prepare('INSERT IGNORE INTO `usuari_torn` (usuari_id, torn_id) VALUES (?, ?)');
            foreach ($usuariIds as $usuariId) {
                $insert->execute([$usuariId, $tornId]);
            }
        }
    }

    /**
     * Reemplaça els torns d'un usuari DINS d'una instal·lació concreta.
     * No toca assignacions de torns d'altres instal·lacions.
     */
    public static function syncTornsForUsuari(int $usuariId, int $instalacioId, array $tornIds): void
    {
        if (!static::supportsUsuariTorn()) {
            return;
        }

        $tornIds = array_values(array_filter(array_map('intval', $tornIds), function (int $id) use ($instalacioId) {
            return $id > 0 && static::belongsToInstalacio($id, $instalacioId);
        }));

        $db = static::db();
        $db->prepare('
            DELETE ut FROM `usuari_torn` ut
            JOIN `torns` t ON t.id = ut.torn_id
            WHERE ut.usuari_id = ? AND t.instalacio_id = ?
        ')->execute([$usuariId, $instalacioId]);

        if (!empty($tornIds)) {
            $insert = $db->prepare('INSERT IGNORE INTO `usuari_torn` (usuari_id, torn_id) VALUES (?, ?)');
            foreach ($tornIds as $tornId) {
                $insert->execute([$usuariId, $tornId]);
            }
        }
    }

    private static function filterUsuarisOfInstalacio(array $usuariIds, int $instalacioId): array
    {
        $usuariIds = array_values(array_unique(array_filter(array_map('intval', $usuariIds))));
        if (empty($usuariIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($usuariIds), '?'));
        $stmt = static::db()->prepare(
            "SELECT usuari_id FROM `usuari_instalacio` WHERE instalacio_id = ? AND usuari_id IN ({$placeholders})"
        );
        $stmt->execute(array_merge([$instalacioId], $usuariIds));

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }
}
