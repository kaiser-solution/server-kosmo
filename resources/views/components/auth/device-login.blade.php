<?php

use App\Models\DeviceFingerprint;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
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

    public bool $success = false;
    public string $redirectUrl = '';

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

        // Restrição: apenas usuários normais (administradores devem usar o painel web)
        if ($user->is_admin) {
             $this->addError('email', __('Administradores devem realizar login pelo painel de gerenciamento.'));
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

        $payload = Crypt::encryptString(json_encode([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'regulatory_bodies' => $user->regulatory_bodies,
                'credentials' => $user->credentials,
                'specialties' => $user->specialties,
                'description' => $user->description,
                'permissions' => $user->permissions->pluck('slug')->toArray(),
            ],
        ]));
        // dd($payload);

        $this->redirectUrl = str_replace(['{token}', '{payload}'], [$token, $payload], config('app.device_login_redirect_url'));
        $this->success = true;
    }
};
?>

<div class="flex flex-col gap-6">
    <x-auth-header 
        :title="__('Login do Aplicativo')" 
        :description="__('Entre com seu e-mail e senha para ser redirecionado para o app')" 
    />

    @if ($success)
        <div class="flex flex-col gap-4 text-center"
            x-data="{
                count: 2,
                redirected: false,
                redirect() {
                    if (this.redirected) return;
                    this.redirected = true;
                    window.location.href = '{{ $redirectUrl }}';
                }
            }"
            x-init="let timer = setInterval(() => {
                if (count > 0) {
                    count--;
                } else {
                    clearInterval(timer);
                    redirect();
                }
            }, 1000)"
        >
            <div class="rounded-md bg-green-50 p-4 text-sm text-green-600 dark:bg-green-900/20 dark:text-green-400">
                <p class="font-bold text-lg mb-2">{{ __('Login realizado com sucesso!') }}</p>
                <p>{{ __('O aplicativo deve abrir automaticamente.') }}</p>
                <p>{{ __('Você já pode fechar esta guia agora.') }}</p>
                <p class="mt-4 text-xs opacity-70" x-show="!redirected && count > 0">{{ __('Redirecionando em') }} <span x-text="count"></span>s...</p>
                <p class="mt-4 text-xs opacity-70" x-show="!redirected && count === 0">{{ __('Redirecionando agora...') }}</p>
                <p class="mt-4 text-xs opacity-70" x-show="redirected">{{ __('Abrindo o aplicativo...') }}</p>
            </div>

            <flux:button variant="primary" x-on:click="redirect()" x-bind:class="count > 0 ? 'opacity-50' : ''">
                {{ __('Abrir Aplicativo Agora') }}
            </flux:button>
        </div>
    @else
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
    @endif
</div>
