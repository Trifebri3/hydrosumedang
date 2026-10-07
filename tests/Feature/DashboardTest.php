<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_successfully_with_hydrosense_data(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('HydroSense by agronex')
            ->assertSee('agronex')
            ->assertSee('Suhu Air')
            ->assertSee('Kepekatan Nutrisi')
            ->assertSee('Instalasi Hidroponik')
            ->assertSee('Buku Panduan HydroSense');
    }
}
