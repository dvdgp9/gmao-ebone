<?php

namespace App\Models;

use App\Core\Model;

class Espai extends Model
{
    protected static string $table = 'espais';

    public static function allByInstalacio(int $instalacioId): array
    {
        return static::all(['instalacio_id' => $instalacioId], 'nom ASC');
    }
}
