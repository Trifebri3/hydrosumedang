<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HydroSenseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_esp32_can_send_sensor_data_and_receive_controls(): void
    {
        $payload = [
            'device_code' => 'HYDROSENSE-01',
            'temperature' => 27.25,
            'tds' => 830.0,
            'voltage' => 1.68,
            'pump' => false,
            'auto' => true,
            'ip_address' => '192.168.1.50',
            'wifi_ssid' => 'Agronex_WiFi',
        ];

        $response = $this->postJson('/api/sensor/data', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'server_time',
                'control' => ['pump', 'auto', 'target_tds'],
            ]);

        $this->assertDatabaseHas('devices', [
            'device_code' => 'HYDROSENSE-01',
            'status' => 'online',
        ]);

        $this->assertDatabaseHas('sensor_readings', [
            'temperature' => 27.25,
            'tds' => 830.0,
        ]);
    }

    public function test_can_get_latest_sensor_data(): void
    {
        $this->postJson('/api/sensor/data', [
            'device_code' => 'HYDROSENSE-01',
            'temperature' => 25.80,
            'tds' => 810.0,
            'voltage' => 1.60,
            'pump' => true,
            'auto' => false,
        ]);

        $response = $this->getJson('/api/sensor/latest?device_code=HYDROSENSE-01');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('device.device_code', 'HYDROSENSE-01')
            ->assertJsonPath('device.temperature', 25.80)
            ->assertJsonPath('device.tds', 810);
    }

    public function test_can_update_device_controls_from_web(): void
    {
        $this->postJson('/api/sensor/data', [
            'device_code' => 'HYDROSENSE-01',
            'temperature' => 25.0,
            'tds' => 800.0,
        ]);

        $response = $this->postJson('/api/sensor/control', [
            'device_code' => 'HYDROSENSE-01',
            'pump' => true,
            'auto' => false,
            'target_tds' => 950.0,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('device.pump_status', true)
            ->assertJsonPath('device.auto_mode', false)
            ->assertJsonPath('device.target_tds', 950);

        $this->assertDatabaseHas('devices', [
            'device_code' => 'HYDROSENSE-01',
            'pump_status' => true,
            'auto_mode' => false,
            'target_tds' => 950.0,
        ]);
    }

    public function test_can_get_sensor_history(): void
    {
        $this->postJson('/api/sensor/data', [
            'device_code' => 'HYDROSENSE-01',
            'temperature' => 26.0,
            'tds' => 800.0,
        ]);

        $response = $this->getJson('/api/sensor/history?device_code=HYDROSENSE-01');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'history');
    }
}
