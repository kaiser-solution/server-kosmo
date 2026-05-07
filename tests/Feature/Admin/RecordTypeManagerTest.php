<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\RecordTypeManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecordTypeManagerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_can_load_record_type_manager_page(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->get(route('record-types.index'));

        $response->assertStatus(200);
    }

    public function test_can_create_record_type(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        Livewire::test(RecordTypeManager::class)
            ->set('data.name', 'Novo Tipo')
            ->set('data.slug', 'novo-tipo')
            ->set('data.status', 'active')
            ->call('save');

        $this->assertDatabaseHas('record_types', [
            'name' => 'Novo Tipo',
            'slug' => 'novo-tipo',
            'status' => 'active',
        ]);
    }
}
