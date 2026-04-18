<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable('name','namespace','description','endpoint')]
class Application extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicationFactory> */
    use HasFactory, HasTimestamps;

    public function plan()
    {
        return $this->belongsToMany(Plan::class, 'application_plan');
    }
    
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'application_permission');
    }

}
