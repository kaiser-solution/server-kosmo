<?php

namespace Tests\Feature;

use App\Models\Application;
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
            ->assertJsonCount(2, 'data');
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
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Energia')
            ->assertJsonPath('data.active', true);
    }

    public function test_create_institution_requires_name(): void
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

    public function test_can_toggle_institution_status(): void
    {
        $this->makeAppWithRecordType([
            ['name' => 'Internet', 'active' => true],
        ]);

        $response = $this->patchJson('/api/test-app/institutions/recurring-bill/Internet');

        $response->assertOk()
            ->assertJsonPath('data.active', false);

        $response2 = $this->patchJson('/api/test-app/institutions/recurring-bill/Internet');
        $response2->assertOk()
            ->assertJsonPath('data.active', true);
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
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Nova')
            ->assertJsonPath('data.category', 'Cat2')
            ->assertJsonPath('data.defaultVal', 20)
            ->assertJsonPath('data.dueDay', 15);

        $this->getJson('/api/test-app/institutions/recurring-bill')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Nova');
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
