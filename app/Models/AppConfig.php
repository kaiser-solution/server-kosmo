<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppConfig extends BaseModel
{
    use HasFactory, HasTimestamps;

    protected static array $fields = [
        'application_id' => [
            'label' => 'Aplicação',
            'type' => 'hidden',
            'rules' => 'required|exists:applications,id',
        ],
        'display_name' => [
            'label' => 'Nome de Exibição',
            'type' => 'text',
            'rules' => 'nullable|string|max:255',
        ],
        'primary_color' => [
            'label' => 'Cor Primária',
            'type' => 'text',
            'rules' => 'nullable|string|max:7',
        ],
        'secondary_color' => [
            'label' => 'Cor Secundária',
            'type' => 'text',
            'rules' => 'nullable|string|max:7',
        ],
        'default_currency' => [
            'label' => 'Moeda Padrão',
            'type' => 'text',
            'rules' => 'nullable|string|max:3',
        ],
        'categories' => [
            'label' => 'Categorias',
            'type' => 'json',
            'rules' => 'nullable|json',
        ],
    ];

    protected $casts = [
        'categories' => 'array',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
