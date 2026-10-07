<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userSumedang = User::where('email', 'sumedang@agronex.id')->first();

        $device = Device::firstOrCreate(
            ['device_code' => 'alat1sumedang'],
            [
                'user_id' => $userSumedang?->id,
                'api_key' => 'alat1sumedang',
                'name' => 'HydroSense Sumedang Unit 1',
                'location' => 'Greenhouse Cisewu - Sumedang',
                'temperature' => 26.50,
                'tds' => 820.00,
                'voltage' => 1.65,
                'pump_status' => false,
                'auto_mode' => true,
                'target_tds' => 800.00,
                'ip_address' => '192.168.1.105',
                'wifi_ssid' => 'Agronex_SmartFarm',
                'status' => 'online',
                'notes' => 'Instalasi Hidroponik NFT Selada & Pakcoy',
                'last_seen_at' => now(),
            ]
        );

        // Also ensure HYDROSENSE-01 links to userSumedang if it exists
        Device::where('device_code', 'HYDROSENSE-01')->update([
            'user_id' => $userSumedang?->id,
            'api_key' => 'HYDROSENSE-01',
        ]);

        // Seed sample history points if empty
        if ($device->readings()->count() === 0) {
            for ($i = 9; $i >= 0; $i--) {
                $time = now()->subMinutes($i * 3);
                $device->readings()->create([
                    'temperature' => round(25.5 + (rand(0, 20) / 10), 2),
                    'tds' => round(780 + rand(-40, 60), 2),
                    'voltage' => round(1.5 + (rand(0, 30) / 100), 2),
                    'pump_status' => ($i % 3 === 0),
                    'auto_mode' => true,
                    'created_at' => $time,
                    'updated_at' => $time,
                ]);
            }
        }
    }
}
