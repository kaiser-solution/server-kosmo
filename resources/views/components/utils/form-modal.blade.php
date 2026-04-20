@props([
    'modalName' => 'crud-modal',
    'isEditing' => false,
    'editModalTitle' => '',
    'createModalTitle' => '',
    'modalSubtitle' => null,
    'fields' => [],
    'cancelButtonText' => 'Cancelar',
    'okButtonText' => 'Salvar',
    'class' => 'md:w-[25rem]'
])


<flux:modal :name="$modalName" :class="$class">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $isEditing ? $this->editModalTitle : $this->createModalTitle }}</flux:heading>
            @if ($this->modalSubtitle)
                <flux:subheading>{{ $this->modalSubtitle }}</flux:subheading>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($this->fields() as $name => $field)
                <flux:field :class="($field['type'] ?? '') === 'textarea' || ($field['type'] ?? '') === 'checkbox-group' ? 'md:col-span-2' : ''">
                    <flux:label>
                        {{ $field['label'] }}
                        @if(($field['type'] ?? '') === 'password' && $isEditing)
                             <span class="text-xs text-zinc-500 font-normal ml-1">(deixe em branco para não alterar)</span>
                        @endif
                    </flux:label>
    
                    @if(($field['type'] ?? 'text') === 'select')
                        <flux:select wire:model="data.{{ $name }}">
                            <flux:select.option value="">Selecione...</flux:select.option>
                            @foreach(($field['options'] ?? []) as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @elseif(($field['type'] ?? 'text') === 'checkbox-group')
                        <flux:checkbox.group wire:model="data.{{ $name }}">
                            @foreach(($field['options'] ?? []) as $value => $label)
                                <flux:checkbox value="{{ $value }}" label="{{ $label }}" />
                            @endforeach
                        </flux:checkbox.group>
                    @elseif(($field['type'] ?? 'text') === 'textarea')
                        <flux:textarea wire:model="data.{{ $name }}" />
                    @elseif(($field['type'] ?? 'text') === 'boolean')
                        <flux:switch wire:model="data.{{ $name }}" />
                    @else
    
                        <flux:input
                            type="{{ $field['type'] }}"
                            wire:model="data.{{ $name }}"
                        />
    
                    @endif
    
                    <flux:error name="data.{{ $name }}" />
                </flux:field>
            @endforeach
        </div>
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">{{ $this->cancelButtonText }}</flux:button>
            </flux:modal.close>
            <flux:button wire:click="save" variant="primary">{{ $this->okButtonText }}</flux:button>
        </div>
    </div>
</flux:modal>
