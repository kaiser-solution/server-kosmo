<?php

namespace Tests\Feature;

use App\Models\DeviceFingerprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that an admin user can access the user management page.
     */
    public function test_admin_can_access_user_management(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get('/users');

        $response->assertStatus(200);
        $response->assertSee('Gerenciamento de Usuários');
    }

    /**
     * Test that a non-admin user cannot access the user management page.
     */
    public function test_non_admin_cannot_access_user_management(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)
            ->get('/users');

        $response->assertStatus(403);
    }

    /**
     * Test that a guest cannot access the user management page.
     */
    public function test_guest_cannot_access_user_management(): void
    {
        $response = $this->get('/users');

        $response->assertRedirect('/login');
    }

    /**
     * Test that an admin can add a fingerprint to a user.
     */
    public function test_admin_can_add_fingerprint_to_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        Livewire::actingAs($admin)
            ->test('users.index')
            ->call('manageFingerprints', $user->id)
            ->set('newFingerprint', 'test-manual-fingerprint')
            ->call('addFingerprint')
            ->assertHasNoErrors()
            ->assertSet('newFingerprint', '');

        $this->assertDatabaseHas('device_fingerprints', [
            'user_id' => $user->id,
            'fingerprint' => 'test-manual-fingerprint',
        ]);
    }

    /**
     * Test that an admin can delete a fingerprint.
     */
    public function test_admin_can_delete_fingerprint(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $fingerprint = DeviceFingerprint::create([
            'user_id' => $user->id,
            'fingerprint' => 'fingerprint-to-delete',
        ]);

        Livewire::actingAs($admin)
            ->test('users.index')
            ->call('manageFingerprints', $user->id)
            ->call('deleteFingerprint', $fingerprint->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('device_fingerprints', [
            'id' => $fingerprint->id,
        ]);
    }

    /**
     * Test that a fingerprint must be unique.
     */
    public function test_fingerprint_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        DeviceFingerprint::create([
            'user_id' => $user1->id,
            'fingerprint' => 'duplicate-fingerprint',
        ]);

        Livewire::actingAs($admin)
            ->test('users.index')
            ->call('manageFingerprints', $user2->id)
            ->set('newFingerprint', 'duplicate-fingerprint')
            ->call('addFingerprint')
            ->assertHasErrors(['newFingerprint' => 'unique']);
    }
}
