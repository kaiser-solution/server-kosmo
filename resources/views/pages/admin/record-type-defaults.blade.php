<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <flux:heading size="xl">{{ __('Padrões de Tipo de Registro') }}</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($recordTypes as $recordType)
                    <flux:card class="space-y-2">
                        <flux:heading>{{ $recordType->name }}</flux:heading>
                        <flux:text>{{ $recordType->slug }}</flux:text>

                        @php
                            $defaults = $recordType->schema['x-institutions'] ?? [];
                        @endphp

                        @if(!empty($defaults))
                            <div class="mt-2 text-xs text-zinc-500">
                                <flux:text>{{ __('Padrões') }}:</flux:text>
                                <ul class="list-disc list-inside">
                                    @foreach($defaults as $default)
                                        <li>{{ $default['name'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <flux:button wire:click="manageDefaults({{ $recordType->id }})" variant="ghost" size="sm" icon="adjustments-horizontal">
                            {{ __('Gerenciar Padrões') }}
                        </flux:button>
                    </flux:card>
                @endforeach
            </div>
        </div>

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
                                    <div class="flex items-center gap-2">
                                        <flux:checkbox wire:click="toggleEnabled({{ $index }})" :checked="($default['enabled_in_registration'] ?? false)" />
                                        <span>{{ $default['name'] }} ({{ $default['type'] }})</span>
                                    </div>
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
    </div>
</div>
