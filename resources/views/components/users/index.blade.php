<?php

use App\Models\DeviceFingerprint;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use App\Livewire\Abstracts\CrudComponent;
use Flux\Flux;
use Illuminate\Support\Str;

new class extends CrudComponent
{
    public string $modelClass = User::class;
    public string $pageTitle = 'Gerenciamento de Usuários';
    public string $newButtonTitle = 'Novo Usuário';
    public string $searchPlaceholder = 'Buscar usuários...';
    public string $editModalTitle = 'Editar Usuário';
    public string $createModalTitle = 'Novo Usuário';
    public string $deleteModalTitle = 'Excluir Usuário';
    public array $columnsToDisplay = [
        'name' => 'Nome',
        'email' => 'Email',
    ];

    public $managingFingerprintsUserId = null;
    public $managingPlansUserId = null;
    public $managingProfilesUserId = null;
    public $newFingerprint = '';
    public $userPlans = []; // [id => bool]
    public $newProfileName = '';
    public $newProfilePin = '';

    public function rules()
    {
        $rules = parent::rules();
        $rules['data.email'] .= '|'.Rule::unique('users', 'email')->ignore($this->editingId);

        if ($this->editingId) {
            $rules['data.password'] = 'nullable|string|min:8';
        }

        return $rules;
    }

    public function beforeSave()
    {
        if ($this->editingId === auth()->id() && ! $this->data['is_admin']) {
            $this->data['is_admin'] = true;
            Flux::toast(
                text: 'Você não pode remover seu próprio acesso administrativo.',
                variant: 'error',
            );

            return false;
        }
    }

    public function delete($id)
    {
        if ($id === auth()->id()) {
            Flux::toast(
                text: 'Você não pode excluir sua própria conta.',
                variant: 'error',
            );

            return;
        }

        parent::delete($id);
    }

    #[Computed]
    public function managingFingerprintsUser()
    {
        return $this->managingFingerprintsUserId ? User::find($this->managingFingerprintsUserId) : null;
    }

    #[Computed]
    public function managingProfilesUser()
    {
        return $this->managingProfilesUserId ? User::with('profiles')->find($this->managingProfilesUserId) : null;
    }

    public function manageProfiles($userId)
    {
        $this->managingProfilesUserId = $userId;
        $this->newProfileName = '';
        $this->newProfilePin = '';
        $this->modal('profiles-modal')->show();
    }

    public function addProfile()
    {
        $this->validate([
            'newProfileName' => 'required|string|max:255',
            'newProfilePin' => 'nullable|string|min:4|max:8',
        ]);

        $this->managingProfilesUser->profiles()->create([
            'name' => $this->newProfileName,
            'pin' => $this->newProfilePin ?: null,
        ]);

        $this->newProfileName = '';
        $this->newProfilePin = '';
    }

    public function deleteProfile($profileId)
    {
        \App\Models\UserProfile::findOrFail($profileId)->delete();
    }

    #[Computed]
    public function managingPlansUser()
    {
        return $this->managingPlansUserId ? User::find($this->managingPlansUserId) : null;
    }

    #[Computed]
    public function allPlans()
    {
        return \App\Models\Plan::all();
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

    public function managePlans($userId)
    {
        $user = User::with('plans')->findOrFail($userId);
        $this->managingPlansUserId = $user->id;
        $this->userPlans = $user->plans->pluck('id')->mapWithKeys(fn ($id) => [(string) $id => true])->toArray();
        $this->modal('plans-user-modal')->show();
    }

    public function savePlans()
    {
        if ($user = $this->managingPlansUser) {
            $planIds = array_keys(array_filter($this->userPlans));
            $user->plans()->sync($planIds);
        }

        $this->modal('plans-user-modal')->close();

        $this->reset(['managingPlansUserId', 'userPlans']);

        Flux::toast(
            text: 'Planos atualizados com sucesso.',
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
            <flux:heading size="xl" level="1">{{ $this->pageTitle }}</flux:heading>
            <flux:input type="search" name="search_{{ Str::random(10) }}" wire:model.live.debounce.300ms="q" placeholder="{{ $this->searchPlaceholder }}" icon="magnifying-glass" size="sm" class="w-64" autocomplete="off"  />
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">{{ $this->newButtonTitle }}</flux:button>
    </div>

    <flux:table :paginate="$this->items">
        <flux:table.columns>
            @foreach($this->columnsToDisplay as $column => $label)
                <flux:table.column>{{ $label }}</flux:table.column>
            @endforeach
            <flux:table.column>Admin</flux:table.column>
            <flux:table.column>Ações</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->items as $user)
                <flux:table.row :key="$user->id">
                    @foreach($this->columnsToDisplay as $column => $label)
                        <flux:table.cell>{{ $user->{$column} }}</flux:table.cell>
                    @endforeach
                    <flux:table.cell>
                        <flux:badge :color="$user->is_admin ? 'green' : 'gray'" size="sm">
                            {{ $user->is_admin ? 'Sim' : 'Não' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:button wire:click="manageProfiles({{ $user->id }})" variant="ghost" size="sm" icon="user-circle" square />
                            <flux:button wire:click="managePlans({{ $user->id }})" variant="ghost" size="sm" icon="credit-card" square />
                            <flux:button wire:click="manageFingerprints({{ $user->id }})" variant="ghost" size="sm" icon="key" square />
                            <flux:button wire:click="edit({{ $user->id }})" variant="ghost" size="sm" icon="pencil" square />
                            @if ($user->id !== auth()->id())
                                <flux:button wire:click="delete({{ $user->id }})" variant="ghost" size="sm" icon="trash" color="red" square />
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <x-utils.form-modal
        :isEditing="!!$editingId"
        :editModalTitle="$editModalTitle"
        :createModalTitle="$createModalTitle"
        class="md:w-[40rem]"
    />

    <flux:modal name="profiles-modal" class="md:w-[40rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Gerenciar Perfis</flux:heading>
                <flux:subheading>Perfis de acesso do usuário: {{ $this->managingProfilesUser?->name }}</flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="flex gap-2">
                    <flux:field class="flex-1">
                        <flux:input wire:model="newProfileName" placeholder="Nome do perfil" />
                        <flux:error name="newProfileName" />
                    </flux:field>
                    <flux:field class="w-36">
                        <flux:input wire:model="newProfilePin" type="password" placeholder="PIN (opcional)" />
                        <flux:error name="newProfilePin" />
                    </flux:field>
                    <flux:button wire:click="addProfile" variant="primary">Adicionar</flux:button>
                </div>

                <div class="rounded-lg border border-zinc-200 dark:border-zinc-800">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Nome</flux:table.column>
                            <flux:table.column>Avatar</flux:table.column>
                            <flux:table.column>PIN</flux:table.column>
                            <flux:table.column>Ações</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @if ($this->managingProfilesUser && $this->managingProfilesUser->profiles->isNotEmpty())
                                @foreach ($this->managingProfilesUser->profiles as $profile)
                                    <flux:table.row :key="$profile->id">
                                        <flux:table.cell>{{ $profile->name }}</flux:table.cell>
                                        <flux:table.cell>{{ $profile->avatar ?? '—' }}</flux:table.cell>
                                        <flux:table.cell>
                                            <flux:badge :color="$profile->getRawOriginal('pin') ? 'green' : 'gray'" size="sm">
                                                {{ $profile->getRawOriginal('pin') ? 'Sim' : 'Não' }}
                                            </flux:badge>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:button wire:click="deleteProfile({{ $profile->id }})" variant="ghost" size="sm" icon="trash" color="red" square />
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            @else
                                <flux:table.row>
                                    <flux:table.cell colspan="4" class="text-center text-zinc-500 py-4">Nenhum perfil cadastrado.</flux:table.cell>
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

    <flux:modal name="plans-user-modal" class="md:w-[25rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Gerenciar Planos</flux:heading>
                <flux:subheading>Atribuir planos para: {{ $this->managingPlansUser?->name }}</flux:subheading>
            </div>

            <div class="space-y-4">
                @forelse ($this->allPlans as $plan)
                    <div class="flex items-center justify-between" wire:key="plan-user-{{ $plan->id }}">
                        <flux:label>{{ $plan->name }} <span class="text-xs text-zinc-500">({{ $plan->price }} {{ $plan->currency }})</span></flux:label>
                        <flux:switch 
                            wire:model="userPlans.{{ $plan->id }}" 
                        />
                    </div>
                @empty
                    <flux:text class="text-center py-4">Nenhum plano cadastrado.</flux:text>
                @endforelse
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button wire:click="savePlans" variant="primary">Salvar</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
