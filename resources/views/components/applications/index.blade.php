<?php

use App\Livewire\Abstracts\CrudComponent;
use App\Models\Application;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

new class extends CrudComponent
{
    public string $modelClass = Application::class;
    public string $pageTitle = 'Gerenciamento de Aplicações';
    public string $newButtonTitle = 'Nova Aplicação';
    public string $searchPlaceholder = 'Buscar aplicações...';
    public string $editModalTitle = 'Editar Aplicação';
    public string $createModalTitle = 'Nova Aplicação';
    public string $deleteModalTitle = 'Excluir Aplicação';
    public ?int $managingPermissionsId = null;
    public string $newPermissionName = '';
    public string $newPermissionSlug = '';

    public array $columnsToDisplay = [
        'name' => 'Nome',
        'endpoint' => 'Endpoint',
        'permissions_count' => 'Permissões',
    ];

    public function managePermissions($id)
    {
        $this->managingPermissionsId = $id;
        $this->modal('permissions-modal')->show();
    }

    public function addPermission()
    {
        $this->validate([
            'newPermissionName' => 'required|string|max:255',
            'newPermissionSlug' => 'required|string|max:255|unique:permissions,slug',
        ]);

        \App\Models\Permission::create([
            'name' => $this->newPermissionName,
            'slug' => $this->newPermissionSlug,
            'application_id' => $this->managingPermissionsId,
        ]);

        $this->reset(['newPermissionName', 'newPermissionSlug']);
    }

    public function deletePermission($id)
    {
        \App\Models\Permission::find($id)?->delete();
    }

    #[Computed]
    public function currentApplicationPermissions()
    {
        if (!$this->managingPermissionsId) return [];
        return \App\Models\Permission::where('application_id', $this->managingPermissionsId)->get();
    }

    #[Computed]
    public function availablePermissions()
    {
        return \App\Models\Permission::all();
    }

    #[Computed]
    public function items()
    {
        return $this->modelClass::query()
            ->withCount('permissions')
            ->when($this->q, fn ($q) =>
                $q->where('name', 'like', "%{$this->q}%")
                  ->orWhere('endpoint', 'like', "%{$this->q}%")
            )
            ->paginate(10);
    }
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
                        <flux:table.cell>
                            @if($key === 'permissions_count')
                                <flux:badge color="zinc" size="sm" inset="top bottom">
                                    {{ $item->$key }}
                                </flux:badge>
                            @else
                                {{ $item->$key }}
                            @endif
                        </flux:table.cell>
                    @endforeach

                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:button wire:click="managePermissions({{ $item->id }})" variant="ghost" size="sm" icon="shield-check" square />
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

    <x-utils.permissions-modal
        title="Permissões da Aplicação"
        subtitle="Gerencie as permissões vinculadas a esta aplicação."
        :show="!!$this->managingPermissionsId"
    >
        <div class="space-y-4">
            <div class="flex gap-2 items-start">
                <flux:field>
                    <flux:input wire:model="newPermissionName" placeholder="Nome da Permissão" size="sm" />
                    <flux:error name="newPermissionName" />
                </flux:field>
                <flux:field>
                    <flux:input wire:model="newPermissionSlug" placeholder="Slug" size="sm" />
                    <flux:error name="newPermissionSlug" />
                </flux:field>
                <flux:button wire:click="addPermission" variant="primary" size="sm" icon="plus" class="mt-0">Adicionar</flux:button>
            </div>

            <flux:separator />

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nome</flux:table.column>
                    <flux:table.column>Slug</flux:table.column>
                    <flux:table.column align="end">Ações</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->currentApplicationPermissions as $permission)
                        <flux:table.row :key="$permission->id">
                            <flux:table.cell>{{ $permission->name }}</flux:table.cell>
                            <flux:table.cell><code>{{ $permission->slug }}</code></flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button wire:click="deletePermission({{ $permission->id }})" variant="ghost" size="sm" icon="trash" color="red" square />
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="text-center text-zinc-500 py-4 italic">
                                Nenhuma permissão vinculada.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </x-utils.permissions-modal>
</section>
