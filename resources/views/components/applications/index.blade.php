<?php

use App\Livewire\Abstracts\CrudComponent;
use App\Models\AppConfig;
use App\Models\Application;
use App\Models\Permission;
use App\Models\Record;
use App\Models\RecordPattern;
use App\Models\RecordType;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
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

    public ?int $configuringAppId = null;

    public string $configDisplayName = '';

    public string $configPrimaryColor = '#000000';

    public string $configSecondaryColor = '#000000';

    public string $configDefaultCurrency = 'BRL';

    public array $configCategories = [];

    public string $newCategoryName = '';

    public string $newCategoryColor = '#6366f1';

    public ?int $managingRecordTypesId = null;

    public ?int $managingDefaultsRecordTypeId = null;

    public array $currentRecordTypeDefaults = [];

    public string $newDefaultName = '';

    public ?float $newDefaultValue = null;

    public string $newDefaultType = 'income';

    public string $newDefaultCategory = '';

    public ?int $newDefaultDueDay = null;

    public string $newRecordTypeName = '';

    public string $newRecordTypeSlug = '';

    public string $newRecordTypeDescription = '';

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
            'newPermissionSlug' => 'required|string|max:255',
        ]);

        $existing = Permission::where('application_id', $this->managingPermissionsId)
            ->where('slug', $this->newPermissionSlug)
            ->exists();

        if ($existing) {
            Flux::toast(
                variant: 'warning',
                heading: 'Aviso',
                text: 'Esta permissão já está cadastrada para esta aplicação.',
            );

            return;
        }

        Permission::create([
            'name' => $this->newPermissionName,
            'slug' => $this->newPermissionSlug,
            'application_id' => $this->managingPermissionsId,
        ]);

        $this->reset(['newPermissionName', 'newPermissionSlug']);

        Flux::toast(
            variant: 'success',
            heading: 'Sucesso',
            text: 'Permissão cadastrada com sucesso.',
        );
    }

    public function deletePermission($id)
    {
        Permission::find($id)?->delete();
    }

    public function manageConfig($id)
    {
        $this->configuringAppId = $id;
        $config = AppConfig::where('application_id', $id)->first();

        $this->configDisplayName = $config?->display_name ?? '';
        $this->configPrimaryColor = $config?->primary_color ?? '#000000';
        $this->configSecondaryColor = $config?->secondary_color ?? '#000000';
        $this->configDefaultCurrency = $config?->default_currency ?? 'BRL';

        $categories = $config?->categories ?? [];
        $this->configCategories = is_array($categories) ? $categories : (json_decode($categories, true) ?? []);
        $this->newCategoryName = '';
        $this->newCategoryColor = '#6366f1';

        $this->modal('config-modal')->show();
    }

    public function saveConfig()
    {
        $this->validate([
            'configDisplayName' => 'nullable|string|max:255',
            'configPrimaryColor' => 'nullable|string|max:7',
            'configSecondaryColor' => 'nullable|string|max:7',
            'configDefaultCurrency' => 'nullable|string|max:3',
            'configCategories' => 'nullable|array',
            'configCategories.*.name' => 'required|string|max:50',
            'configCategories.*.color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        AppConfig::updateOrCreate(
            ['application_id' => $this->configuringAppId],
            [
                'display_name' => $this->configDisplayName ?: null,
                'primary_color' => $this->configPrimaryColor,
                'secondary_color' => $this->configSecondaryColor,
                'default_currency' => $this->configDefaultCurrency,
                'categories' => $this->configCategories,
            ]
        );

        $application = Application::find($this->configuringAppId);
        if ($application) {
            Cache::forget("app_config_by_namespace_{$application->namespace}");
        }

        $this->modal('config-modal')->close();
    }

    public function restoreDefaultCategories()
    {
        $this->configCategories = [
            ['name' => 'Assinaturas',    'color' => '#6366f1'],
            ['name' => 'Cartões',        'color' => '#ec4899'],
            ['name' => 'Casa',           'color' => '#f59e0b'],
            ['name' => 'Comunicação',    'color' => '#06b6d4'],
            ['name' => 'Manutenção',     'color' => '#64748b'],
            ['name' => 'Material Tattoo', 'color' => '#0ea5e9'],
            ['name' => 'MEI / Impostos', 'color' => '#ef4444'],
            ['name' => 'Saúde',          'color' => '#10b981'],
            ['name' => 'Segurança',      'color' => '#d97706'],
            ['name' => 'Retiradas',      'color' => '#06d977'],
            ['name' => 'Terreno',        'color' => '#78716c'],
            ['name' => 'Transporte',     'color' => '#3b82f6'],
            ['name' => 'Outros',         'color' => '#718096'],
        ];
    }

    public function addCategory(): void
    {
        $this->validate([
            'newCategoryName' => 'required|string|max:50',
            'newCategoryColor' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $name = trim($this->newCategoryName);

        $exists = collect($this->configCategories)->contains(fn ($c) => strtolower($c['name']) === strtolower($name));
        if ($exists) {
            $this->addError('newCategoryName', 'Esta categoria já existe.');

            return;
        }

        $categories = $this->configCategories;
        $categories[] = ['name' => $name, 'color' => $this->newCategoryColor];

        usort($categories, function ($a, $b) {
            if ($a['name'] === 'Outros') {
                return 1;
            }
            if ($b['name'] === 'Outros') {
                return -1;
            }

            return strcmp($a['name'], $b['name']);
        });

        $this->configCategories = $categories;

        $this->newCategoryName = '';
        $this->newCategoryColor = '#6366f1';
    }

    public function removeCategory(int $index): void
    {
        $categories = $this->configCategories;
        array_splice($categories, $index, 1);
        $this->configCategories = $categories;
    }

    public function manageRecordTypes($id)
    {
        $this->managingRecordTypesId = $id;
        $this->reset(['newRecordTypeName', 'newRecordTypeSlug', 'newRecordTypeDescription']);
        $this->modal('record-types-modal')->show();
    }

    public function addPattern($patternId)
    {
        $pattern = RecordPattern::with('recordTypes')->find($patternId);
        if ($pattern) {
            $application = Application::find($this->managingRecordTypesId);

            $patternTypeIds = $pattern->recordTypes->pluck('id')->toArray();
            $existingTypeIds = $application->recordTypes->pluck('id')->toArray();

            $newTypeIds = array_diff($patternTypeIds, $existingTypeIds);
            $duplicateCount = count($patternTypeIds) - count($newTypeIds);

            if (count($newTypeIds) > 0) {
                $application->recordTypes()->syncWithoutDetaching($newTypeIds);
            }

            if ($duplicateCount > 0) {
                Flux::toast(
                    variant: 'warning',
                    heading: 'Aviso',
                    text: "Alguns itens ({$duplicateCount}) já estavam cadastrados.",
                );
            }

            if (count($newTypeIds) === count($patternTypeIds)) {
                Flux::toast(
                    variant: 'success',
                    heading: 'Sucesso',
                    text: 'Todos os tipos de registro foram cadastrados com sucesso.',
                );
            } elseif (count($newTypeIds) > 0) {
                Flux::toast(
                    variant: 'success',
                    heading: 'Sucesso',
                    text: 'Novos tipos de registro foram adicionados.',
                );
            }
        }
    }

    public function addDefaultRecordTypes()
    {
        // This method is now obsolete if we use patterns,
        // but let's keep it for compatibility or refactor it.
        // For now, let's just make it do nothing or remove it.
    }

    public function manageDefaults($recordTypeId)
    {
        $this->managingDefaultsRecordTypeId = $recordTypeId;
        $recordType = RecordType::find($recordTypeId);
        $schema = $recordType->schema ?? [];
        $this->currentRecordTypeDefaults = $schema['x-institutions'] ?? [];

        $this->modal('record-type-defaults-modal')->show();
    }

    public function addDefault()
    {
        $this->validate([
            'newDefaultName' => 'required|string|max:255',
            'newDefaultValue' => 'nullable|numeric',
            'newDefaultType' => 'required|string',
            'newDefaultCategory' => 'nullable|string',
        ]);

        $this->currentRecordTypeDefaults[] = [
            'name' => $this->newDefaultName,
            'defaultVal' => $this->newDefaultValue,
            'type' => $this->newDefaultType,
            'category' => $this->newDefaultCategory,
        ];

        $this->reset(['newDefaultName', 'newDefaultValue', 'newDefaultType', 'newDefaultCategory']);
    }

    public function removeDefault(int $index)
    {
        $defaults = $this->currentRecordTypeDefaults;
        array_splice($defaults, $index, 1);
        $this->currentRecordTypeDefaults = $defaults;
    }

    public function saveDefaults()
    {
        $recordType = RecordType::find($this->managingDefaultsRecordTypeId);
        $schema = $recordType->schema ?? [];
        $schema['x-institutions'] = $this->currentRecordTypeDefaults;

        $recordType->update(['schema' => $schema]);

        $this->modal('record-type-defaults-modal')->close();
    }

    public function generateRecordsFromDefaults()
    {
        $recordType = RecordType::find($this->managingDefaultsRecordTypeId);
        $defaults = $recordType->schema['x-institutions'] ?? [];

        foreach ($defaults as $default) {
            Record::create([
                'application_id' => $recordType->application_id,
                'record_type_id' => $recordType->id,
                'payload' => [
                    'inst' => $default['name'],
                    'val' => $default['defaultVal'] ?? 0,
                    'type' => $default['type'],
                    'cat' => $default['category'],
                ],
            ]);
        }

        $this->modal('record-type-defaults-modal')->close();
    }

    public function addRecordType()
    {
        $this->validate([
            'newRecordTypeName' => 'required|string|max:255',
            'newRecordTypeSlug' => 'required|string|max:100',
            'newRecordTypeDescription' => 'nullable|string',
        ]);

        $recordType = RecordType::firstOrCreate(
            ['slug' => $this->newRecordTypeSlug],
            [
                'name' => $this->newRecordTypeName,
                'description' => $this->newRecordTypeDescription ?: null,
                'status' => 'active',
            ]
        );

        $application = Application::find($this->managingRecordTypesId);

        $isAlreadyAttached = $application->recordTypes()->where('record_type_id', $recordType->id)->exists();

        if ($isAlreadyAttached) {
            Flux::toast(
                variant: 'warning',
                heading: 'Aviso',
                text: 'Este tipo de registro já está associado a esta aplicação.',
            );

            return;
        }

        $application->recordTypes()->syncWithoutDetaching([$recordType->id]);

        $this->reset(['newRecordTypeName', 'newRecordTypeSlug', 'newRecordTypeDescription']);

        Flux::toast(
            variant: 'success',
            heading: 'Sucesso',
            text: 'Tipo de registro adicionado com sucesso.',
        );
    }

    public function toggleRecordType($id)
    {
        $type = RecordType::find($id);
        if ($type) {
            $newStatus = $type->status === 'active' ? 'inactive' : 'active';
            $type->update(['status' => $newStatus]);
        }
    }

    public function detachRecordType($id)
    {
        $application = Application::find($this->managingRecordTypesId);
        $application->recordTypes()->detach($id);
    }

    public function deleteRecordType($id)
    {
        RecordType::find($id)?->delete();
    }

    #[Computed]
    public function currentRecordTypes()
    {
        if (! $this->managingRecordTypesId) {
            return [];
        }

        return Application::find($this->managingRecordTypesId)->recordTypes;
    }

    #[Computed]
    public function hasRecords()
    {
        if (! $this->managingRecordTypesId) {
            return false;
        }

        return Record::where('application_id', $this->managingRecordTypesId)->exists();
    }

    #[Computed]
    public function availablePatterns()
    {
        // dd(RecordPattern::all());
        return RecordPattern::all();
    }

    #[Computed]
    public function currentApplicationPermissions()
    {
        if (! $this->managingPermissionsId) {
            return [];
        }

        return Permission::where('application_id', $this->managingPermissionsId)->get();
    }

    #[Computed]
    public function availablePermissions()
    {
        return Permission::all();
    }

    #[Computed]
    public function items()
    {
        return $this->modelClass::query()
            ->withCount('permissions')
            ->when($this->q, fn ($q) => $q->where('name', 'like', "%{$this->q}%")
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
                            <flux:button wire:click="manageRecordTypes({{ $item->id }})" variant="ghost" size="sm" icon="rectangle-stack" square />
                            <flux:button wire:click="manageConfig({{ $item->id }})" variant="ghost" size="sm" icon="cog-6-tooth" square />
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

    <flux:modal name="config-modal" class="md:w-[500px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Configurações da Aplicação</flux:heading>
                <flux:subheading>Personalize a aparência e comportamento do frontend.</flux:subheading>
            </div>

            @if($configuringAppId)
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Nome de Exibição</flux:label>
                        <flux:input wire:model="configDisplayName" placeholder="Ex: Meu App de Finanças" />
                        <flux:error name="configDisplayName" />
                    </flux:field>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Cor Primária</flux:label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model="configPrimaryColor" class="h-9 w-12 cursor-pointer rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800" />
                                <flux:input wire:model="configPrimaryColor" placeholder="#000000" class="font-mono" />
                            </div>
                            <flux:error name="configPrimaryColor" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Cor Secundária</flux:label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model="configSecondaryColor" class="h-9 w-12 cursor-pointer rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800" />
                                <flux:input wire:model="configSecondaryColor" placeholder="#000000" class="font-mono" />
                            </div>
                            <flux:error name="configSecondaryColor" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Moeda Padrão</flux:label>
                        <flux:select wire:model="configDefaultCurrency">
                            <flux:select.option value="BRL">BRL — Real Brasileiro</flux:select.option>
                            <flux:select.option value="USD">USD — Dólar Americano</flux:select.option>
                            <flux:select.option value="EUR">EUR — Euro</flux:select.option>
                            <flux:select.option value="GBP">GBP — Libra Esterlina</flux:select.option>
                        </flux:select>
                        <flux:error name="configDefaultCurrency" />
                    </flux:field>
                </div>

                <flux:separator />

                <div>
                    <div class="flex justify-between items-center mb-3">
                        <flux:heading size="sm">🎨 Categorias e Cores</flux:heading>
                        <flux:button wire:click="restoreDefaultCategories" variant="ghost" size="sm" icon="arrow-path">Restaurar Padrões</flux:button>
                    </div>

                    <div class="space-y-2 mb-3 max-h-48 overflow-y-auto">
                        @forelse($configCategories as $i => $cat)
                            <div class="flex items-center gap-2" wire:key="category-{{ $i }}-{{ $cat['name'] }}">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium text-white flex-1" style="background:{{ $cat['color'] }}">
                                    {{ $cat['name'] }}
                                </span>
                                <input type="color" wire:model.live="configCategories.{{ $i }}.color" class="h-7 w-9 cursor-pointer rounded border border-zinc-300 dark:border-zinc-600" />
                                <flux:button wire:click="removeCategory({{ $i }})" variant="ghost" size="sm" icon="trash" color="red" square />
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">Nenhuma categoria cadastrada.</p>
                        @endforelse
                    </div>

                    <div class="flex gap-2 items-start">
                        <flux:field class="flex-1">
                            <flux:input wire:model.live="newCategoryName" wire:keydown.enter="addCategory" placeholder="Nome da categoria" size="sm" />
                            <flux:error name="newCategoryName" />
                        </flux:field>
                        <input type="color" wire:model="newCategoryColor" class="h-9 w-10 cursor-pointer rounded border border-zinc-300 dark:border-zinc-600 mt-0" />
                        <flux:button wire:click="addCategory" variant="ghost" size="sm" icon="plus">Adicionar</flux:button>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="saveConfig" variant="primary">Salvar Configurações</flux:button>
                </div>
            @endif
        </div>
    </flux:modal>

    <flux:modal name="record-type-defaults-modal" class="md:w-[600px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Gerenciar Padrões</flux:heading>
                <flux:subheading>Gerencie os padrões (x-institutions) para este tipo de registro.</flux:subheading>
            </div>

            @if($managingDefaultsRecordTypeId)
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-2">
                        <flux:input wire:model="newDefaultName" placeholder="Nome" size="sm" />
                        <flux:input wire:model="newDefaultValue" placeholder="Valor (opcional)" size="sm" />
                        <flux:input wire:model="newDefaultType" placeholder="Tipo (ex: income)" size="sm" />
                        <flux:input wire:model="newDefaultCategory" placeholder="Categoria" size="sm" />
                        <flux:button wire:click="addDefault" variant="primary" size="sm" icon="plus" class="col-span-2">Adicionar Padrão</flux:button>
                    </div>

                    <flux:separator />

                    <div class="space-y-2">
                        @forelse($currentRecordTypeDefaults as $index => $default)
                            <div class="flex items-center justify-between p-2 border rounded">
                                <span>{{ $default['name'] }} ({{ $default['type'] }})</span>
                                <flux:button wire:click="removeDefault({{ $index }})" variant="ghost" size="sm" icon="trash" color="red" square />
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">Nenhum padrão cadastrado.</p>
                        @endforelse
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="generateRecordsFromDefaults" variant="outline">Gerar Registros</flux:button>
                    <flux:button wire:click="saveDefaults" variant="primary">Salvar</flux:button>
                </div>
            @endif
        </div>
    </flux:modal>

    <flux:modal name="record-types-modal" class="md:w-[700px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Tipos de Registro</flux:heading>
                <flux:subheading>Defina quais tipos de registro esta aplicação pode receber.</flux:subheading>
            </div>

            @if($managingRecordTypesId)
                <div class="space-y-4">
                    @if(!$this->hasRecords && $this->availablePatterns->isNotEmpty())
                        <div class="flex items-center gap-2">
                            <flux:dropdown>
                                <flux:button variant="subtle" size="sm" icon:trailing="chevron-down">
                                    {{ __('Adicionar Padrão') }}
                                </flux:button>

                                <flux:menu>
                                    @foreach($this->availablePatterns as $pattern)
                                        <flux:menu.item wire:click="addPattern({{ $pattern->id }})" icon="plus">
                                            {{ $pattern->name }}
                                        </flux:menu.item>
                                    @endforeach
                                </flux:menu>
                            </flux:dropdown>
                        </div>

                        <flux:separator />
                    @endif

                    <div class="flex gap-2 items-start">
                        <flux:field>
                            <flux:input wire:model="newRecordTypeName" placeholder="Nome (ex: Despesa)" size="sm" />
                            <flux:error name="newRecordTypeName" />
                        </flux:field>
                        <flux:field>
                            <flux:input wire:model="newRecordTypeSlug" placeholder="Slug (ex: expense)" size="sm" />
                            <flux:error name="newRecordTypeSlug" />
                        </flux:field>
                        <flux:field>
                            <flux:input wire:model="newRecordTypeDescription" placeholder="Descrição (opcional)" size="sm" />
                        </flux:field>
                        <flux:button wire:click="addRecordType" variant="primary" size="sm" icon="plus">Adicionar</flux:button>
                    </div>

                    <flux:separator />

                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Nome</flux:table.column>
                            <flux:table.column>Slug</flux:table.column>
                            <flux:table.column>Descrição</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column align="end">Ações</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @forelse($this->currentRecordTypes as $recordType)
                                <flux:table.row :key="$recordType->id">
                                    <flux:table.cell>{{ $recordType->name }}</flux:table.cell>
                                    <flux:table.cell><code>{{ $recordType->slug }}</code></flux:table.cell>
                                    <flux:table.cell>{{ $recordType->description ?? '—' }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge :color="$recordType->status === 'active' ? 'green' : 'zinc'" size="sm" inset="top bottom">
                                            {{ $recordType->status === 'active' ? 'Ativo' : 'Inativo' }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        <div class="flex items-center gap-1 justify-end">
                                            <flux:button wire:click="manageDefaults({{ $recordType->id }})" variant="ghost" size="sm" icon="adjustments-horizontal" square />
                                            <flux:button wire:click="toggleRecordType({{ $recordType->id }})" variant="ghost" size="sm" :icon="$recordType->status === 'active' ? 'eye-slash' : 'eye'" square />
                                            <flux:button wire:click="detachRecordType({{ $recordType->id }})" variant="ghost" size="sm" icon="x-mark" color="red" square />
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="5" class="text-center text-zinc-500 py-4 italic">
                                        {{ __('Nenhum tipo de registro cadastrado.') }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>

                </div>
            @endif

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Fechar</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

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
