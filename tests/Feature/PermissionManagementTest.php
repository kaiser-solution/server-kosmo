<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin can create a permission.
     */
    public function test_admin_can_create_permission(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('permissions.index')
            ->set('name', 'My New Permission')
            ->set('slug', 'my-new-permission')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('permissions', [
            'name' => 'My New Permission',
            'slug' => 'my-new-permission',
        ]);
    }

    /**
     * Test permission slug is automatically generated.
     */
    public function test_permission_slug_is_automatically_generated(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('permissions.index')
            ->set('name', 'Test Permission')
            ->assertSet('slug', 'test-permission');
    }

    /**
     * Test permission search.
     */
    public function test_permission_search(): void
    {
        $admin = User::factory()->admin()->create();
        Permission::create(['name' => 'First', 'slug' => 'first']);
        Permission::create(['name' => 'Second', 'slug' => 'second']);

        Livewire::actingAs($admin)
            ->test('permissions.index')
            ->set('q', 'First')
            ->assertSee('First')
            ->assertDontSee('Second');
    }

    /**
     * Test that an admin can manage user permissions.
     */
    public function test_admin_can_manage_user_permissions(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $permission1 = Permission::create(['name' => 'View Users', 'slug' => 'view-users']);
        $permission2 = Permission::create(['name' => 'Edit Users', 'slug' => 'edit-users']);

        Livewire::actingAs($admin)
            ->test('users.index')
            ->call('managePermissions', $user->id)
            ->assertSet('userPermissions', [])
            ->set('userPermissions.'.$permission1->id, true)
            ->set('userPermissions.'.$permission2->id, true)
            ->call('savePermissions')
            ->assertHasNoErrors();

        $this->assertEquals(2, $user->fresh()->permissions()->count());
        $this->assertTrue($user->fresh()->permissions->contains($permission1));
        $this->assertTrue($user->fresh()->permissions->contains($permission2));
    }

    /**
     * Test that an admin can remove user permissions.
     */
    public function test_admin_can_remove_user_permissions(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $permission = Permission::create(['name' => 'View Users', 'slug' => 'view-users']);
        $user->permissions()->attach($permission);

        Livewire::actingAs($admin)
            ->test('users.index')
            ->call('managePermissions', $user->id)
            ->assertSet('userPermissions', [(string) $permission->id => true])
            ->set('userPermissions.'.$permission->id, false)
            ->call('savePermissions')
            ->assertHasNoErrors();

        $this->assertEquals(0, $user->fresh()->permissions()->count());
    }
}
