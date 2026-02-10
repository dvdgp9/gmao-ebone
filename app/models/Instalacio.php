<?php

namespace App\Models;

use App\Core\Model;

class Instalacio extends Model
{
    protected static string $table = 'instalacions';

    public static function actives(): array
    {
        return static::all(['activa' => 1], 'nom ASC');
    }
}
