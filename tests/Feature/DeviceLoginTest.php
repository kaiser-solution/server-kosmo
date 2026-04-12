<?php

namespace Tests\Feature;

use App\Models\DeviceFingerprint;
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
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'admin@example.com',
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
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        DeviceFingerprint::create([
            'user_id' => $user->id,
            'fingerprint' => 'existing-id',
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'admin@example.com',
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
        $otherUser = User::factory()->admin()->create();
        DeviceFingerprint::create([
            'user_id' => $otherUser->id,
            'fingerprint' => 'stolen-id',
        ]);

        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'admin@example.com',
            'password' => 'password',
            'fingerprint' => 'stolen-id',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'This device is already associated with another account.']);
    }

    /**
     * Test login fails if user is not an admin.
     */
    public function test_login_fails_if_user_is_not_admin(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'fingerprint' => 'some-id',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Only administrators can login.']);
    }

    /**
     * Test login fails with wrong credentials.
     */
    public function test_login_fails_with_wrong_credentials(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'admin@example.com',
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
        $user = User::factory()->admin()->create();
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
     * Test login fails with fingerprint if user was revoked admin status.
     */
    public function test_login_fails_with_fingerprint_if_user_no_longer_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        DeviceFingerprint::create([
            'user_id' => $user->id,
            'fingerprint' => 'known-fingerprint',
        ]);

        $response = $this->postJson('/api/device-login', [
            'fingerprint' => 'known-fingerprint',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Only administrators can login.']);
    }
}
