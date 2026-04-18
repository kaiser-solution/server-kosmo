<?php

namespace App\Models\Abstract;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
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
