<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_live_reports_ok_without_touching_the_database(): void
    {
        $response = $this->getJson('/health/live');

        $response->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_ready_reports_ok_when_the_database_answers(): void
    {
        $response = $this->getJson('/health/ready');

        $response->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_ready_reports_unavailable_when_the_database_connection_fails(): void
    {
        DB::shouldReceive('connection')->once()->andThrow(new PDOException('Connection refused'));

        $response = $this->getJson('/health/ready');

        $response->assertStatus(503)->assertJsonPath('status', 'error');
    }

    public function test_live_stays_ok_even_when_the_database_connection_fails(): void
    {
        DB::shouldReceive('connection')->never();

        $response = $this->getJson('/health/live');

        $response->assertOk()->assertJsonPath('status', 'ok');
    }
}
