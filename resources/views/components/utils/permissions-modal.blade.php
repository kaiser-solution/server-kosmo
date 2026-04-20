@props([
    'name' => 'permissions-modal',
    'title' => 'Gerenciar Permissões',
    'subtitle' => 'Selecione as permissões desejadas.',
    'permissions' => [],
    'wireModel' => 'selectedPermissions',
    'saveAction' => 'savePermissions',
    'show' => false,
])

<flux:modal :name="$name" class="md:w-[800px]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $title }}</flux:heading>
            @if($subtitle)
                <flux:subheading>{{ $subtitle }}</flux:subheading>
            @endif
        </div>

        @if($show)
            @if($slot->isNotEmpty())
                {{ $slot }}
            @else
                <flux:checkbox.group wire:model="{{ $wireModel }}" label="Permissões Disponíveis">
                    <div class="grid grid-cols-2 gap-4">
                        @foreach ($permissions as $permission)
                            <flux:checkbox label="{{ $permission->name }}" value="{{ (string) $permission->id }}" description="{{ $permission->description }}" />
                        @endforeach
                    </div>
                </flux:checkbox.group>
            @endif
        @endif

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost">{{ $slot->isNotEmpty() ? 'Fechar' : 'Cancelar' }}</flux:button>
            </flux:modal.close>
            @if($slot->isEmpty())
                <flux:button wire:click="{{ $saveAction }}" variant="primary">Salvar Permissões</flux:button>
            @endif
        </div>
    </div>
</flux:modal>
