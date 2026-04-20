<?php

namespace Tests\Feature;

use App\Models\Application;
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
        $application = Application::factory()->create();

        Livewire::actingAs($admin)
            ->test('permissions.index')
            ->set('data.name', 'My New Permission')
            ->set('data.slug', 'my-new-permission')
            ->set('data.application_id', $application->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('permissions', [
            'name' => 'My New Permission',
            'slug' => 'my-new-permission',
            'application_id' => $application->id,
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
            ->set('data.name', 'Test Permission')
            ->assertSet('data.slug', 'test-permission');
    }

    /**
     * Test permission search.
     */
    public function test_permission_search(): void
    {
        $admin = User::factory()->admin()->create();
        $application = Application::factory()->create();
        Permission::create(['name' => 'First', 'slug' => 'first', 'application_id' => $application->id]);
        Permission::create(['name' => 'Second', 'slug' => 'second', 'application_id' => $application->id]);

        Livewire::actingAs($admin)
            ->test('permissions.index')
            ->set('q', 'First')
            ->assertSee('First')
            ->assertDontSee('Second');
    }
}
