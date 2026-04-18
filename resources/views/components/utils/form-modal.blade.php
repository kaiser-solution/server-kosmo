@props([
    'modalName' => 'crud-modal',
    'isEditing' => false,
    'editModalTitle' => '',
    'createModalTitle' => '',
    'modalSubtitle' => null,
    'fields' => [],
    'cancelButtonText' => 'Cancelar',
    'okButtonText' => 'Salvar',
])


<flux:modal name="crud-modal" class="md:w-[25rem]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $isEditing ? $this->editModalTitle : $this->createModalTitle }}</flux:heading>
            @if ($this->modalSubtitle)
                <flux:subheading>{{ $this->modalSubtitle }}</flux:subheading>
            @endif
        </div>

        @foreach($this->fields() as $name => $field)
            <flux:field>
                <flux:label>{{ $field['label'] }}</flux:label>

                <flux:input
                    type="{{ $field['type'] }}"
                    wire:model="data.{{ $name }}"
                />

                <flux:error name="data.{{ $name }}" />
            </flux:field>
        @endforeach
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">{{ $this->cancelButtonText }}</flux:button>
            </flux:modal.close>
            <flux:button wire:click="save" variant="primary">{{ $this->okButtonText }}</flux:button>
        </div>
    </div>
</flux:modal>
