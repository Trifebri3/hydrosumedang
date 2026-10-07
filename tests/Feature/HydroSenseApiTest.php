<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
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

    public function test_custom_slug_alat1sumedang_telemetry_and_control(): void
    {
        // ESP32 sends telemetry to custom slug /api/sensor/alat1sumedang/data
        $response = $this->postJson('/api/sensor/alat1sumedang/data', [
            'temperature' => 26.80,
            'tds' => 825.0,
            'voltage' => 1.66,
            'pump' => false,
            'auto' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('device_code', 'alat1sumedang');

        $this->assertDatabaseHas('devices', [
            'device_code' => 'alat1sumedang',
            'status' => 'online',
        ]);

        // Web controls custom device /api/sensor/alat1sumedang/control
        $controlResponse = $this->postJson('/api/sensor/alat1sumedang/control', [
            'pump' => true,
            'auto' => false,
            'target_tds' => 880.0,
        ]);

        $controlResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('device.pump_status', true)
            ->assertJsonPath('device.target_tds', 880);
    }

    public function test_mobile_app_login_endpoint(): void
    {
        $user = User::factory()->create([
            'email' => 'sumedang@agronex.id',
            'username' => 'usersumedang',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'usersumedang',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['token', 'user', 'devices']);
    }

    public function test_admin_can_update_device_features_and_owner(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $farmer = User::factory()->create([
            'role' => 'user',
        ]);

        $device = Device::create([
            'device_code' => 'alat_custom',
            'api_key' => 'alat_custom',
            'name' => 'Alat Custom',
            'location' => 'Kebun Barat',
            'has_tds' => true,
            'has_temp' => true,
            'has_pump' => true,
            'has_auto_mode' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.devices.update', $device->id), [
            'name' => 'Alat Sensor TDS Saja',
            'location' => 'Kebun Timur',
            'user_id' => $farmer->id,
            'target_tds' => 850,
            'has_tds' => '1',
            // has_temp, has_pump, has_auto_mode unchecked
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'name' => 'Alat Sensor TDS Saja',
            'user_id' => $farmer->id,
            'has_tds' => true,
            'has_temp' => false,
            'has_pump' => false,
            'has_auto_mode' => false,
        ]);
    }
}
