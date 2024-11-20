<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class ZenMoneyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Allow real HTTP requests
        Http::preventStrayRequests(false);
    }

    public function test_can_connect_to_zenmoney_api(): void
    {
        $response = $this->get('/zenmoney/test');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'instrument',
                    'company',
                    'user',
                    'account',
                    'tag',
                    'budget',
                    'merchant',
                    'reminder',
                    'reminderMarker',
                    'transaction',
                    'deletion'
                ]
            ]);

        // Log the response for debugging
        \Log::info('ZenMoney API Response:', ['data' => $response->json()]);
    }
}
