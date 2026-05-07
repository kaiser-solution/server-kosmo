<?php

namespace App\Livewire\Admin;

use App\Livewire\Abstracts\CrudComponent;
use App\Models\RecordPattern;
use App\Models\RecordType;

class RecordPatternManager extends CrudComponent
{
    public string $modelClass = RecordPattern::class;

    public string $pageTitle = 'Gerenciamento de Padrões de Registro';

    public string $newButtonTitle = 'Novo Padrão de Registro';

    public string $searchPlaceholder = 'Buscar padrões de registro...';

    public string $editModalTitle = 'Editar Padrão de Registro';

    public string $createModalTitle = 'Novo Padrão de Registro';

    public string $deleteModalTitle = 'Excluir Padrão de Registro';

    public array $columnsToDisplay = [
        'name' => 'Nome',
    ];

    public function fields(): array
    {
        $fields = parent::fields();
        $fields['recordTypes']['options'] = RecordType::pluck('name', 'id')->toArray();

        return $fields;
    }

    public function render()
    {
        return view('pages.admin.record-pattern-manager');
    }
}
