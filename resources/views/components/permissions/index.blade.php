<?php

use App\Models\Permission;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

new class extends Component
{
    use WithPagination;

    public $q = '';
    public $name = '';
    public $slug = '';
    public $editingPermissionId = null;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('permissions')->ignore($this->editingPermissionId)],
        ];
    }

    #[Computed]
    public function editingPermission()
    {
        return $this->editingPermissionId ? Permission::find($this->editingPermissionId) : null;
    }

    #[Computed]
    public function permissions()
    {
        return Permission::query()
            ->when($this->q, function ($query) {
                $query->where('name', 'like', '%'.$this->q.'%')
                    ->orWhere('slug', 'like', '%'.$this->q.'%');
            })
            ->paginate(10);
    }

    public function createPermission()
    {
        $this->reset(['name', 'slug', 'editingPermissionId']);
        $this->modal('permission-modal')->show();
    }

    public function editPermission(Permission $permission)
    {
        $this->editingPermissionId = $permission->id;
        $this->name = $permission->name;
        $this->slug = $permission->slug;

        $this->modal('permission-modal')->show();
    }

    public function updatedName($value)
    {
        if (!$this->editingPermissionId) {
            $this->slug = Str::slug($value);
        }
    }

    public function save()
    {
        $this->validate();

        if ($this->editingPermissionId && $permission = $this->editingPermission) {
            $permission->update([
                'name' => $this->name,
                'slug' => $this->slug,
            ]);
        } else {
            Permission::create([
                'name' => $this->name,
                'slug' => $this->slug,
            ]);
        }

        $this->modal('permission-modal')->close();
        $this->reset(['name', 'slug', 'editingPermissionId']);
    }

    public function deletePermission(Permission $permission)
    {
        $permission->delete();
    }
};
?>

<section class="w-full">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <flux:heading size="xl" level="1">Gerenciamento de Permissões</flux:heading>
            <flux:input type="search" name="search_{{ Str::random(10) }}" wire:model.live.debounce.300ms="q" placeholder="Buscar permissões..." icon="magnifying-glass" size="sm" class="w-64" autocomplete="off"  />
        </div>
        <flux:button wire:click="createPermission" variant="primary" icon="plus">Nova Permissão</flux:button>
    </div>

    <flux:table :paginate="$this->permissions">
        <flux:table.columns>
            <flux:table.column>Nome</flux:table.column>
            <flux:table.column>Slug (Chave)</flux:table.column>
            <flux:table.column>Ações</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->permissions as $permission)
                <flux:table.row :key="$permission->id">
                    <flux:table.cell variant="strong">{{ $permission->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="blue" size="sm" inset="top bottom">
                            {{ $permission->slug }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:button wire:click="editPermission({{ $permission->id }})" variant="ghost" size="sm" icon="pencil" square />
                            <flux:button wire:click="deletePermission({{ $permission->id }})" variant="ghost" size="sm" icon="trash" color="red" square />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="permission-modal" class="md:w-[25rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingPermissionId ? 'Editar Permissão' : 'Nova Permissão' }}</flux:heading>
                <flux:subheading>As permissões são usadas para controlar o acesso no sistema.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Nome</flux:label>
                <flux:input wire:model.live="name" placeholder="Ex: Criar Usuários" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Slug (Chave)</flux:label>
                <flux:input wire:model="slug" placeholder="Ex: criar-usuarios" />
                <flux:subheading>A chave é usada internamente no código para verificações.</flux:subheading>
                <flux:error name="slug" />
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button wire:click="save" variant="primary">Salvar</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
