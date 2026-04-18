<?php

use App\Livewire\Abstracts\CrudComponent;
use App\Models\Application;

new class extends CrudComponent
{
    public string $modelClass = Application::class;
    public string $pageTitle = 'Gerenciamento de Aplicações';
    public string $newButtonTitle = 'Nova Aplicação';
    public string $searchPlaceholder = 'Buscar aplicações...';
    public string $editModalTitle = 'Editar Aplicação';
    public string $createModalTitle = 'Nova Aplicação';
    public string $deleteModalTitle = 'Excluir Aplicação';
    public array $columnsToDisplay = [
        'name' => 'Nome',
        'endpoint' => 'Endpoint',
    ];


};
?>

<section class="w-full">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <flux:heading size="xl" level="1">{{ $this->pageTitle }}</flux:heading>
            <flux:input type="search" name="search_{{ Str::random(10) }}" wire:model.live.debounce.300ms="q" placeholder="{{ $this->searchPlaceholder }}" icon="magnifying-glass" size="sm" class="w-50" autocomplete="off"  />
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">{{ $this->newButtonTitle }}</flux:button>
    </div>

    <flux:table :paginate="$this->items">
        <flux:table.columns>
            @foreach ($columnsToDisplay as $key => $column)
                <flux:table.column>{{ $column }}</flux:table.column>
            @endforeach
            <flux:table.column>{{ $this->actionsColumnTitle }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->items as $item)
                <flux:table.row :key="$item->id">
                    @foreach ($columnsToDisplay as $key => $column)
                        <flux:table.cell variant="strong">{{ $item->$key }}</flux:table.cell>
                    @endforeach

                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:button wire:click="edit({{ $item->id }})" variant="ghost" size="sm" icon="pencil" square />
                            <flux:button wire:click="delete({{ $item->id }})" variant="ghost" size="sm" icon="trash" color="red" square />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <x-utils.form-modal
        :isEditing="!!$editingId"
        :editModalTitle="$this->editModalTitle"
        :createModalTitle="$this->createModalTitle"
        :modalSubtitle="$this->modalSubtitle"
        :fields="$this->fields()"
        :cancelButtonText="$this->cancelButtonText"
        :okButtonText="$this->okButtonText"
    />
</section>
