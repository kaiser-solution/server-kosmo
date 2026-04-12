<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeviceWebLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test device login page is accessible.
     */
    public function test_device_login_page_is_accessible(): void
    {
        $response = $this->get('/device/login');

        $response->assertStatus(200);
    }

    /**
     * Test admin can login and is redirected to custom app url from env.
     */
    public function test_admin_is_redirected_to_custom_app_url_from_env(): void
    {
        config(['app.device_login_redirect_url' => 'custom-app://auth?token={token}']);

        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        Livewire::test('auth.device-login', ['fingerprint' => 'test-device-123'])
            ->set('email', 'admin@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirectContains('custom-app://auth?token=');
    }

    /**
     * Test non-admin cannot login to device.
     */
    public function test_non_admin_cannot_login_to_device(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        Livewire::test('auth.device-login', ['fingerprint' => 'test-device-123'])
            ->set('email', 'user@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    /**
     * Test fingerprint is required for login.
     */
    public function test_fingerprint_is_required_for_login(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        Livewire::test('auth.device-login', ['fingerprint' => ''])
            ->set('email', 'admin@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['fingerprint']);
    }
}
