<?php

namespace App\Models;

use App\Core\Model;

class Normativa extends Model
{
    protected static string $table = 'normatives';

    public static function allOrdered(): array
    {
        return static::all([], 'nom ASC');
    }
}
