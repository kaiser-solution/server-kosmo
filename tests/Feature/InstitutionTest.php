<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Record;
use App\Models\RecordType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionTest extends TestCase
{
    use RefreshDatabase;

    private function makeAppWithRecordType(array $institutions = []): array
    {
        $application = Application::factory()->create(['namespace' => 'test-app']);
        $recordType = RecordType::factory()->create([
            'application_id' => $application->id,
            'slug' => 'recurring-bill',
            'active' => true,
            'schema' => ['x-institutions' => $institutions],
        ]);

        return [$application, $recordType];
    }

    public function test_can_list_institutions(): void
    {
        $this->makeAppWithRecordType([
            ['name' => 'Aluguel', 'active' => true],
            ['name' => 'Internet', 'active' => false],
        ]);

        $response = $this->getJson('/api/test-app/institutions/recurring-bill');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Aluguel');
    }

    public function test_list_institutions_returns_404_for_unknown_namespace(): void
    {
        $response = $this->getJson('/api/unknown/institutions/recurring-bill');

        $response->assertNotFound();
    }

    public function test_list_institutions_returns_404_for_unknown_type(): void
    {
        Application::factory()->create(['namespace' => 'test-app']);

        $response = $this->getJson('/api/test-app/institutions/nonexistent');

        $response->assertNotFound();
    }

    public function test_can_create_institution(): void
    {
        $this->makeAppWithRecordType();

        $response = $this->postJson('/api/test-app/institutions/recurring-bill', [
            'name' => 'Energia',
            'category' => 'Utilidades',
            'defaultVal' => 150.00,
            'dueDay' => 10,
            'tracking_since' => '2026-05',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Energia')
            ->assertJsonPath('data.active', true)
            ->assertJsonPath('data.tracking_since', '2026-05')
            ->assertJsonPath('data.createdAt', now()->format('Y-m'));
    }

    public function test_can_create_institution_requires_name(): void
    {
        $this->makeAppWithRecordType();

        $response = $this->postJson('/api/test-app/institutions/recurring-bill', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_institution_rejects_duplicate_name(): void
    {
        $this->makeAppWithRecordType([
            ['name' => 'Aluguel', 'active' => true],
        ]);

        $response = $this->postJson('/api/test-app/institutions/recurring-bill', [
            'name' => 'Aluguel',
        ]);

        $response->assertUnprocessable();
    }

    public function test_create_institution_reactivates_inactive_one(): void
    {
        $this->makeAppWithRecordType([
            ['name' => 'Antiga', 'active' => false, 'category' => 'Velha'],
        ]);

        $response = $this->postJson('/api/test-app/institutions/recurring-bill', [
            'name' => 'Antiga',
            'category' => 'Nova',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.active', true)
            ->assertJsonPath('data.category', 'Nova');
    }

    public function test_can_toggle_institution_status_and_remove_if_no_records(): void
    {
        [$application, $recordType] = $this->makeAppWithRecordType([
            ['name' => 'Internet', 'active' => true],
        ]);

        // Toggle desativa e remove pois não há registros
        $response = $this->patchJson('/api/test-app/institutions/recurring-bill/Internet');

        $response->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->getJson('/api/test-app/institutions/recurring-bill')
            ->assertJsonCount(0, 'data');
    }

    public function test_toggle_institution_keeps_inactive_if_has_records(): void
    {
        [$application, $recordType] = $this->makeAppWithRecordType([
            ['name' => 'Aluguel', 'active' => true],
        ]);

        // Cria um registro associado
        Record::create([
            'application_id' => $application->id,
            'record_type_id' => $recordType->id,
            'payload' => ['name' => 'Aluguel', 'amount' => 1000],
            'occurred_at' => now(),
        ]);

        $response = $this->patchJson('/api/test-app/institutions/recurring-bill/Aluguel');

        $response->assertOk()
            ->assertJsonPath('data.active', false);

        // Não aparece na listagem
        $this->getJson('/api/test-app/institutions/recurring-bill')
            ->assertJsonCount(0, 'data');
    }

    public function test_toggle_institution_returns_404_for_unknown_name(): void
    {
        $this->makeAppWithRecordType();

        $response = $this->patchJson('/api/test-app/institutions/recurring-bill/Inexistente');

        $response->assertNotFound();
    }

    public function test_can_update_institution(): void
    {
        $this->makeAppWithRecordType([
            ['name' => 'Antiga', 'category' => 'Cat1', 'defaultVal' => 10, 'dueDay' => 5, 'active' => true],
        ]);

        $response = $this->putJson('/api/test-app/institutions/recurring-bill/Antiga', [
            'name' => 'Nova',
            'category' => 'Cat2',
            'defaultVal' => 20,
            'dueDay' => 15,
            'tracking_since' => '2026-06',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Nova')
            ->assertJsonPath('data.category', 'Cat2')
            ->assertJsonPath('data.defaultVal', 20)
            ->assertJsonPath('data.dueDay', 15)
            ->assertJsonPath('data.tracking_since', '2026-06');

        $this->getJson('/api/test-app/institutions/recurring-bill')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Nova')
            ->assertJsonPath('data.0.tracking_since', '2026-06');
    }

    public function test_update_institution_rejects_duplicate_new_name(): void
    {
        $this->makeAppWithRecordType([
            ['name' => 'Conta1', 'active' => true],
            ['name' => 'Conta2', 'active' => true],
        ]);

        $response = $this->putJson('/api/test-app/institutions/recurring-bill/Conta1', [
            'name' => 'Conta2',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Institution with this name already exists');
    }
}
