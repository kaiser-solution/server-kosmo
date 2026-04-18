<?php

namespace App\Models;

use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable('name', 'slug')]
class Permission extends Model
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory,HasTimestamps;


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
