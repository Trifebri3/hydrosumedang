<?php

namespace App\Http\Controllers;

use App\Models\Device;

class DashboardController extends Controller
{
    public function index()
    {
        $device = Device::firstOrCreate(
            ['device_code' => 'HYDROSENSE-01'],
            [
                'name' => 'HydroSense Sumedang',
                'location' => 'Greenhouse 01 - Sumedang',
                'target_tds' => 800.0,
                'auto_mode' => true,
                'pump_status' => false,
                'temperature' => 26.5,
                'tds' => 820.0,
                'voltage' => 1.65,
                'status' => 'online',
                'last_seen_at' => now(),
            ]
        );

        $readings = $device->readings()
            ->latest('id')
            ->take(15)
            ->get();

        $historyPoints = $device->readings()
            ->latest('id')
            ->take(20)
            ->get()
            ->reverse()
            ->values();

        return view('dashboard', compact('device', 'readings', 'historyPoints'));
    }
}
