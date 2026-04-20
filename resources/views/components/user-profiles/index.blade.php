<?php

use App\Livewire\Abstracts\CrudComponent;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

new class extends CrudComponent
{
    public string $modelClass = UserProfile::class;
    public string $pageTitle = 'Perfis de Acesso';
    public string $newButtonTitle = 'Novo Perfil';
    public string $searchPlaceholder = 'Buscar perfis...';
    public string $editModalTitle = 'Editar Perfil';
    public string $createModalTitle = 'Novo Perfil';
    public string $deleteModalTitle = 'Excluir Perfil';
    public array $columnsToDisplay = [
        'user_name' => 'Usuário',
        'name' => 'Nome do Perfil',
        'avatar' => 'Avatar',
        'has_pin' => 'PIN',
    ];

    public function fields(): array
    {
        $fields = parent::fields();

        $fields['user_id']['options'] = User::orderBy('name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        return $fields;
    }

    #[Computed]
    public function items()
    {
        return UserProfile::query()
            ->with('user')
            ->when($this->q, fn ($q) => $q->where('name', 'like', "%{$this->q}%")
                ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$this->q}%"))
            )
            ->paginate(10);
    }
};
?>

<section class="w-full">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <input type="text" style="display:none">
            <input type="password" style="display:none">
            <flux:heading size="xl" level="1">{{ $this->pageTitle }}</flux:heading>
            <flux:input type="search" name="search_{{ Str::random(10) }}" wire:model.live.debounce.300ms="q" placeholder="{{ $this->searchPlaceholder }}" icon="magnifying-glass" size="sm" class="w-64" autocomplete="off" />
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
                    <flux:table.cell>{{ $item->user?->name }}</flux:table.cell>
                    <flux:table.cell>{{ $item->name }}</flux:table.cell>
                    <flux:table.cell>{{ $item->avatar ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$item->getRawOriginal('pin') ? 'green' : 'gray'" size="sm">
                            {{ $item->getRawOriginal('pin') ? 'Sim' : 'Não' }}
                        </flux:badge>
                    </flux:table.cell>
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
