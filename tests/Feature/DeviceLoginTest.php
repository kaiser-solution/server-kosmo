<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\DeviceFingerprint;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful device login and fingerprint association.
     */
    public function test_successful_device_login_associates_fingerprint(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'fingerprint' => 'pc-unique-id-123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('device_fingerprints', [
            'user_id' => $user->id,
            'fingerprint' => 'pc-unique-id-123',
        ]);
    }

    /**
     * Test successful login with existing fingerprint for the same user.
     */
    public function test_login_with_existing_fingerprint_for_same_user(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        DeviceFingerprint::create([
            'user_id' => $user->id,
            'fingerprint' => 'existing-id',
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'fingerprint' => 'existing-id',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    /**
     * Test login fails if fingerprint belongs to another user.
     */
    public function test_login_fails_if_fingerprint_belongs_to_another_user(): void
    {
        $otherUser = User::factory()->create(['is_admin' => false]);
        DeviceFingerprint::create([
            'user_id' => $otherUser->id,
            'fingerprint' => 'stolen-id',
        ]);

        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'fingerprint' => 'stolen-id',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'This device is already associated with another account.']);
    }

    public function test_device_login_filters_permissions_by_application(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
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

        // Login via API informando App 1
        $response1 = $this->postJson('/api/device-login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'fingerprint' => 'api-device-123',
            'app' => 'app1',
        ]);

        // Agora o login deve retornar TODAS as permissões de todos os planos do usuário,
        // independentemente da aplicação informada.
        $response1->assertStatus(200)
            ->assertJsonPath('user.permissions', ['p-app1', 'p-app2']);

        // Login via API informando App 2 - também deve retornar tudo
        $response2 = $this->postJson('/api/device-login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'fingerprint' => 'api-device-456',
            'app' => 'app2',
        ]);

        $response2->assertStatus(200)
            ->assertJsonPath('user.permissions', ['p-app1', 'p-app2']);
    }

    /**
     * Test login fails if user is an admin.
     */
    public function test_login_fails_if_user_is_admin(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'admin@example.com',
            'password' => 'password',
            'fingerprint' => 'some-id',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Administrators must login via management panel.']);
    }

    /**
     * Test login fails with wrong credentials.
     */
    public function test_login_fails_with_wrong_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
            'fingerprint' => 'some-id',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test login with only fingerprint for a registered device.
     */
    public function test_login_with_only_fingerprint_for_registered_device(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        DeviceFingerprint::create([
            'user_id' => $user->id,
            'fingerprint' => 'known-fingerprint',
        ]);

        $response = $this->postJson('/api/device-login', [
            'fingerprint' => 'known-fingerprint',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user'])
            ->assertJsonPath('user.id', $user->id);
    }

    /**
     * Test login fails with only fingerprint for an unregistered device.
     */
    public function test_login_fails_with_only_fingerprint_for_unregistered_device(): void
    {
        $response = $this->postJson('/api/device-login', [
            'fingerprint' => 'unknown-fingerprint',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Device not registered. Please login with your credentials first.']);
    }

    /**
     * Test login fails with fingerprint if user is an admin.
     */
    public function test_login_fails_with_fingerprint_if_user_is_admin(): void
    {
        $user = User::factory()->admin()->create();
        DeviceFingerprint::create([
            'user_id' => $user->id,
            'fingerprint' => 'known-fingerprint',
        ]);

        $response = $this->postJson('/api/device-login', [
            'fingerprint' => 'known-fingerprint',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Administrators must login via management panel.']);
    }

    /**
     * Test that the login response contains all required user data.
     */
    public function test_login_response_contains_all_required_user_data(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'is_admin' => false,
            'phone' => '123456789',
            'regulatory_bodies' => 'Body A',
            'credentials' => 'Cred 123',
            'specialties' => 'Spec X',
            'description' => 'Desc Y',
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'fingerprint' => 'test-fingerprint',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => 'user@example.com',
                    'phone' => '123456789',
                    'regulatory_bodies' => 'Body A',
                    'credentials' => 'Cred 123',
                    'specialties' => 'Spec X',
                    'description' => 'Desc Y',
                    'is_admin' => false,
                    'permissions' => [],
                ],
            ]);
    }

    /**
     * Test that the login response contains user permissions.
     */
    public function test_login_response_contains_user_permissions(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $app = Application::create(['name' => 'App 1', 'endpoint' => 'http://app1.test', 'namespace' => 'app1']);
        $permission = Permission::create(['name' => 'Test', 'slug' => 'test-permission', 'application_id' => $app->id]);
        $plan = Plan::create(['name' => 'Plan 1', 'price' => 10, 'currency' => 'BRL', 'application_id' => $app->id]);

        $plan->permissions()->attach($permission);
        $user->plans()->attach($plan);

        $response = $this->postJson('/api/device-login', [
            'email' => $user->email,
            'password' => 'password',
            'fingerprint' => 'test-fingerprint',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['permissions' => ['test-permission']]);
    }
}
