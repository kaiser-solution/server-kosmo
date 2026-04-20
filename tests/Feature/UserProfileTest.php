<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a user can have multiple profiles.
     */
    public function test_user_can_have_multiple_profiles(): void
    {
        $user = User::factory()->create();

        UserProfile::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->profiles);
    }

    /**
     * Test that a profile belongs to a user.
     */
    public function test_profile_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $profile->user->id);
    }

    /**
     * Test that a profile inherits permissions from the parent user's plans.
     */
    public function test_profile_inherits_permissions_from_user_plans(): void
    {
        $user = User::factory()->create();

        $plan = Plan::factory()->create();
        $permission = Permission::factory()->create(['slug' => 'edit-content']);
        $plan->permissions()->attach($permission);
        $user->plans()->attach($plan);

        $profile = UserProfile::factory()->create(['user_id' => $user->id]);

        $this->assertContains('edit-content', $profile->getPermissions());
    }

    /**
     * Test that a profile with no user plans returns empty permissions.
     */
    public function test_profile_with_no_plans_returns_empty_permissions(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);

        $this->assertEmpty($profile->getPermissions());
    }

    /**
     * Test login with a valid profile_id returns profile data.
     */
    public function test_device_login_with_valid_profile_returns_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $profile = UserProfile::factory()->create([
            'user_id' => $user->id,
            'name' => 'Perfil Kids',
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'fingerprint' => 'fp-001',
            'profile_id' => $profile->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.profile.id', $profile->id)
            ->assertJsonPath('user.profile.name', 'Perfil Kids');
    }

    /**
     * Test login without profile_id returns null profile.
     */
    public function test_device_login_without_profile_returns_null_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'fingerprint' => 'fp-002',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.profile', null);
    }

    /**
     * Test login with a profile that does not belong to the user returns 404.
     */
    public function test_device_login_with_profile_of_another_user_returns_404(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $otherUser = User::factory()->create();
        $profile = UserProfile::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'fingerprint' => 'fp-003',
            'profile_id' => $profile->id,
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test login with a PIN-protected profile and correct PIN succeeds.
     */
    public function test_device_login_with_correct_pin_succeeds(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $profile = UserProfile::factory()->withPin('1234')->create([
            'user_id' => $user->id,
            'name' => 'Perfil Adulto',
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'fingerprint' => 'fp-004',
            'profile_id' => $profile->id,
            'pin' => '1234',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.profile.name', 'Perfil Adulto');
    }

    /**
     * Test login with a PIN-protected profile and wrong PIN returns 403.
     */
    public function test_device_login_with_wrong_pin_returns_403(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $profile = UserProfile::factory()->withPin('1234')->create([
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/device-login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'fingerprint' => 'fp-005',
            'profile_id' => $profile->id,
            'pin' => '9999',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test that profile PIN is stored hashed.
     */
    public function test_profile_pin_is_stored_hashed(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->withPin('5678')->create([
            'user_id' => $user->id,
        ]);

        $this->assertNotEquals('5678', $profile->fresh()->getRawOriginal('pin'));
        $this->assertTrue(Hash::check('5678', $profile->fresh()->getRawOriginal('pin')));
    }
}
