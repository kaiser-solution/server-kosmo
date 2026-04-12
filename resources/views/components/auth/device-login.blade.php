<?php

use App\Models\DeviceFingerprint;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.auth')] #[Title('App Login')] class extends Component
{
    #[Url]
    public string $fingerprint = '';

    public string $email = '';
    public string $password = '';

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
            'fingerprint' => 'required',
        ]);

        $user = User::where('email', $this->email)->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            $this->addError('email', __('As credenciais fornecidas estão incorretas.'));
            return;
        }

        // Restrição: apenas administradores (seguindo a regra atual do projeto)
        if (! $user->is_admin) {
             $this->addError('email', __('Somente administradores podem realizar login.'));
             return;
        }

        $existingFingerprint = DeviceFingerprint::where('fingerprint', $this->fingerprint)->first();

        if ($existingFingerprint && $existingFingerprint->user_id !== $user->id) {
            $this->addError('email', __('Este dispositivo já está associado a outra conta.'));
            return;
        }

        if (!$existingFingerprint) {
            DeviceFingerprint::create([
                'user_id' => $user->id,
                'fingerprint' => $this->fingerprint,
            ]);
        }

        $token = $user->createToken($this->fingerprint)->plainTextToken;
        $redirectUrl = str_replace('{token}', $token, config('app.device_login_redirect_url'));

        return redirect()->away($redirectUrl);
    }
};
?>

<div class="flex flex-col gap-6">
    <x-auth-header 
        :title="__('Login do Aplicativo')" 
        :description="__('Entre com seu e-mail e senha para ser redirecionado para o app')" 
    />

    <form wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:field>
            <flux:input
                wire:model="email"
                :label="__('Endereço de e-mail')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@exemplo.com"
            />
            <flux:error name="email" />
        </flux:field>

        <!-- Password -->
        <flux:field>
            <flux:input
                wire:model="password"
                :label="__('Senha')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Senha')"
                viewable
            />
            <flux:error name="password" />
        </flux:field>

        @if ($fingerprint)
            <div class="text-xs text-zinc-500">
                Dispositivo: <span class="font-mono text-xs opacity-70">{{ $fingerprint }}</span>
            </div>
            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Entrar e abrir App') }}
            </flux:button>
        @else
             <div class="rounded-md bg-red-50 p-3 text-sm text-red-600 dark:bg-red-900/20 dark:text-red-400">
                Aviso: Fingerprint não detectado. Este login deve ser iniciado a partir do aplicativo.
            </div>
            <flux:button variant="primary" type="submit" class="w-full" disabled>
                {{ __('Entrar e abrir App') }}
            </flux:button>
        @endif
    </form>
</div>