<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\RecordPatternManager;
use App\Models\RecordPattern;
use App\Models\RecordType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecordPatternManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_load_record_pattern_manager_page(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->get(route('record-patterns.index'));

        $response->assertStatus(200);
    }

    public function test_can_create_record_pattern_with_types(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $type1 = RecordType::create(['name' => 'Tipo 1', 'slug' => 'tipo-1', 'status' => 'active']);
        $type2 = RecordType::create(['name' => 'Tipo 2', 'slug' => 'tipo-2', 'status' => 'active']);

        Livewire::test(RecordPatternManager::class)
            ->set('data.name', 'Padrão Tattoo')
            ->set('data.recordTypes', [(string) $type1->id, (string) $type2->id])
            ->call('save');

        $this->assertDatabaseHas('record_patterns', [
            'name' => 'Padrão Tattoo',
        ]);

        $pattern = RecordPattern::where('name', 'Padrão Tattoo')->first();
        $this->assertCount(2, $pattern->recordTypes);
        $this->assertTrue($pattern->recordTypes->contains($type1));
        $this->assertTrue($pattern->recordTypes->contains($type2));
    }

    public function test_can_edit_record_pattern_types(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $type1 = RecordType::create(['name' => 'Tipo 1', 'slug' => 'tipo-1', 'status' => 'active']);
        $type2 = RecordType::create(['name' => 'Tipo 2', 'slug' => 'tipo-2', 'status' => 'active']);

        $pattern = RecordPattern::create(['name' => 'Padrão Antigo']);
        $pattern->recordTypes()->attach($type1);

        Livewire::test(RecordPatternManager::class)
            ->call('edit', $pattern->id)
            ->set('data.recordTypes', [(string) $type2->id])
            ->call('save');

        $pattern->refresh();
        $this->assertCount(1, $pattern->recordTypes);
        $this->assertTrue($pattern->recordTypes->contains($type2));
        $this->assertFalse($pattern->recordTypes->contains($type1));
    }

    public function test_new_record_pattern_has_empty_record_types(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        RecordType::create(['name' => 'Tipo 1', 'slug' => 'tipo-1', 'status' => 'active']);

        Livewire::test(RecordPatternManager::class)
            ->call('create')
            ->assertSet('data.recordTypes', []);
    }
}
