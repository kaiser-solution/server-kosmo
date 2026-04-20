<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlanManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin can manage plan permissions.
     */
    public function test_admin_can_manage_plan_permissions(): void
    {
        $admin = User::factory()->admin()->create();
        $app = Application::create(['name' => 'App 1', 'endpoint' => 'http://app1.test', 'namespace' => 'app1']);
        $p1 = Permission::create(['name' => 'P1', 'slug' => 'p1', 'application_id' => $app->id]);
        $p2 = Permission::create(['name' => 'P2', 'slug' => 'p2', 'application_id' => $app->id]);
        $plan = Plan::create(['name' => 'Premium Plan', 'price' => 99.99, 'currency' => 'USD', 'application_id' => $app->id]);

        Livewire::actingAs($admin)
            ->test('plans.index')
            ->call('managePermissions', $plan->id)
            ->set('selectedPermissions', [(string) $p1->id, (string) $p2->id])
            ->call('savePermissions')
            ->assertHasNoErrors();

        $plan->refresh();
        $this->assertEquals(2, $plan->permissions()->count());
        $this->assertTrue($plan->permissions->contains($p1));
        $this->assertTrue($plan->permissions->contains($p2));
    }

    /**
     * Test admin can create a plan.
     */
    public function test_admin_can_create_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $app = Application::create(['name' => 'App 1', 'endpoint' => 'http://app1.test', 'namespace' => 'app1']);

        Livewire::actingAs($admin)
            ->test('plans.index')
            ->set('data.application_id', (string) $app->id)
            ->set('data.name', 'Premium Plan')
            ->set('data.description', 'A premium plan')
            ->set('data.price', 99.99)
            ->set('data.currency', 'USD')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('plans', [
            'name' => 'Premium Plan',
            'application_id' => $app->id,
            'price' => 99.99,
            'currency' => 'USD',
        ]);
    }

    /**
     * Test plan search.
     */
    public function test_plan_search(): void
    {
        $admin = User::factory()->admin()->create();
        $app = Application::create(['name' => 'App 1', 'endpoint' => 'http://app1.test', 'namespace' => 'app1']);
        Plan::create(['name' => 'Basic', 'price' => 10, 'currency' => 'BRL', 'application_id' => $app->id]);
        Plan::create(['name' => 'Advanced', 'price' => 20, 'currency' => 'BRL', 'application_id' => $app->id]);

        Livewire::actingAs($admin)
            ->test('plans.index')
            ->set('q', 'Basic')
            ->assertSee('Basic')
            ->assertDontSee('Advanced');
    }

    /**
     * Test admin can delete a plan.
     */
    public function test_admin_can_delete_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $app = Application::create(['name' => 'App 1', 'endpoint' => 'http://app1.test', 'namespace' => 'app1']);
        $plan = Plan::create(['name' => 'To Delete', 'price' => 0, 'currency' => 'BRL', 'application_id' => $app->id]);

        Livewire::actingAs($admin)
            ->test('plans.index')
            ->call('delete', $plan->id);

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    /**
     * Test permissions are cleared when plan application is changed.
     */
    public function test_permissions_are_cleared_when_plan_application_is_changed(): void
    {
        $admin = User::factory()->admin()->create();
        $app1 = Application::create(['name' => 'App 1', 'endpoint' => 'http://app1.test', 'namespace' => 'app1']);
        $app2 = Application::create(['name' => 'App 2', 'endpoint' => 'http://app2.test', 'namespace' => 'app2']);

        $p1 = Permission::create(['name' => 'P1', 'slug' => 'p1', 'application_id' => $app1->id]);
        $plan = Plan::create(['name' => 'Plan', 'price' => 10, 'currency' => 'BRL', 'application_id' => $app1->id]);
        $plan->permissions()->attach($p1);

        $this->assertEquals(1, $plan->permissions()->count());

        Livewire::actingAs($admin)
            ->test('plans.index')
            ->call('edit', $plan->id)
            ->set('data.application_id', (string) $app2->id)
            ->call('save')
            ->assertHasNoErrors();

        $plan->refresh();
        $this->assertEquals($app2->id, $plan->application_id);
        $this->assertEquals(0, $plan->permissions()->count(), 'Permissions should have been cleared when application changed.');
    }
}
