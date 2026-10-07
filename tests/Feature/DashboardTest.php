<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'Petani Sumedang',
        ]);

        $device = Device::create([
            'user_id' => $user->id,
            'device_code' => 'alat1sumedang',
            'api_key' => 'alat1sumedang',
            'name' => 'HydroSense Sumedang Unit 1',
            'location' => 'Greenhouse Cisewu',
            'has_tds' => true,
            'has_temp' => true,
            'has_pump' => true,
            'has_auto_mode' => true,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200)
            ->assertSee('HydroSense by agronex')
            ->assertSee('Petani Sumedang')
            ->assertSee('Suhu Air')
            ->assertSee('Kepekatan Nutrisi')
            ->assertSee('Instalasi Hidroponik')
            ->assertSee('Buku Panduan HydroSense');
    }
}
