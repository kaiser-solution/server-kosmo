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
        'status' => [
            'label' => 'Status',
            'type' => 'select',
            'options' => [
                'active' => 'Ativo',
                'inactive' => 'Inativo',
            ],
            'rules' => 'required|string|in:active,inactive',
        ],
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function applications()
    {
        return $this->belongsToMany(Application::class, 'application_record_type');
    }

    public function records()
    {
        return $this->hasMany(Record::class);
    }

    public function recordPatterns()
    {
        return $this->belongsToMany(RecordPattern::class, 'record_pattern_record_type');
    }
}
