<?php

namespace Tests\Feature\Api;

use App\Models\AppConfig;
use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AppConfigCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    private function makeApp(string $namespace = 'test-app'): Application
    {
        return Application::factory()->create(['namespace' => $namespace]);
    }

    public function test_can_save_categories(): void
    {
        $user = $this->makeUser();
        $app = $this->makeApp();

        $categories = [
            ['name' => 'Casa', 'color' => '#f59e0b'],
            ['name' => 'Estúdio', 'color' => '#8b5cf6'],
        ];

        $response = $this->actingAs($user)
            ->putJson("/api/{$app->namespace}/config/categories", ['categories' => $categories]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'categories');

        $this->assertDatabaseHas('app_configs', ['application_id' => $app->id]);
    }

    public function test_categories_are_persisted_and_returned_in_config(): void
    {
        $user = $this->makeUser();
        $app = $this->makeApp('branding-app');

        $categories = [
            ['name' => 'Saúde', 'color' => '#10b981'],
            ['name' => 'Outros', 'color' => '#718096'],
        ];

        $this->actingAs($user)
            ->putJson("/api/{$app->namespace}/config/categories", ['categories' => $categories])
            ->assertOk();

        $response = $this->getJson("/api/{$app->namespace}/config");

        $response->assertOk()
            ->assertJsonCount(2, 'config.categories');

        $names = collect($response->json('config.categories'))->pluck('name')->toArray();
        $this->assertContains('Saúde', $names);
        $this->assertContains('Outros', $names);
    }

    public function test_saving_categories_invalidates_cache(): void
    {
        $user = $this->makeUser();
        $app = $this->makeApp('cache-app');
        AppConfig::factory()->create(['application_id' => $app->id]);
        Cache::put("app_config_by_namespace_{$app->namespace}", ['app_name' => 'Cache App'], 86400);

        $this->actingAs($user)
            ->putJson("/api/{$app->namespace}/config/categories", [
                'categories' => [['name' => 'Casa', 'color' => '#f59e0b']],
            ])
            ->assertOk();

        $this->assertFalse(Cache::has("app_config_by_namespace_{$app->namespace}"));
    }

    public function test_requires_authentication(): void
    {
        $app = $this->makeApp();

        $this->putJson("/api/{$app->namespace}/config/categories", [
            'categories' => [['name' => 'Casa', 'color' => '#f59e0b']],
        ])->assertUnauthorized();
    }

    public function test_validates_category_color_format(): void
    {
        $user = $this->makeUser();
        $app = $this->makeApp();

        $this->actingAs($user)
            ->putJson("/api/{$app->namespace}/config/categories", [
                'categories' => [['name' => 'Casa', 'color' => 'not-a-color']],
            ])->assertUnprocessable();
    }

    public function test_validates_category_name_required(): void
    {
        $user = $this->makeUser();
        $app = $this->makeApp();

        $this->actingAs($user)
            ->putJson("/api/{$app->namespace}/config/categories", [
                'categories' => [['color' => '#f59e0b']],
            ])->assertUnprocessable();
    }

    public function test_returns_404_for_unknown_namespace(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->putJson('/api/unknown-namespace/config/categories', [
                'categories' => [['name' => 'Casa', 'color' => '#f59e0b']],
            ])->assertNotFound();
    }
}
