<?php

namespace Tests\Feature\Auth;

use App\Models\DeviceFingerprint;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use Tests\TestCase;

class DeviceLoginLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_login_page_can_be_rendered(): void
    {
        $response = $this->get(route('device.login', ['fingerprint' => 'test-id']));

        $response->assertOk();
        $response->assertSee('test-id');
    }

    public function test_device_login_requires_fingerprint_to_enable_button(): void
    {
        $response = $this->get(route('device.login'));

        $response->assertOk();
        $response->assertSee('Aviso: Fingerprint não detectado');
    }

    public function test_normal_users_can_authenticate_via_device_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        Livewire::withQueryParams(['fingerprint' => 'device-123'])
            ->test('auth.device-login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertSet('success', true)
            ->assertSee('Login realizado com sucesso!')
            ->assertSee('Você já pode fechar esta guia agora.');

        $this->assertDatabaseHas('device_fingerprints', [
            'user_id' => $user->id,
            'fingerprint' => 'device-123',
        ]);
    }

    public function test_admin_users_cannot_authenticate_via_device_login(): void
    {
        $user = User::factory()->admin()->create([
            'password' => bcrypt('password'),
        ]);

        Livewire::withQueryParams(['fingerprint' => 'device-123'])
            ->test('auth.device-login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email' => 'Administradores devem realizar login pelo painel de gerenciamento.']);

        $this->assertDatabaseMissing('device_fingerprints', [
            'fingerprint' => 'device-123',
        ]);
    }

    public function test_cannot_login_if_fingerprint_belongs_to_another_user(): void
    {
        $otherUser = User::factory()->create(['is_admin' => false]);
        DeviceFingerprint::create([
            'user_id' => $otherUser->id,
            'fingerprint' => 'shared-device',
        ]);

        $user = User::factory()->create(['is_admin' => false]);

        Livewire::withQueryParams(['fingerprint' => 'shared-device'])
            ->test('auth.device-login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    public function test_device_login_redirect_contains_encrypted_payload_with_user_data(): void
    {
        config(['app.device_login_redirect_url' => 'https://example.com/?payload={payload}']);

        $user = User::factory()->create([
            'is_admin' => false,
            'password' => bcrypt('password'),
            'phone' => '123',
        ]);

        $permission1 = Permission::create(['name' => 'p1', 'slug' => 'p1']);
        $permission2 = Permission::create(['name' => 'p2', 'slug' => 'p2']);
        $user->permissions()->attach([$permission1->id, $permission2->id]);

        $component = Livewire::withQueryParams(['fingerprint' => 'device-123'])
            ->test('auth.device-login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login');

        $redirectUrl = $component->get('redirectUrl');

        $this->assertStringContainsString('https://example.com/?payload=', $redirectUrl);

        $encryptedPayload = str_replace('https://example.com/?payload=', '', $redirectUrl);
        $decryptedPayload = json_decode(Crypt::decryptString($encryptedPayload), true);

        $this->assertArrayHasKey('token', $decryptedPayload);
        $this->assertArrayHasKey('user', $decryptedPayload);
        $this->assertEquals($user->email, $decryptedPayload['user']['email']);
        $this->assertEquals('123', $decryptedPayload['user']['phone']);
        $this->assertEquals(['p1', 'p2'], $decryptedPayload['user']['permissions']);
    }

    public function test_fingerprint_is_required_for_login(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        Livewire::withQueryParams(['fingerprint' => ''])
            ->test('auth.device-login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['fingerprint' => 'required']);
    }
}
