<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends BaseModel
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory, HasTimestamps;

    protected static array $fields = [
        'application_id' => [
            'label' => 'Aplicação',
            'type' => 'select',
            'rules' => 'required|exists:applications,id',
            'options' => [], // Preenchido no componente
        ],
        'name' => [
            'label' => 'Nome',
            'type' => 'text',
            'rules' => 'required|string|max:255',
        ],
        'description' => [
            'label' => 'Descrição',
            'type' => 'text',
            'rules' => 'nullable|string',
        ],
        'price' => [
            'label' => 'Preço',
            'type' => 'number',
            'rules' => 'required|numeric|min:0',
        ],
        'currency' => [
            'label' => 'Moeda',
            'type' => 'text',
            'rules' => 'required|string|max:3',
        ],
    ];

    protected static function booted()
    {
        static::updating(function (Plan $plan) {
            if ($plan->isDirty('application_id')) {
                $plan->permissions()->detach();
            }
        });
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_plan');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'plan_user');
    }

    protected function getApplicationNameAttribute(): string
    {
        return $this->application?->name ?? '-';
    }
}
