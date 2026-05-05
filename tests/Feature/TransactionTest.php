<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Record;
use App\Models\RecordType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_record_types_for_valid_namespace(): void
    {
        $application = Application::factory()->create(['namespace' => 'finance-app']);
        RecordType::factory()->count(2)->create(['application_id' => $application->id, 'active' => true]);

        $response = $this->getJson('/api/finance-app/record-types');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data');
    }

    public function test_returns_404_when_listing_record_types_for_unknown_namespace(): void
    {
        $response = $this->getJson('/api/unknown/record-types');

        $response->assertNotFound();
    }

    public function test_can_list_records_by_type(): void
    {
        $application = Application::factory()->create(['namespace' => 'finance-app']);
        $recordType = RecordType::factory()->create(['application_id' => $application->id, 'slug' => 'expense', 'active' => true]);
        Record::factory()->count(3)->create(['application_id' => $application->id, 'record_type_id' => $recordType->id]);

        $response = $this->getJson('/api/finance-app/records/expense');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(3, 'data.data');
    }

    public function test_returns_404_for_unknown_record_type_slug(): void
    {
        Application::factory()->create(['namespace' => 'finance-app']);

        $response = $this->getJson('/api/finance-app/records/unknown-type');

        $response->assertNotFound();
    }

    public function test_can_store_a_record_with_validity(): void
    {
        $application = Application::factory()->create(['namespace' => 'finance-app']);
        RecordType::factory()->create(['application_id' => $application->id, 'slug' => 'expense', 'active' => true]);

        $response = $this->postJson('/api/finance-app/records/expense', [
            'payload' => ['amount' => 150.50, 'description' => 'Assinatura'],
            'startDate' => '2026-05-01',
            'endDate' => '2026-12-31',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.payload.startDate', '2026-05-01')
            ->assertJsonPath('data.payload.endDate', '2026-12-31');
    }

    public function test_can_store_a_record(): void
    {
        $application = Application::factory()->create(['namespace' => 'finance-app']);
        RecordType::factory()->create(['application_id' => $application->id, 'slug' => 'expense', 'active' => true]);

        $response = $this->postJson('/api/finance-app/records/expense', [
            'payload' => ['amount' => 150.50, 'description' => 'Almoço', 'category' => 'alimentação'],
            'occurred_at' => '2026-04-24 12:00:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.payload.description', 'Almoço');

        $this->assertDatabaseHas('records', [
            'application_id' => $application->id,
        ]);
    }

    public function test_store_returns_404_for_unknown_namespace(): void
    {
        $response = $this->postJson('/api/unknown/records/expense', [
            'payload' => ['amount' => 100],
        ]);

        $response->assertNotFound();
    }

    public function test_store_validates_required_payload(): void
    {
        $application = Application::factory()->create(['namespace' => 'finance-app']);
        RecordType::factory()->create(['application_id' => $application->id, 'slug' => 'expense', 'active' => true]);

        $response = $this->postJson('/api/finance-app/records/expense', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payload']);
    }

    public function test_can_update_a_record(): void
    {
        $application = Application::factory()->create(['namespace' => 'finance-app']);
        $recordType = RecordType::factory()->create(['application_id' => $application->id, 'slug' => 'expense', 'active' => true]);
        $record = Record::factory()->create([
            'application_id' => $application->id,
            'record_type_id' => $recordType->id,
            'payload' => ['amount' => 100, 'isVoided' => false],
        ]);

        $response = $this->patchJson("/api/finance-app/records/expense/{$record->id}", [
            'payload' => ['isVoided' => true],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.payload.isVoided', true);
    }

    public function test_can_update_record_type(): void
    {
        $application = Application::factory()->create(['namespace' => 'finance-app']);
        $recordType1 = RecordType::factory()->create(['application_id' => $application->id, 'slug' => 'type1', 'active' => true]);
        $recordType2 = RecordType::factory()->create(['application_id' => $application->id, 'slug' => 'type2', 'active' => true]);

        $record = Record::factory()->create([
            'application_id' => $application->id,
            'record_type_id' => $recordType1->id,
        ]);

        $response = $this->patchJson("/api/finance-app/records/type1/{$record->id}", [
            'record_type_id' => $recordType2->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.record_type_id', $recordType2->id);
    }

    public function test_update_returns_404_for_unknown_record(): void
    {
        $application = Application::factory()->create(['namespace' => 'finance-app']);
        RecordType::factory()->create(['application_id' => $application->id, 'slug' => 'expense', 'active' => true]);

        $response = $this->patchJson('/api/finance-app/records/expense/99999', [
            'payload' => ['isVoided' => true],
        ]);

        $response->assertNotFound();
    }

    public function test_update_returns_404_for_unknown_namespace(): void
    {
        $response = $this->patchJson('/api/unknown/records/expense/1', [
            'payload' => ['isVoided' => true],
        ]);

        $response->assertNotFound();
    }

    public function test_can_list_records_with_month_filter_and_totals()
    {
        $application = Application::factory()->create(['namespace' => 'test-app']);
        $recordType = RecordType::factory()->create([
            'application_id' => $application->id,
            'slug' => 'transaction',
        ]);

        // Record em Abril
        Record::factory()->create([
            'application_id' => $application->id,
            'record_type_id' => $recordType->id,
            'occurred_at' => '2026-04-10',
            'payload' => ['type' => 'income', 'val' => 100],
        ]);

        // Outro Record em Abril
        Record::factory()->create([
            'application_id' => $application->id,
            'record_type_id' => $recordType->id,
            'occurred_at' => '2026-04-15',
            'payload' => ['type' => 'expense', 'val' => 40],
        ]);

        // Record em Maio
        Record::factory()->create([
            'application_id' => $application->id,
            'record_type_id' => $recordType->id,
            'occurred_at' => '2026-05-01',
            'payload' => ['type' => 'income', 'val' => 500],
        ]);

        $response = $this->getJson('/api/test-app/records/transaction?month=2026-04&with_totals=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('totals.income', 100)
            ->assertJsonPath('totals.expense', 40);
    }
}
