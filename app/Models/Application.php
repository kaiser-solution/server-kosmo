<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Application extends BaseModel
{
    /** @use HasFactory<\Database\Factories\ApplicationFactory> */
    use HasFactory, HasTimestamps;

    protected static array $fields = [
        'name',
        'namespace',
        'description',
        'endpoint',
    ];

    public function plan()
    {
        return $this->belongsToMany(Plan::class, 'application_plan');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'application_permission');
    }

}
