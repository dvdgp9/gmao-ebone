<?php

namespace App\Models;

use App\Core\Model;

class Periodicitat extends Model
{
    protected static string $table = 'periodicitats';

    public static function allOrdered(): array
    {
        return static::all([], 'ordre ASC');
    }
}
