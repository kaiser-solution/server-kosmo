<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppConfig extends BaseModel
{
    use HasFactory, HasTimestamps;

    protected static array $fields = [
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
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
