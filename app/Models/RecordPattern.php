<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecordPattern extends BaseModel
{
    use HasFactory;

    protected static array $fields = [
        'name' => [
            'label' => 'Nome',
            'type' => 'text',
            'rules' => 'required|string|max:255',
        ],
        'recordTypes' => [
            'label' => 'Tipos de Registro',
            'type' => 'checkbox-group',
            'rules' => 'nullable|array',
        ],
    ];

    protected $casts = [
        'defaults' => 'array',
    ];

    public function recordTypes()
    {
        return $this->belongsToMany(RecordType::class, 'record_pattern_record_type');
    }
}
