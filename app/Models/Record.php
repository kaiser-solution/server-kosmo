<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Database\Factories\RecordFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Record extends BaseModel
{
    /** @use HasFactory<RecordFactory> */
    use HasFactory, HasTimestamps;

    protected static array $fields = [
        'application_id' => [
            'label' => 'Aplicação',
            'type' => 'select',
            'rules' => 'required|exists:applications,id',
        ],
        'record_type_id' => [
            'label' => 'Tipo de Registro',
            'type' => 'select',
            'rules' => 'required|exists:record_types,id',
        ],
        'payload' => [
            'label' => 'Dados',
            'type' => 'json',
            'rules' => 'required|array',
        ],
        'occurred_at' => [
            'label' => 'Data',
            'type' => 'datetime',
            'rules' => 'nullable|date',
        ],
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function recordType()
    {
        return $this->belongsTo(RecordType::class);
    }
}
