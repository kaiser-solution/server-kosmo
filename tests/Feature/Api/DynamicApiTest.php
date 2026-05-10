<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_can_access_application_api()
    {
        $application = Application::factory()->create(['namespace' => 'financial-ns']);

        $response = $this->getJson('/api/financial-ns');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'namespace' => 'financial-ns',
            ]);
    }

    public function test_returns_404_if_application_does_not_exist()
    {
        $response = $this->getJson('/api/non-existent');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Application not found']);
    }
}
