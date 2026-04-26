<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_their_applications()
    {
        $user = User::factory()->create();

        $app1 = Application::factory()->create(['name' => 'App 1', 'namespace' => 'app-1']);
        $app2 = Application::factory()->create(['name' => 'App 2', 'namespace' => 'app-2']);
        $app3 = Application::factory()->create(['name' => 'App 3', 'namespace' => 'app-3']);

        $plan1 = Plan::factory()->create(['application_id' => $app1->id]);
        $plan2 = Plan::factory()->create(['application_id' => $app2->id]);
        $plan3 = Plan::factory()->create(['application_id' => $app3->id]);

        // Usuário tem acesso a App 1 e App 2
        $user->plans()->attach([$plan1->id, $plan2->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me/applications');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['namespace' => 'app-1'])
            ->assertJsonFragment(['namespace' => 'app-2'])
            ->assertJsonMissing(['namespace' => 'app-3']);
    }

    public function test_user_with_no_plans_gets_empty_list()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me/applications');

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }
}
