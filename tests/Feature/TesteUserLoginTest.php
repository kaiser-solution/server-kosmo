<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TesteUserLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test login with specific credentials requested by user.
     */
    public function test_user_can_login_with_provided_credentials(): void
    {
        // Certificar que o usuário existe como administrador (regra atual)
        $user = User::factory()->create([
            'email' => 'teste@teste.com',
            'password' => Hash::make('123'),
            'is_admin' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'teste@teste.com',
            'password' => '123',
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test user cannot login if not administrator.
     */
    public function test_user_cannot_login_if_not_admin(): void
    {
        $user = User::factory()->create([
            'email' => 'teste-nonadmin@example.com',
            'password' => Hash::make('123'),
            'is_admin' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'teste-nonadmin@example.com',
            'password' => '123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }
}
