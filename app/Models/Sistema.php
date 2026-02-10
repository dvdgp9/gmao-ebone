<?php

namespace App\Models;

use App\Core\Model;

class Sistema extends Model
{
    protected static string $table = 'sistemes';

    public static function allOrdered(): array
    {
        return static::all([], 'codi ASC');
    }
}
