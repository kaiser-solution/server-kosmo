<?php

namespace Tests\Feature\Admin;

use App\Models\Application;
use App\Models\Permission;
use App\Models\Record;
use App\Models\RecordPattern;
use App\Models\RecordType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApplicationRecordPatternTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_associate_pattern_record_types_to_application(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $app = Application::create([
            'name' => 'My App',
            'namespace' => 'my-app',
            'endpoint' => 'http://localhost',
        ]);

        $type1 = RecordType::create(['name' => 'T1', 'slug' => 't1', 'status' => 'active']);
        $type2 = RecordType::create(['name' => 'T2', 'slug' => 't2', 'status' => 'active']);

        $pattern = RecordPattern::create(['name' => 'My Pattern']);
        $pattern->recordTypes()->attach([$type1->id, $type2->id]);

        Livewire::test('applications.index')
            ->call('manageRecordTypes', $app->id)
            ->assertSee('My Pattern')
            ->call('addPattern', $pattern->id)
            ->assertDispatched('toast-show');

        $this->assertDatabaseHas('application_record_type', [
            'application_id' => $app->id,
            'record_type_id' => $type1->id,
        ]);

        $this->assertDatabaseHas('application_record_type', [
            'application_id' => $app->id,
            'record_type_id' => $type2->id,
        ]);
    }

    public function test_prevents_duplicate_pattern_association(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $app = Application::create([
            'name' => 'My App',
            'namespace' => 'my-app',
            'endpoint' => 'http://localhost',
        ]);

        $type1 = RecordType::create(['name' => 'T1', 'slug' => 't1', 'status' => 'active']);
        $app->recordTypes()->attach($type1->id);

        $pattern = RecordPattern::create(['name' => 'My Pattern']);
        $pattern->recordTypes()->attach([$type1->id]);

        Livewire::test('applications.index')
            ->call('manageRecordTypes', $app->id)
            ->call('addPattern', $pattern->id)
            ->assertDispatched('toast-show', function ($event, $data) {
                return ($data['dataset']['variant'] ?? '') === 'warning' && str_contains($data['slots']['text'] ?? '', 'já estavam cadastrados');
            });

        $this->assertEquals(1, $app->recordTypes()->count());
    }

    public function test_prevents_duplicate_permission_by_slug(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $app = Application::create([
            'name' => 'My App',
            'namespace' => 'my-app',
            'endpoint' => 'http://localhost',
        ]);

        Permission::create([
            'name' => 'Existing',
            'slug' => 'existing-slug',
            'application_id' => $app->id,
        ]);

        Livewire::test('applications.index')
            ->set('managingPermissionsId', $app->id)
            ->set('newPermissionName', 'New Name')
            ->set('newPermissionSlug', 'existing-slug')
            ->call('addPermission')
            ->assertDispatched('toast-show', function ($event, $data) {
                return ($data['dataset']['variant'] ?? '') === 'warning' && str_contains($data['slots']['text'] ?? '', 'já está cadastrada');
            });

        $this->assertEquals(1, Permission::where('application_id', $app->id)->count());
    }

    public function test_prevents_duplicate_record_type_association(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $app = Application::create([
            'name' => 'My App',
            'namespace' => 'my-app',
            'endpoint' => 'http://localhost',
        ]);

        $type1 = RecordType::create(['name' => 'T1', 'slug' => 't1', 'status' => 'active']);
        $app->recordTypes()->attach($type1->id);

        Livewire::test('applications.index')
            ->set('managingRecordTypesId', $app->id)
            ->set('newRecordTypeName', 'T1')
            ->set('newRecordTypeSlug', 't1')
            ->call('addRecordType')
            ->assertDispatched('toast-show', function ($event, $data) {
                return ($data['dataset']['variant'] ?? '') === 'warning' && str_contains($data['slots']['text'] ?? '', 'já está associado');
            });

        $this->assertEquals(1, $app->recordTypes()->count());
    }

    public function test_pattern_button_hides_when_app_has_records(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $app = Application::create([
            'name' => 'My App',
            'namespace' => 'my-app',
            'endpoint' => 'http://localhost',
        ]);

        $type1 = RecordType::create(['name' => 'T1', 'slug' => 't1', 'status' => 'active']);
        $app->recordTypes()->attach($type1->id);

        RecordPattern::create(['name' => 'My Pattern']);

        // No records yet
        Livewire::test('applications.index')
            ->call('manageRecordTypes', $app->id)
            ->assertSee('My Pattern');

        // Create a record
        Record::create([
            'application_id' => $app->id,
            'record_type_id' => $type1->id,
            'payload' => [],
            'occurred_at' => now(),
        ]);

        // Should NOT see "Add Pattern" anymore
        Livewire::test('applications.index')
            ->call('manageRecordTypes', $app->id)
            ->assertDontSee('Adicionar Padrão')
            ->assertDontSee('My Pattern');
    }
}
