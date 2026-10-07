<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;

class HydroSenseApiController extends Controller
{
    /**
     * Endpoint for ESP32 to send sensor data & receive latest control states.
     */
    public function recordTelemetry(Request $request)
    {
        $validated = $request->validate([
            'device_code' => ['required', 'string', 'max:50'],
            'temperature' => ['required', 'numeric'],
            'tds' => ['required', 'numeric'],
            'voltage' => ['nullable', 'numeric'],
            'pump' => ['nullable', 'boolean'],
            'auto' => ['nullable', 'boolean'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'wifi_ssid' => ['nullable', 'string', 'max:100'],
        ]);

        $device = Device::firstOrCreate(
            ['device_code' => $validated['device_code']],
            [
                'name' => 'HydroSense '.$validated['device_code'],
                'location' => 'Greenhouse Sumedang',
                'target_tds' => 800.0,
                'auto_mode' => $validated['auto'] ?? false,
                'pump_status' => $validated['pump'] ?? false,
            ]
        );

        // If ESP32 is in auto mode, sync device state; otherwise retain user-commanded pump state
        $pumpStatus = $device->auto_mode
            ? (bool) ($validated['pump'] ?? $device->pump_status)
            : $device->pump_status;

        $device->update([
            'temperature' => $validated['temperature'],
            'tds' => $validated['tds'],
            'voltage' => $validated['voltage'] ?? 0.0,
            'pump_status' => $pumpStatus,
            'ip_address' => $validated['ip_address'] ?? $device->ip_address,
            'wifi_ssid' => $validated['wifi_ssid'] ?? $device->wifi_ssid,
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        // Save sensor reading history log
        $device->readings()->create([
            'temperature' => $validated['temperature'],
            'tds' => $validated['tds'],
            'voltage' => $validated['voltage'] ?? 0.0,
            'pump_status' => $device->pump_status,
            'auto_mode' => $device->auto_mode,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Telemetry saved successfully',
            'server_time' => now()->toDateTimeString(),
            'control' => [
                'pump' => (bool) $device->pump_status,
                'auto' => (bool) $device->auto_mode,
                'target_tds' => (float) $device->target_tds,
            ],
        ]);
    }

    /**
     * Get latest telemetry & status for web dashboard.
     */
    public function getLatest(Request $request)
    {
        $deviceCode = $request->query('device_code', 'HYDROSENSE-01');
        $device = Device::where('device_code', $deviceCode)->first();

        if (! $device) {
            $device = Device::first();
        }

        if (! $device) {
            return response()->json([
                'status' => 'error',
                'message' => 'No device found',
            ], 404);
        }

        $isOnline = $device->isOnline();

        return response()->json([
            'status' => 'success',
            'device' => [
                'id' => $device->id,
                'device_code' => $device->device_code,
                'name' => $device->name,
                'location' => $device->location,
                'temperature' => (float) ($device->temperature ?? 0),
                'tds' => (float) ($device->tds ?? 0),
                'voltage' => (float) ($device->voltage ?? 0),
                'pump_status' => (bool) $device->pump_status,
                'auto_mode' => (bool) $device->auto_mode,
                'target_tds' => (float) $device->target_tds,
                'is_online' => $isOnline,
                'status' => $isOnline ? 'online' : 'offline',
                'ip_address' => $device->ip_address ?? '-',
                'wifi_ssid' => $device->wifi_ssid ?? 'Tidak Terhubung',
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
                'last_seen_formatted' => $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Belum pernah',
            ],
        ]);
    }

    /**
     * Update pump, auto mode, or target TDS from web dashboard.
     */
    public function updateControl(Request $request)
    {
        $validated = $request->validate([
            'device_code' => ['nullable', 'string'],
            'pump' => ['nullable', 'boolean'],
            'auto' => ['nullable', 'boolean'],
            'target_tds' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ]);

        $deviceCode = $validated['device_code'] ?? 'HYDROSENSE-01';
        $device = Device::where('device_code', $deviceCode)->firstOrFail();

        $updateData = [];

        if (isset($validated['auto'])) {
            $updateData['auto_mode'] = (bool) $validated['auto'];
        }

        if (isset($validated['pump'])) {
            $updateData['pump_status'] = (bool) $validated['pump'];
        }

        if (isset($validated['target_tds'])) {
            $updateData['target_tds'] = (float) $validated['target_tds'];
        }

        $device->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Kontrol perangkat berhasil diperbarui',
            'device' => [
                'pump_status' => (bool) $device->pump_status,
                'auto_mode' => (bool) $device->auto_mode,
                'target_tds' => (float) $device->target_tds,
            ],
        ]);
    }

    /**
     * Get historical sensor data for charts and logs.
     */
    public function getHistory(Request $request)
    {
        $deviceCode = $request->query('device_code', 'HYDROSENSE-01');
        $device = Device::where('device_code', $deviceCode)->first();

        if (! $device) {
            $device = Device::first();
        }

        if (! $device) {
            return response()->json(['status' => 'success', 'history' => []]);
        }

        $readings = $device->readings()
            ->latest('id')
            ->take(20)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($r) => [
                'id' => $r->id,
                'time' => $r->created_at->format('H:i:s'),
                'temperature' => (float) $r->temperature,
                'tds' => (float) $r->tds,
                'voltage' => (float) $r->voltage,
                'pump_status' => (bool) $r->pump_status,
            ]);

        return response()->json([
            'status' => 'success',
            'history' => $readings,
        ]);
    }
}
