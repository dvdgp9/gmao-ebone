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
}
