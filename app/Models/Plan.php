<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable('name', 'description', 'price', 'currency')]
class Plan extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory, HasTimestamps;

    public function applications()
    {
        return $this->belongsToMany(Application::class, 'application_plan');
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'plan_user');
    }

}
