<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    private function makeApp(): Application
    {
        return Application::factory()->create(['namespace' => 'test-app']);
    }

    public function test_can_list_contacts(): void
    {
        $app = $this->makeApp();
        Contact::factory()->count(3)->create(['application_id' => $app->id, 'type' => 'supplier']);
        Contact::factory()->count(2)->create(['application_id' => $app->id, 'type' => 'client']);

        $response = $this->getJson('/api/test-app/contacts');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(5, 'data');
    }

    public function test_can_filter_contacts_by_type(): void
    {
        $app = $this->makeApp();
        Contact::factory()->count(3)->create(['application_id' => $app->id, 'type' => 'supplier']);
        Contact::factory()->count(2)->create(['application_id' => $app->id, 'type' => 'client']);

        $response = $this->getJson('/api/test-app/contacts?type=supplier');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_list_contacts_returns_404_for_unknown_namespace(): void
    {
        $response = $this->getJson('/api/unknown/contacts');

        $response->assertNotFound();
    }

    public function test_can_create_contact(): void
    {
        $this->makeApp();

        $response = $this->postJson('/api/test-app/contacts', [
            'type' => 'supplier',
            'name' => 'Electric Ink',
            'phone' => '5511999999999',
            'email' => 'vendas@electricink.com.br',
            'category' => 'Material Tattoo',
            'payload' => ['notes' => 'Tintas e pigmentos'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Electric Ink')
            ->assertJsonPath('data.type', 'supplier')
            ->assertJsonPath('data.active', true);
    }

    public function test_create_contact_requires_name_and_type(): void
    {
        $this->makeApp();

        $response = $this->postJson('/api/test-app/contacts', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'type']);
    }

    public function test_create_contact_returns_404_for_unknown_namespace(): void
    {
        $response = $this->postJson('/api/unknown/contacts', [
            'type' => 'client',
            'name' => 'João',
        ]);

        $response->assertNotFound();
    }

    public function test_can_update_contact(): void
    {
        $app = $this->makeApp();
        $contact = Contact::factory()->create([
            'application_id' => $app->id,
            'type' => 'client',
            'name' => 'Maria',
        ]);

        $response = $this->patchJson("/api/test-app/contacts/{$contact->id}", [
            'name' => 'Maria Silva',
            'phone' => '5511988887777',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Maria Silva')
            ->assertJsonPath('data.phone', '5511988887777');
    }

    public function test_update_contact_returns_404_for_unknown_id(): void
    {
        $this->makeApp();

        $response = $this->patchJson('/api/test-app/contacts/9999', ['name' => 'X']);

        $response->assertNotFound();
    }

    public function test_can_toggle_contact_active(): void
    {
        $app = $this->makeApp();
        $contact = Contact::factory()->create([
            'application_id' => $app->id,
            'type' => 'supplier',
            'active' => true,
        ]);

        $response = $this->patchJson("/api/test-app/contacts/{$contact->id}", ['active' => false]);

        $response->assertOk()
            ->assertJsonPath('data.active', false);
    }

    public function test_can_delete_contact(): void
    {
        $app = $this->makeApp();
        $contact = Contact::factory()->create(['application_id' => $app->id, 'type' => 'client']);

        $response = $this->deleteJson("/api/test-app/contacts/{$contact->id}");

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_delete_contact_returns_404_for_unknown_id(): void
    {
        $this->makeApp();

        $response = $this->deleteJson('/api/test-app/contacts/9999');

        $response->assertNotFound();
    }
}
