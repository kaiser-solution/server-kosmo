<?php

namespace Tests\Feature;

use App\Models\AppConfig;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AppConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_config_for_valid_namespace(): void
    {
        $application = Application::factory()->create(['namespace' => 'finance-app']);
        AppConfig::factory()->create([
            'application_id' => $application->id,
            'display_name' => 'Finance App',
            'primary_color' => '#ff0000',
            'secondary_color' => '#00ff00',
            'default_currency' => 'BRL',
        ]);

        $response = $this->getJson('/api/finance-app/config');

        $response->assertOk()
            ->assertJsonStructure(['status', 'config' => ['display_name', 'primary_color', 'secondary_color', 'default_currency']])
            ->assertJsonPath('config.display_name', 'Finance App')
            ->assertJsonPath('config.primary_color', '#ff0000')
            ->assertJsonPath('config.default_currency', 'BRL');
    }

    public function test_returns_default_config_when_application_has_no_explicit_config(): void
    {
        Application::factory()->create([
            'name' => 'Default App',
            'namespace' => 'no-config-app',
        ]);

        $response = $this->getJson('/api/no-config-app/config');

        $response->assertOk()
            ->assertJsonPath('config.name', 'Default App')
            ->assertJsonPath('config.display_name', 'Default App');
    }

    public function test_returns_404_for_unknown_namespace(): void
    {
        $response = $this->getJson('/api/unknown-namespace/config');

        $response->assertNotFound();
    }

    public function test_config_is_cached_after_first_request(): void
    {
        $application = Application::factory()->create(['namespace' => 'cached-app']);
        AppConfig::factory()->create(['application_id' => $application->id, 'display_name' => 'Cached App']);

        Cache::flush();

        $this->getJson('/api/cached-app/config')->assertOk();

        $this->assertTrue(Cache::has('app_config_by_namespace_cached-app'));
    }

    public function test_cache_is_invalidated_when_config_is_saved(): void
    {
        $application = Application::factory()->create(['namespace' => 'livewire-app']);
        $config = AppConfig::factory()->create(['application_id' => $application->id]);

        Cache::put("app_config_by_namespace_{$application->namespace}", $config->toArray(), 86400);

        $config->update(['display_name' => 'Updated Name']);
        Cache::forget("app_config_by_namespace_{$application->namespace}");

        $this->assertFalse(Cache::has("app_config_by_namespace_{$application->namespace}"));
    }
}
