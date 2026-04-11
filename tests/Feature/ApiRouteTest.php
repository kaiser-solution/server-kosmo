<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiRouteTest extends TestCase
{
    /**
     * Test api route returns ok status.
     */
    public function test_api_route_returns_ok_status(): void
    {
        $response = $this->getJson('/api');

        $response->assertStatus(200)
                 ->assertJson(['status' => 'ok']);
    }
}
