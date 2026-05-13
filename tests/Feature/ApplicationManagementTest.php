<?php

namespace Tests\Feature;

use App\Models\AppConfig;
use App\Models\Application;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    public function test_admin_can_save_application_configuration(): void
    {
        $admin = User::factory()->admin()->create();
        $application = Application::create([
            'name' => 'App Config Test',
            'namespace' => 'app-config-test',
            'endpoint' => 'https://config.test',
        ]);

        Cache::put("app_config_by_namespace_{$application->namespace}", ['stale' => true], 600);

        Livewire::actingAs($admin)
            ->test('applications.index')
            ->call('manageConfig', $application->id)
            ->set('configDisplayName', 'Minha Aplicacao')
            ->set('configPrimaryColor', '#000001')
            ->set('configSecondaryColor', '#000001')
            ->set('configDefaultCurrency', 'BRL')
            ->set('configCategories', [
                ['name' => 'Teste', 'color' => '#6366f1'],
            ])
            ->call('saveConfig')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('app_configs', [
            'application_id' => $application->id,
            'display_name' => 'Minha Aplicacao',
            'primary_color' => '#000001',
            'secondary_color' => '#000001',
            'default_currency' => 'BRL',
        ]);

        $this->assertFalse(Cache::has("app_config_by_namespace_{$application->namespace}"));

        $config = AppConfig::where('application_id', $application->id)->first();

        $this->assertNotNull($config);
        $this->assertSame([
            ['name' => 'Teste', 'color' => '#6366f1'],
        ], $config->categories);
    }

    public function test_admin_can_apply_default_categories(): void
    {
        $admin = User::factory()->admin()->create();
        $application = Application::create([
            'name' => 'App Config Test',
            'namespace' => 'app-config-test',
            'endpoint' => 'https://config.test',
        ]);

        Livewire::actingAs($admin)
            ->test('applications.index')
            ->call('manageConfig', $application->id)
            ->call('applyDefaults')
            ->assertSet('configCategories', [
                ['name' => 'Assinaturas',    'color' => '#6366f1'],
                ['name' => 'Cartões',        'color' => '#ec4899'],
                ['name' => 'Casa',           'color' => '#f59e0b'],
                ['name' => 'Comunicação',    'color' => '#06b6d4'],
                ['name' => 'Manutenção',     'color' => '#64748b'],
                ['name' => 'Material Tattoo', 'color' => '#0ea5e9'],
                ['name' => 'MEI / Impostos', 'color' => '#ef4444'],
                ['name' => 'Saúde',          'color' => '#10b981'],
                ['name' => 'Segurança',      'color' => '#d97706'],
                ['name' => 'Terreno',        'color' => '#78716c'],
                ['name' => 'Transporte',     'color' => '#3b82f6'],
                ['name' => 'Outros',         'color' => '#718096'],
            ]);
    }
}
