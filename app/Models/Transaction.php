<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends BaseModel
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, HasTimestamps;

    protected static array $fields = [
        'application_id' => [
            'label' => 'Aplicação',
            'type' => 'select',
            'rules' => 'required|exists:applications,id',
        ],
        'type' => [
            'label' => 'Tipo',
            'type' => 'select',
            'rules' => 'required|in:expense,income,transfer',
        ],
        'amount' => [
            'label' => 'Valor',
            'type' => 'number',
            'rules' => 'required|numeric|min:0',
        ],
        'description' => [
            'label' => 'Descrição',
            'type' => 'text',
            'rules' => 'nullable|string|max:255',
        ],
        'category' => [
            'label' => 'Categoria',
            'type' => 'text',
            'rules' => 'nullable|string|max:100',
        ],
        'occurred_at' => [
            'label' => 'Data',
            'type' => 'datetime',
            'rules' => 'required|date',
        ],
        'metadata' => [
            'label' => 'Metadados',
            'type' => 'json',
            'rules' => 'nullable|array',
        ],
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'metadata' => 'array',
        'amount' => 'decimal:2',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
