<?php

use App\Livewire\Abstracts\CrudComponent;
use App\Models\Plan;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

new class extends CrudComponent
{
    public string $modelClass = Plan::class;
    public string $pageTitle = 'Gerenciamento de Planos';
    public string $newButtonTitle = 'Novo Plano';
    public string $searchPlaceholder = 'Buscar planos...';
    public string $editModalTitle = 'Editar Plano';
    public string $createModalTitle = 'Novo Plano';
    public string $deleteModalTitle = 'Excluir Plano';
    public ?int $managingPermissionsId = null;
    public array $selectedPermissions = [];
    public array $columnsToDisplay = [
        'application_name' => 'Aplicação',
        'name' => 'Nome',
        'price' => 'Preço',
        'currency' => 'Moeda',
        'permissions_count' => 'Permissões',
        'users_count' => 'Usuários',
    ];

    public function fields(): array
    {
        $fields = parent::fields();
        
        $fields['application_id']['options'] = \App\Models\Application::all()
            ->pluck('name', 'id')
            ->toArray();

        return $fields;
    }

    public function managePermissions($id)
    {
        $this->managingPermissionsId = $id;
        $plan = Plan::with('permissions')->find($id);
        $this->selectedPermissions = $plan->permissions->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->modal('permissions-modal')->show();
    }

    public function savePermissions()
    {
        $plan = Plan::find($this->managingPermissionsId);
        $plan->permissions()->sync($this->selectedPermissions);
        $this->modal('permissions-modal')->close();
        $this->reset(['managingPermissionsId', 'selectedPermissions']);
    }

    #[Computed]
    public function availablePermissions()
    {
        if (!$this->managingPermissionsId) return [];

        $plan = Plan::find($this->managingPermissionsId);
        
        return \App\Models\Permission::where('application_id', $plan->application_id)
            ->get();
    }

    #[Computed]
    public function items()
    {
        return $this->modelClass::query()
            ->with(['application'])
            ->withCount(['permissions', 'users'])
            ->when($this->q, fn ($q) =>
                $q->where('name', 'like', "%{$this->q}%")
                  ->orWhereHas('application', fn($query) => $query->where('name', 'like', "%{$this->q}%"))
            )
            ->paginate(10);
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
                            @if($key === 'permissions_count' || $key === 'users_count')
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
        subtitle="Selecione as permissões para este plano."
        :permissions="$this->availablePermissions"
        :show="!!$this->managingPermissionsId"
    />
</section>
