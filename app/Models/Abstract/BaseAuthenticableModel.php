<?php

namespace App\Models\Abstract;

use Illuminate\Foundation\Auth\User as Authenticatable;

abstract class BaseAuthenticableModel extends Authenticatable
{
    protected static array $fields = [];

    public static function fields(): array
    {
        return static::$fields;
    }

    public function getFillable()
    {
        return array_keys(static::$fields);
    }
}
