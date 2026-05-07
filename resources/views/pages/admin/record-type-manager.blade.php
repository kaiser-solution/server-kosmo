<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">{{ __($pageTitle) }}</flux:heading>
            <flux:button wire:click="create" variant="primary" class="w-max">{{ __($newButtonTitle) }}</flux:button>
        </div>

        <flux:input wire:model.live.debounce.300ms="q" :placeholder="__($searchPlaceholder)" icon="magnifying-glass" />

        <flux:table>
            <flux:table.columns>
                @foreach($columnsToDisplay as $column => $label)
                    <flux:table.column>{{ __($label) }}</flux:table.column>
                @endforeach
                <flux:table.column>{{ __($actionsColumnTitle) }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->items() as $item)
                <flux:table.row>
                    @foreach($columnsToDisplay as $column => $label)
                        <flux:table.cell>
                            @if(isset($item->$column))
                                @if(is_bool($item->$column))
                                    {{ $item->$column ? 'Sim' : 'Não' }}
                                @else
                                    {{ $item->$column }}
                                @endif
                            @endif
                        </flux:table.cell>
                    @endforeach
                    <flux:table.cell>
                        <flux:button wire:click="edit({{ $item->id }})" variant="ghost" size="sm">{{ __('Editar') }}</flux:button>
                        <flux:button wire:click="delete({{ $item->id }})" variant="ghost" color="red" size="sm">{{ __('Excluir') }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $this->items()->links() }}
        </div>

        <flux:modal name="crud-modal">
            <div class="p-6">
                <flux:heading size="lg">{{ $editingId ? __($editModalTitle) : __($createModalTitle) }}</flux:heading>
                <div class="grid gap-4 mt-4">
                    @foreach($this->fields() as $field => $config)
                        @if($config['type'] === 'checkbox')
                            <flux:checkbox wire:model="data.{{ $field }}" :label="__($config['label'] ?? $field)" />
                        @elseif($config['type'] === 'select')
                            <flux:select wire:model="data.{{ $field }}" :label="__($config['label'] ?? $field)">
                                @foreach($config['options'] ?? [] as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @elseif($config['type'] === 'boolean')
                             <flux:checkbox wire:model="data.{{ $field }}" :label="__($config['label'] ?? $field)" />
                        @else
                            <flux:input wire:model="data.{{ $field }}" :label="__($config['label'] ?? $field)" />
                        @endif
                    @endforeach
                    <flux:button wire:click="save" variant="primary">{{ __($okButtonText) }}</flux:button>
                </div>
            </div>
        </flux:modal>
    </div>
</div>
