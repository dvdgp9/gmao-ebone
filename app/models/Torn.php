<?php

namespace App\Models;

use App\Core\Model;

class Torn extends Model
{
    protected static string $table = 'torns';

    public static function allByInstalacio(int $instalacioId): array
    {
        return static::all(['instalacio_id' => $instalacioId], 'nom ASC');
    }
}
