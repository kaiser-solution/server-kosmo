<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/profiles', ['name' => 'Dani']);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('profile.name', 'Dani');

        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->id, 'name' => 'Dani']);
    }

    public function test_create_profile_with_pin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/profiles', ['name' => 'Denis', 'pin' => '1234']);

        $response->assertCreated()
            ->assertJsonPath('profile.pin', true);
    }

    public function test_create_profile_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/profiles', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unauthenticated_cannot_create_profile(): void
    {
        $response = $this->postJson('/api/profiles', ['name' => 'Teste']);

        $response->assertUnauthorized();
    }

    public function test_user_can_delete_profile(): void
    {
        $user = User::factory()->create();
        $profile1 = $user->profiles()->create(['name' => 'P1']);
        $profile2 = $user->profiles()->create(['name' => 'P2']);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/profiles/{$profile2->id}");

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('user_profiles', ['id' => $profile2->id]);
    }

    public function test_user_cannot_delete_only_profile(): void
    {
        $user = User::factory()->create();
        $profile = $user->profiles()->create(['name' => 'Unico']);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/profiles/{$profile->id}");

        $response->assertForbidden()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Você não pode excluir o seu único perfil.');

        $this->assertDatabaseHas('user_profiles', ['id' => $profile->id]);
    }

    public function test_user_cannot_delete_others_profile(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $profile2_1 = $user2->profiles()->create(['name' => 'U2P1']);
        $profile2_2 = $user2->profiles()->create(['name' => 'U2P2']);

        $response = $this->actingAs($user1, 'sanctum')
            ->deleteJson("/api/profiles/{$profile2_2->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('user_profiles', ['id' => $profile2_2->id]);
    }
}
