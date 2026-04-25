<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Database\Factories\RecordTypeFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecordType extends BaseModel
{
    /** @use HasFactory<RecordTypeFactory> */
    use HasFactory, HasTimestamps;

    protected static array $fields = [
        'application_id' => [
            'label' => 'Aplicação',
            'type' => 'select',
            'rules' => 'required|exists:applications,id',
        ],
        'name' => [
            'label' => 'Nome',
            'type' => 'text',
            'rules' => 'required|string|max:255',
        ],
        'slug' => [
            'label' => 'Slug',
            'type' => 'text',
            'rules' => 'required|string|max:100',
        ],
        'description' => [
            'label' => 'Descrição',
            'type' => 'text',
            'rules' => 'nullable|string',
        ],
        'schema' => [
            'label' => 'Schema (JSON)',
            'type' => 'json',
            'rules' => 'nullable|array',
        ],
        'active' => [
            'label' => 'Ativo',
            'type' => 'checkbox',
            'rules' => 'boolean',
        ],
    ];

    protected $casts = [
        'schema' => 'array',
        'active' => 'boolean',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function records()
    {
        return $this->hasMany(Record::class);
    }
}
