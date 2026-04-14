<?php

use App\Models\DeviceFingerprint;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;
use Illuminate\Support\Str;

new class extends Component
{
    use WithPagination;

    public $q = '';
    public $name = '';
    public $email = '';
    public $phone = '';
    public $regulatory_bodies = '';
    public $credentials = '';
    public $specialties = '';
    public $description = '';
    public $password = '';
    public $is_admin = false;
    public $editingUserId = null;
    public $managingFingerprintsUserId = null;
    public $managingPermissionsUserId = null;
    public $newFingerprint = '';
    public $userPermissions = []; // [id => bool]

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->editingUserId)],
            'phone' => 'nullable|string|max:255',
            'regulatory_bodies' => 'nullable|string',
            'credentials' => 'nullable|string',
            'specialties' => 'nullable|string',
            'description' => 'nullable|string',
            'password' => $this->editingUserId ? 'nullable|string|min:8' : 'required|string|min:8',
            'is_admin' => 'boolean',
            'userPermissions' => 'array',
        ];
    }

    #[Computed]
    public function editingUser()
    {
        return $this->editingUserId ? User::find($this->editingUserId) : null;
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->q, function ($query) {
                $query->where('name', 'like', '%'.$this->q.'%')
                    ->orWhere('email', 'like', '%'.$this->q.'%');
            })
            ->paginate(10);
    }

    #[Computed]
    public function managingFingerprintsUser()
    {
        return $this->managingFingerprintsUserId ? User::find($this->managingFingerprintsUserId) : null;
    }

    #[Computed]
    public function managingPermissionsUser()
    {
        return $this->managingPermissionsUserId ? User::find($this->managingPermissionsUserId) : null;
    }

    #[Computed]
    public function allPermissions()
    {
        return Permission::all();
    }

    public function createUser()
    {
        $this->reset(['name', 'email', 'phone', 'regulatory_bodies', 'credentials', 'specialties', 'description', 'password', 'is_admin', 'editingUserId']);
        $this->modal('user-modal')->show();
    }

    public function editUser($userId)
    {
        $user = User::findOrFail($userId);
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->regulatory_bodies = $user->regulatory_bodies;
        $this->credentials = $user->credentials;
        $this->specialties = $user->specialties;
        $this->description = $user->description;
        $this->password = '';
        $this->is_admin = $user->is_admin;

        $this->modal('user-modal')->show();
    }

    public function save()
    {
        $data = $this->validate();

        if ($this->editingUserId === auth()->id() && ! $this->is_admin) {
            $this->is_admin = true;
            Flux::toast(
                text: 'Você não pode remover seu próprio acesso administrativo.',
                variant: 'error',
            );

            return;
        }

        if ($this->editingUserId && $user = $this->editingUser) {
            $updateData = [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'regulatory_bodies' => $this->regulatory_bodies,
                'credentials' => $this->credentials,
                'specialties' => $this->specialties,
                'description' => $this->description,
                'is_admin' => $this->is_admin,
            ];

            if ($this->password) {
                $updateData['password'] = Hash::make($this->password);
            }

            $user->update($updateData);
        } else {
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'regulatory_bodies' => $this->regulatory_bodies,
                'credentials' => $this->credentials,
                'specialties' => $this->specialties,
                'description' => $this->description,
                'password' => Hash::make($this->password),
                'is_admin' => $this->is_admin,
            ]);
        }

        $this->modal('user-modal')->close();
        $this->reset(['name', 'email', 'phone', 'regulatory_bodies', 'credentials', 'specialties', 'description', 'password', 'is_admin', 'editingUserId']);
    }

    public function deleteUser($userId)
    {
        if ($userId === auth()->id()) {
            return;
        }

        User::findOrFail($userId)->delete();
    }

    public function manageFingerprints($userId)
    {
        $this->managingFingerprintsUserId = $userId;
        $this->newFingerprint = '';
        $this->modal('fingerprints-modal')->show();
    }

    public function addFingerprint()
    {
        $this->validate([
            'newFingerprint' => 'required|string|unique:device_fingerprints,fingerprint',
        ]);

        $this->managingFingerprintsUser->fingerprints()->create([
            'fingerprint' => $this->newFingerprint,
        ]);

        $this->newFingerprint = '';
    }

    public function deleteFingerprint($fingerprintId)
    {
        DeviceFingerprint::findOrFail($fingerprintId)->delete();
    }

    public function managePermissions($userId)
    {
        $user = User::with('permissions')->findOrFail($userId);
        $this->managingPermissionsUserId = $user->id;
        $this->userPermissions = $user->permissions->pluck('id')->mapWithKeys(fn ($id) => [(string) $id => true])->toArray();
        $this->modal('permissions-user-modal')->show();
    }

    public function savePermissions()
    {
        if ($user = $this->managingPermissionsUser) {
            $permissionIds = array_keys(array_filter($this->userPermissions));
            $user->permissions()->sync($permissionIds);
        }

        $this->modal('permissions-user-modal')->close();

        $this->reset(['managingPermissionsUserId', 'userPermissions']);

        Flux::toast(
            text: 'Permissões atualizadas com sucesso.',
            variant: 'success',
        );
    }
};
?>

