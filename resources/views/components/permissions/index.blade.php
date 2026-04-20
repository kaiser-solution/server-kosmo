<?php

use App\Livewire\Abstracts\CrudComponent;
use App\Models\Permission;
use Illuminate\Support\Str;

new class extends CrudComponent
{
    public string $modelClass = Permission::class;
    public string $pageTitle = 'Gerenciamento de Permissões';
    public string $newButtonTitle = 'Nova Permissão';
    public string $searchPlaceholder = 'Buscar permissões...';
    public string $editModalTitle = 'Editar Permissão';
    public string $createModalTitle = 'Nova Permissão';
    public string $deleteModalTitle = 'Excluir Permissão';
    public ?string $modalSubtitle = 'As permissões são usadas para controlar o acesso no sistema.';
    public array $columnsToDisplay = [
        'name' => 'Nome',
        'slug' => 'Slug (Chave)',
        'application_name' => 'Aplicação',
    ];

    public function fields(): array
    {
        $fields = parent::fields();
        $fields['application_id']['options'] = \App\Models\Application::pluck('name', 'id')->toArray();
        return $fields;
    }

    public function updatedDataName($value)
    {
        if (!$this->editingId) {
            $this->data['slug'] = Str::slug($value);
        }
    }

};
?>

<section class="w-full">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <flux:heading size="xl" level="1">{{ $this->pageTitle }}</flux:heading>
            <flux:input type="search" name="search_{{ Str::random(10) }}" wire:model.live.debounce.300ms="q" placeholder="{{ $this->searchPlaceholder }}" icon="magnifying-glass" size="sm" class="w-64" autocomplete="off"  />
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
                        <flux:table.cell>
                            @if($key === 'slug')
                                <flux:badge color="blue" size="sm" inset="top bottom">
                                    {{ $item->slug }}
                                </flux:badge>
                            @else
                                {{ $item->$key }}
                            @endif
                        </flux:table.cell>
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
