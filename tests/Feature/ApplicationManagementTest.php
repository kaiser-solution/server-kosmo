<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApplicationManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin can create an application.
     */
    public function test_admin_can_create_application(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('applications.index')
            ->call('create')
            ->set('data.name', 'Test App')
            ->set('data.namespace', 'test-app')
            ->set('data.endpoint', 'https://test.app')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('applications', [
            'name' => 'Test App',
            'namespace' => 'test-app',
            'endpoint' => 'https://test.app',
        ]);
    }

    /**
     * Test application search.
     */
    public function test_application_search(): void
    {
        $admin = User::factory()->admin()->create();
        Application::create(['name' => 'First', 'namespace' => 'first', 'endpoint' => 'https://first.com']);
        Application::create(['name' => 'Second', 'namespace' => 'second', 'endpoint' => 'https://second.com']);

        Livewire::actingAs($admin)
            ->test('applications.index')
            ->set('q', 'First')
            ->assertSee('First')
            ->assertDontSee('Second');
    }

    /**
     * Test admin can manage application permissions.
     */
    public function test_admin_can_manage_application_permissions(): void
    {
        $admin = User::factory()->admin()->create();
        $app = Application::create(['name' => 'App 1', 'namespace' => 'app1', 'endpoint' => 'https://app1.com']);

        Livewire::actingAs($admin)
            ->test('applications.index')
            ->call('managePermissions', $app->id)
            ->set('newPermissionName', 'New Permission')
            ->set('newPermissionSlug', 'new-permission')
            ->call('addPermission')
            ->assertHasNoErrors()
            ->assertSet('newPermissionName', '')
            ->assertSet('newPermissionSlug', '');

        $this->assertDatabaseHas('permissions', [
            'name' => 'New Permission',
            'slug' => 'new-permission',
            'application_id' => $app->id,
        ]);

        $permission = Permission::where('slug', 'new-permission')->first();

        Livewire::actingAs($admin)
            ->test('applications.index')
            ->call('managePermissions', $app->id)
            ->call('deletePermission', $permission->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('permissions', [
            'id' => $permission->id,
        ]);
    }
}
