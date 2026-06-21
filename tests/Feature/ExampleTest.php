<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_mysql_database_status(): void
    {
        $response = $this->get('/');

        $this->assertContains($response->getStatusCode(), [200, 503]);

        $response
            ->assertJsonPath('service', '2026-hpe-api')
            ->assertJsonPath('version', '3.6.0')
            ->assertJsonPath('database.driver', 'mysql')
            ->assertJsonStructure([
                'status',
                'service',
                'version',
                'database' => [
                    'driver',
                    'connected',
                ],
                'checkedAt',
            ]);

        $this->assertIsBool($response->json('database.connected'));
    }
}
