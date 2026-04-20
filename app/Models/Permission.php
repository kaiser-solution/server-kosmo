<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permission extends BaseModel
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory, HasTimestamps;

    protected static array $fields = [
        'name' => [
            'label' => 'Nome',
            'type' => 'text',
            'rules' => 'required|string|max:255',
            'live' => true,
        ],
        'slug' => [
            'label' => 'Slug (Chave)',
            'type' => 'text',
            'rules' => 'required|string|max:255',
        ],
        'application_id' => [
            'label' => 'Aplicação',
            'type' => 'select',
            'rules' => 'required|exists:applications,id',
        ],
    ];

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'permission_plan');
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function getApplicationNameAttribute(): string
    {
        return $this->application?->name ?? '-';
    }
}
