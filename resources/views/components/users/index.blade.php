<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;

new class extends Component
{
    use WithPagination;

    public $name = '';
    public $email = '';
    public $password = '';
    public $is_admin = false;
    public $editingUser = null;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->editingUser?->id)],
            'password' => $this->editingUser ? 'nullable|string|min:8' : 'required|string|min:8',
            'is_admin' => 'boolean',
        ];
    }

    #[Computed]
    public function users()
    {
        return User::paginate(10);
    }

    public function createUser()
    {
        $this->reset(['name', 'email', 'password', 'is_admin', 'editingUser']);
        $this->modal('user-modal')->show();
    }

    public function editUser(User $user)
    {
        $this->editingUser = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->is_admin = $user->is_admin;

        $this->modal('user-modal')->show();
    }

    public function save()
    {
        $data = $this->validate();

        if ($this->editingUser) {
            $updateData = [
                'name' => $this->name,
                'email' => $this->email,
                'is_admin' => $this->is_admin,
            ];

            if ($this->password) {
                $updateData['password'] = Hash::make($this->password);
            }

            $this->editingUser->update($updateData);
        } else {
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'is_admin' => $this->is_admin,
            ]);
        }

        $this->modal('user-modal')->close();
        $this->reset(['name', 'email', 'password', 'is_admin', 'editingUser']);
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return;
        }

        $user->delete();
    }
};
?>

<section class="w-full">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl" level="1">Gerenciamento de Usuários</flux:heading>
        <flux:button wire:click="createUser" variant="primary" icon="plus">Novo Usuário</flux:button>
    </div>

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column>Nome</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Admin</flux:table.column>
            <flux:table.column>Ações</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell>{{ $user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$user->is_admin ? 'green' : 'gray'" size="sm">
                            {{ $user->is_admin ? 'Sim' : 'Não' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:button wire:click="editUser({{ $user->id }})" variant="ghost" size="sm" icon="pencil" square />
                            @if ($user->id !== auth()->id())
                                <flux:button wire:click="deleteUser({{ $user->id }})" variant="ghost" size="sm" icon="trash" color="red" square />
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="user-modal" class="md:w-[25rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingUser ? 'Editar Usuário' : 'Novo Usuário' }}</flux:heading>
                <flux:subheading>Preencha os dados abaixo.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Nome</flux:label>
                <flux:input wire:model="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input wire:model="email" type="email" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Senha {{ $editingUser ? '(deixe em branco para não alterar)' : '' }}</flux:label>
                <flux:input wire:model="password" type="password" />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:switch wire:model="is_admin" label="Administrador" />
                <flux:error name="is_admin" />
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
