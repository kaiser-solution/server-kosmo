<?php

namespace App\Livewire\Admin;

use App\Livewire\Abstracts\CrudComponent;
use App\Models\RecordType;

class RecordTypeManager extends CrudComponent
{
    public string $modelClass = RecordType::class;

    public string $pageTitle = 'Gerenciamento de Tipos de Registro';

    public string $newButtonTitle = 'Novo Tipo de Registro';

    public string $searchPlaceholder = 'Buscar tipos de registro...';

    public string $editModalTitle = 'Editar Tipo de Registro';

    public string $createModalTitle = 'Novo Tipo de Registro';

    public string $deleteModalTitle = 'Excluir Tipo de Registro';

    public array $columnsToDisplay = [
        'name' => 'Nome',
        'slug' => 'Slug',
        'status' => 'Status',
    ];

    public function render()
    {
        return view('pages.admin.record-type-manager');
    }
}
