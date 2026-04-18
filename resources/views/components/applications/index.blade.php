<?php

use App\Models\Application;
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
    public $editingApplicationId = null;


    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    #[Computed]
    public function editingApplication()
    {
        return $this->editingApplicationId ? Application::find($this->editingApplicationId) : null;
    }

    #[Computed]
    public function applications()
    {
        return Application::query()
            ->when($this->q, function ($query) {
                $query->where('name', 'like', '%'.$this->q.'%');
            })
            ->paginate(10);
    }

    public function createApplication()
    {
        $this->reset(['name', 'slug', '']);
        $this->modal('application-modal')->show();
    }

    public function editApplication(Application $application)
    {
        $this->editingApplicationId = $application->id;
        $this->name = $application->name;
        $this->slug = $application->slug;

        $this->modal('application-modal')->show();
    }

    public function updatedName($value)
    {
        if (!$this->editingApplicationId) {
            $this->slug = Str::slug($value);
        }
    }

    public function save()
    {
        $this->validate();

        if ($this->editingApplicationId && $application = $this->editingApplication) {
            $application->update([
                'name' => $this->name,
                'slug' => $this->slug,
            ]);
        } else {
            Application::create([
                'name' => $this->name,
                'slug' => $this->slug,
            ]);
        }

        $this->modal('application-modal')->close();
        $this->reset(['name', 'slug', 'editingApplicationId']);
    }

    public function deleteApplication(Application $application)
    {
        $application->delete();
    }
};
?>

<section class="w-full">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <flux:heading size="xl" level="1">Gerenciamento de Aplicações</flux:heading>
            <flux:input type="search" name="search_{{ Str::random(10) }}" wire:model.live.debounce.300ms="q" placeholder="Buscar permissões..." icon="magnifying-glass" size="sm" class="w-64" autocomplete="off"  />
        </div>
        <flux:button wire:click="createApplication" variant="primary" icon="plus">Nova Aplicação</flux:button>
    </div>

    <flux:table :paginate="$this->applications">
        <flux:table.columns>
            <flux:table.column>Nome</flux:table.column>
            <flux:table.column>Ações</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->applications as $application)
                <flux:table.row :key="$application->id">
                    <flux:table.cell variant="strong">{{ $application->name }}</flux:table.cell>

                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:button wire:click="editApplication({{ $application->id }})" variant="ghost" size="sm" icon="pencil" square />
                            <flux:button wire:click="deleteApplication({{ $application->id }})" variant="ghost" size="sm" icon="trash" color="red" square />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="application-modal" class="md:w-[25rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingApplicationId ? 'Editar Permissão' : 'Nova Permissão' }}</flux:heading>
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
