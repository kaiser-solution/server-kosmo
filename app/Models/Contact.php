<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contact extends BaseModel
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory, HasTimestamps;

    protected static array $fields = [
        'application_id' => [
            'label' => 'Aplicação',
            'type' => 'select',
            'rules' => 'required|exists:applications,id',
        ],
        'type' => [
            'label' => 'Tipo',
            'type' => 'string',
            'rules' => 'required|string|max:50',
        ],
        'name' => [
            'label' => 'Nome',
            'type' => 'string',
            'rules' => 'required|string|max:255',
        ],
        'phone' => [
            'label' => 'Telefone',
            'type' => 'string',
            'rules' => 'nullable|string|max:30',
        ],
        'phone2' => [
            'label' => 'Telefone 2',
            'type' => 'string',
            'rules' => 'nullable|string|max:30',
        ],
        'email' => [
            'label' => 'E-mail',
            'type' => 'string',
            'rules' => 'nullable|email|max:255',
        ],
        'category' => [
            'label' => 'Categoria',
            'type' => 'string',
            'rules' => 'nullable|string|max:100',
        ],
        'active' => [
            'label' => 'Ativo',
            'type' => 'boolean',
            'rules' => 'boolean',
        ],
        'payload' => [
            'label' => 'Dados extras',
            'type' => 'json',
            'rules' => 'nullable|array',
        ],
    ];

    protected $casts = [
        'active' => 'boolean',
        'payload' => 'array',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
