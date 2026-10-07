<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get accessible devices for this session
        if ($user && ! $user->isAdmin()) {
            $devices = $user->devices()->with('user')->get();
            // If user has no devices assigned yet, fallback to all or empty
            if ($devices->isEmpty()) {
                $devices = Device::with('user')->get();
            }
        } else {
            $devices = Device::with('user')->get();
        }

        // Selected device
        $selectedCode = $request->query('device');
        $device = null;

        if ($selectedCode) {
            $device = $devices->firstWhere('device_code', $selectedCode)
                ?? Device::where('device_code', $selectedCode)->orWhere('api_key', $selectedCode)->first();
        }

        if (! $device) {
            $device = $devices->firstWhere('device_code', 'alat1sumedang')
                ?? $devices->first();
        }

        // If database was completely empty, create default alat1sumedang
        if (! $device) {
            $defaultUser = User::where('role', 'user')->first();
            $device = Device::create([
                'user_id' => $defaultUser?->id,
                'device_code' => 'alat1sumedang',
                'api_key' => 'alat1sumedang',
                'name' => 'HydroSense Sumedang Unit 1',
                'location' => 'Greenhouse Cisewu - Sumedang',
                'target_tds' => 800.0,
                'auto_mode' => true,
                'pump_status' => false,
                'temperature' => 26.5,
                'tds' => 820.0,
                'voltage' => 1.65,
                'status' => 'online',
                'last_seen_at' => now(),
            ]);
            $devices = collect([$device]);
        }

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

        $allUsers = User::where('role', 'user')->get();

        return view('dashboard', compact('device', 'devices', 'readings', 'historyPoints', 'allUsers'));
    }
}
