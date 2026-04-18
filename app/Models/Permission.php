<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable('name', 'slug')]
class Permission extends BaseModel
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory, HasTimestamps;

    protected static array $fields = [
        'name' => 'string',
        'slug' => 'string',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'permission_plan');
    }

    public function applications()
    {
        return $this->belongsToMany(Application::class, 'permission_application');
    }
}
