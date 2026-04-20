<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserCRUDTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin can create a user.
     */
    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('users.index')
            ->call('create')
            ->set('data.name', 'John Doe')
            ->set('data.email', 'john@example.com')
            ->set('data.phone', '123456789')
            ->set('data.password', 'password123')
            ->set('data.is_admin', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123456789',
            'is_admin' => false,
        ]);
    }

    /**
     * Test admin can edit a user.
     */
    public function test_admin_can_edit_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Old Name']);

        Livewire::actingAs($admin)
            ->test('users.index')
            ->call('edit', $user->id)
            ->set('data.name', 'New Name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('New Name', $user->fresh()->name);
    }

    /**
     * Test admin cannot remove own admin status.
     */
    public function test_admin_cannot_remove_own_admin_status(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('users.index')
            ->call('edit', $admin->id)
            ->set('data.is_admin', false)
            ->call('save')
            ->assertSet('data.is_admin', true); // Should be reverted

        $this->assertTrue($admin->fresh()->is_admin);
    }

    /**
     * Test admin cannot delete themselves.
     */
    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('users.index')
            ->call('delete', $admin->id);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    /**
     * Test user search.
     */
    public function test_user_search(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Alice']);
        User::factory()->create(['name' => 'Bob']);

        Livewire::actingAs($admin)
            ->test('users.index')
            ->set('q', 'Alice')
            ->assertSee('Alice')
            ->assertDontSee('Bob');
    }
}
