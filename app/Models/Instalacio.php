<?php

namespace App\Models;

use App\Core\Model;
use Throwable;

class Instalacio extends Model
{
    protected static string $table = 'instalacions';

    /** Mòduls opcionals d'una instal·lació. El pla de tasques sempre està actiu. */
    public const MODULS = ['espais', 'torns', 'equips'];

    public static function actives(): array
    {
        return static::all(['activa' => 1], 'nom ASC');
    }

    public static function supportsModuls(): bool
    {
        static $supportsModuls = null;

        if ($supportsModuls !== null) {
            return $supportsModuls;
        }

        try {
            $stmt = static::db()->query("SHOW COLUMNS FROM `instalacions` LIKE 'moduls'");
            $supportsModuls = (bool)$stmt->fetch();
        } catch (Throwable $e) {
            $supportsModuls = false;
        }

        return $supportsModuls;
    }

    /**
     * Mòduls actius d'una instal·lació. NULL (o columna inexistent) = tots,
     * per compatibilitat amb instal·lacions anteriors a la migració.
     */
    public static function modulsActius(?array $instalacio): array
    {
        if (!static::supportsModuls() || $instalacio === null || empty($instalacio['moduls'])) {
            return static::MODULS;
        }

        $moduls = json_decode((string)$instalacio['moduls'], true);
        if (!is_array($moduls)) {
            return static::MODULS;
        }

        return array_values(array_intersect(static::MODULS, $moduls));
    }

    /** Mòduls actius de la instal·lació indicada, amb caché per petició. */
    public static function modulsActiusById(?int $instalacioId): array
    {
        static $cache = [];

        if (!$instalacioId || !static::supportsModuls()) {
            return static::MODULS;
        }

        if (!isset($cache[$instalacioId])) {
            try {
                $cache[$instalacioId] = static::modulsActius(static::find($instalacioId));
            } catch (Throwable $e) {
                $cache[$instalacioId] = static::MODULS;
            }
        }

        return $cache[$instalacioId];
    }

    public static function modulActiu(?int $instalacioId, string $modul): bool
    {
        return in_array($modul, static::modulsActiusById($instalacioId), true);
    }

    public static function setModuls(int $id, array $moduls): void
    {
        if (!static::supportsModuls()) {
            return;
        }

        $moduls = array_values(array_intersect(static::MODULS, $moduls));
        static::update($id, ['moduls' => json_encode($moduls)]);
    }
}
