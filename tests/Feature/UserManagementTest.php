<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