<section class="w-full">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <input type="text" style="display:none">
            <input type="password" style="display:none">
            <flux:heading size="xl" level="1">Gerenciamento de Usuários</flux:heading>
            <flux:input type="search" name="search_{{ Str::random(10) }}" wire:model.live.debounce.300ms="q" placeholder="Buscar usuários..." icon="magnifying-glass" size="sm" class="w-64" autocomplete="off"  />
        </div>
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
                            <flux:button wire:click="managePermissions({{ $user->id }})" variant="ghost" size="sm" icon="shield-check" square />
                            <flux:button wire:click="manageFingerprints({{ $user->id }})" variant="ghost" size="sm" icon="key" square />
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

    <flux:modal name="user-modal" class="md:w-[40rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingUserId ? 'Editar Usuário' : 'Novo Usuário' }}</flux:heading>
                <flux:subheading>Preencha os dados abaixo.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    <flux:label>Telefone</flux:label>
                    <flux:input wire:model="phone" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Senha {{ $editingUserId ? '(deixe em branco para não alterar)' : '' }}</flux:label>
                    <flux:input wire:model="password" type="password" />
                    <flux:error name="password" />
                </flux:field>
            </div>

            <flux:separator />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Órgãos Reguladores/Centralizadores</flux:label>
                    <flux:input wire:model="regulatory_bodies" />
                    <flux:error name="regulatory_bodies" />
                </flux:field>

                <flux:field>
                    <flux:label>Credenciais</flux:label>
                    <flux:input wire:model="credentials" />
                    <flux:error name="credentials" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Especialidades</flux:label>
                <flux:input wire:model="specialties" />
                <flux:error name="specialties" />
            </flux:field>

            <flux:field>
                <flux:label>Descrição</flux:label>
                <flux:textarea wire:model="description" />
                <flux:error name="description" />
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

    <flux:modal name="fingerprints-modal" class="md:w-[35rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Gerenciar Fingerprints</flux:heading>
                <flux:subheading>Visualizar e gerenciar impressões digitais do usuário: {{ $this->managingFingerprintsUser?->name }}</flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="flex gap-2">
                    <flux:field class="flex-1">
                        <flux:input wire:model="newFingerprint" placeholder="Digite novo fingerprint" />
                        <flux:error name="newFingerprint" />
                    </flux:field>
                    <flux:button wire:click="addFingerprint" variant="primary">Adicionar</flux:button>
                </div>

                <div class="rounded-lg border border-zinc-200 dark:border-zinc-800">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Fingerprint</flux:table.column>
                            <flux:table.column>Data</flux:table.column>
                            <flux:table.column>Ações</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @if ($this->managingFingerprintsUser && $this->managingFingerprintsUser->fingerprints->isNotEmpty())
                                @foreach ($this->managingFingerprintsUser->fingerprints as $fingerprint)
                                    <flux:table.row :key="$fingerprint->id">
                                        <flux:table.cell class="font-mono text-xs">{{ $fingerprint->fingerprint }}</flux:table.cell>
                                        <flux:table.cell class="text-xs">{{ $fingerprint->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                                        <flux:table.cell>
                                            <flux:button wire:click="deleteFingerprint({{ $fingerprint->id }})" variant="ghost" size="sm" icon="trash" color="red" square />
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            @else
                                <flux:table.row>
                                    <flux:table.cell colspan="3" class="text-center text-zinc-500 py-4">Nenhum fingerprint cadastrado.</flux:table.cell>
                                </flux:table.row>
                            @endif
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Fechar</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="permissions-user-modal" class="md:w-[25rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Gerenciar Permissões</flux:heading>
                <flux:subheading>Atribuir permissões para: {{ $this->managingPermissionsUser?->name }}</flux:subheading>
            </div>

            <div class="space-y-4">
                @forelse ($this->allPermissions as $permission)
                    <div class="flex items-center justify-between" wire:key="perm-user-{{ $permission->id }}">
                        <flux:label>{{ $permission->name }} <span class="text-xs text-zinc-500">({{ $permission->slug }})</span></flux:label>
                        <flux:switch 
                            wire:model="userPermissions.{{ $permission->id }}" 
                        />
                    </div>
                @empty
                    <flux:text class="text-center py-4">Nenhuma permissão cadastrada.</flux:text>
                @endforelse
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button wire:click="savePermissions" variant="primary">Salvar</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
