<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRestrictionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that an admin user can log in.
     */
    public function test_admin_can_log_in(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => bcrypt($password = 'i-love-laravel'),
        ]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => $password,
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect('/dashboard');
    }

    /**
     * Test that a non-admin user cannot log in.
     */
    public function test_non_admin_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'password' => bcrypt($password = 'i-love-laravel'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }
}
