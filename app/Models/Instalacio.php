<?php

namespace App\Models;

use App\Core\Model;
use Throwable;

class Instalacio extends Model
{
    protected static string $table = 'instalacions';

    /**
     * Apartats disponibles a totes les instal·lacions.
     * La selecció de mòduls es conserva només per compatibilitat amb dades antigues.
     */
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
        return static::MODULS;
    }

    /** Mòduls actius de la instal·lació indicada, amb caché per petició. */
    public static function modulsActiusById(?int $instalacioId): array
    {
        return static::MODULS;
    }

    public static function modulActiu(?int $instalacioId, string $modul): bool
    {
        return in_array($modul, static::MODULS, true);
    }

    public static function setModuls(int $id, array $moduls): void
    {
        if (!static::supportsModuls()) {
            return;
        }

        static::update($id, ['moduls' => json_encode(static::MODULS)]);
    }
}
