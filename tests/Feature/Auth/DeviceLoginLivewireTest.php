<?php

namespace Tests\Feature\Auth;

use App\Models\Application;
use App\Models\DeviceFingerprint;
use App\Models\Permission;
use App\Models\Plan;
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

    public function test_device_login_does_not_show_user_permissions_on_success(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $app = Application::create(['name' => 'App 1', 'endpoint' => 'http://app1.test', 'namespace' => 'app1']);
        $p1 = Permission::create(['name' => 'View Dashboard', 'slug' => 'view-dashboard', 'application_id' => $app->id]);
        $plan = Plan::create(['name' => 'Plan 1', 'price' => 10, 'currency' => 'BRL', 'application_id' => $app->id]);
        $plan->permissions()->attach($p1);
        $user->plans()->attach($plan);

        Livewire::withQueryParams(['fingerprint' => 'device-123', 'app' => 'app1'])
            ->test('auth.device-login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertDontSee('Permissões do seu Plano:')
            ->assertDontSee('View Dashboard');
    }

    public function test_device_login_filters_permissions_by_application(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $app1 = Application::create(['name' => 'App 1', 'endpoint' => 'http://app1.test', 'namespace' => 'app1']);
        $app2 = Application::create(['name' => 'App 2', 'endpoint' => 'http://app2.test', 'namespace' => 'app2']);

        $p1 = Permission::create(['name' => 'Permission App 1', 'slug' => 'p-app1', 'application_id' => $app1->id]);
        $p2 = Permission::create(['name' => 'Permission App 2', 'slug' => 'p-app2', 'application_id' => $app2->id]);

        $plan1 = Plan::create(['name' => 'Plan 1', 'price' => 10, 'currency' => 'BRL', 'application_id' => $app1->id]);
        $plan2 = Plan::create(['name' => 'Plan 2', 'price' => 20, 'currency' => 'BRL', 'application_id' => $app2->id]);

        $plan1->permissions()->attach($p1);
        $plan2->permissions()->attach($p2);

        $user->plans()->attach([$plan1->id, $plan2->id]);

        // Login informando App 1
        Livewire::withQueryParams(['fingerprint' => 'device-123', 'app' => 'app1'])
            ->test('auth.device-login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertDontSee('Permission App 1')
            ->assertDontSee('Permission App 2');

        // Login informando App 2
        Livewire::withQueryParams(['fingerprint' => 'device-456', 'app' => 'app2'])
            ->test('auth.device-login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertDontSee('Permission App 1')
            ->assertDontSee('Permission App 2');
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

        $app = Application::create(['name' => 'App 1', 'endpoint' => 'http://app1.test', 'namespace' => 'app1']);
        $permission1 = Permission::create(['name' => 'p1', 'slug' => 'p1', 'application_id' => $app->id]);
        $permission2 = Permission::create(['name' => 'p2', 'slug' => 'p2', 'application_id' => $app->id]);
        $plan = Plan::create(['name' => 'Plan 1', 'price' => 10, 'currency' => 'BRL', 'application_id' => $app->id]);
        $plan->permissions()->attach([$permission1->id, $permission2->id]);
        $user->plans()->attach($plan);

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
