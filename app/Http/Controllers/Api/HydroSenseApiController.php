<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class HydroSenseApiController extends Controller
{
    /**
     * Endpoint for ESP32 to send sensor data & receive latest control states.
     * Supports POST /api/sensor/{device_code}/data or POST /api/sensor/data
     */
    public function recordTelemetry(Request $request, ?string $device_code = null)
    {
        $targetCode = $device_code
            ?? $request->input('device_code')
            ?? $request->input('api_key')
            ?? $request->header('X-Device-Code')
            ?? $request->header('X-Device-Token');

        if (! $targetCode) {
            return response()->json([
                'status' => 'error',
                'message' => 'device_code atau api_key wajib diisi.',
            ], 422);
        }

        $validated = $request->validate([
            'temperature' => ['nullable', 'numeric'],
            'tds' => ['nullable', 'numeric'],
            'voltage' => ['nullable', 'numeric'],
            'pump' => ['nullable', 'boolean'],
            'auto' => ['nullable', 'boolean'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'wifi_ssid' => ['nullable', 'string', 'max:100'],
        ]);

        $device = Device::where('device_code', $targetCode)
            ->orWhere('api_key', $targetCode)
            ->first();

        if (! $device) {
            $device = Device::create([
                'device_code' => $targetCode,
                'api_key' => $targetCode,
                'name' => 'HydroSense '.$targetCode,
                'location' => 'Greenhouse Hidroponik',
                'target_tds' => 800.0,
                'has_tds' => true,
                'has_temp' => true,
                'has_pump' => true,
                'has_auto_mode' => true,
                'auto_mode' => $validated['auto'] ?? true,
                'pump_status' => $validated['pump'] ?? false,
            ]);
        }

        $pumpStatus = $device->has_pump && $device->auto_mode
            ? (bool) ($validated['pump'] ?? $device->pump_status)
            : ($device->has_pump ? $device->pump_status : false);

        $tempValue = $device->has_temp
            ? (float) ($validated['temperature'] ?? $device->temperature ?? 25.0)
            : 0.0;

        $tdsValue = $device->has_tds
            ? (float) ($validated['tds'] ?? $device->tds ?? 0.0)
            : 0.0;

        $device->update([
            'temperature' => $tempValue,
            'tds' => $tdsValue,
            'voltage' => $validated['voltage'] ?? $device->voltage ?? 0.0,
            'pump_status' => $pumpStatus,
            'ip_address' => $validated['ip_address'] ?? $device->ip_address,
            'wifi_ssid' => $validated['wifi_ssid'] ?? $device->wifi_ssid,
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        $device->readings()->create([
            'temperature' => $tempValue,
            'tds' => $tdsValue,
            'voltage' => $validated['voltage'] ?? 0.0,
            'pump_status' => $device->pump_status,
            'auto_mode' => $device->auto_mode,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Telemetry saved successfully',
            'device_code' => $device->device_code,
            'server_time' => now()->toDateTimeString(),
            'control' => [
                'pump' => $device->has_pump ? (bool) $device->pump_status : false,
                'auto' => $device->has_auto_mode ? (bool) $device->auto_mode : false,
                'target_tds' => (float) $device->target_tds,
            ],
            'features' => [
                'has_tds' => (bool) $device->has_tds,
                'has_temp' => (bool) $device->has_temp,
                'has_pump' => (bool) $device->has_pump,
                'has_auto_mode' => (bool) $device->has_auto_mode,
            ],
        ]);
    }

    /**
     * Get latest telemetry & status for web dashboard or mobile app.
     */
    public function getLatest(Request $request, ?string $device_code = null)
    {
        $targetCode = $device_code ?? $request->query('device_code', $request->query('api_key'));

        $query = Device::query();
        if ($targetCode) {
            $query->where(fn ($q) => $q->where('device_code', $targetCode)->orWhere('api_key', $targetCode));
        }

        $device = $query->first();

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
                'api_key' => $device->api_key ?? $device->device_code,
                'name' => $device->name,
                'location' => $device->location,
                'notes' => $device->notes,
                'owner' => $device->user?->name ?? 'Belum Ditugaskan',
                'temperature' => (float) ($device->temperature ?? 0),
                'tds' => (float) ($device->tds ?? 0),
                'voltage' => (float) ($device->voltage ?? 0),
                'has_tds' => (bool) $device->has_tds,
                'has_temp' => (bool) $device->has_temp,
                'has_pump' => (bool) $device->has_pump,
                'has_auto_mode' => (bool) $device->has_auto_mode,
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
     * Update pump, auto mode, or target TDS.
     */
    public function updateControl(Request $request, ?string $device_code = null)
    {
        $targetCode = $device_code
            ?? $request->input('device_code')
            ?? $request->input('api_key')
            ?? 'alat1sumedang';

        $device = Device::where('device_code', $targetCode)
            ->orWhere('api_key', $targetCode)
            ->first();

        if (! $device) {
            $device = Device::firstOrFail();
        }

        $validated = $request->validate([
            'pump' => ['nullable', 'boolean'],
            'auto' => ['nullable', 'boolean'],
            'target_tds' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ]);

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
            'message' => 'Kontrol perangkat '.$device->device_code.' berhasil diperbarui',
            'device' => [
                'device_code' => $device->device_code,
                'pump_status' => (bool) $device->pump_status,
                'auto_mode' => (bool) $device->auto_mode,
                'target_tds' => (float) $device->target_tds,
            ],
        ]);
    }

    /**
     * Get historical sensor data for charts and logs.
     */
    public function getHistory(Request $request, ?string $device_code = null)
    {
        $targetCode = $device_code ?? $request->query('device_code', $request->query('api_key'));

        $query = Device::query();
        if ($targetCode) {
            $query->where(fn ($q) => $q->where('device_code', $targetCode)->orWhere('api_key', $targetCode));
        }

        $device = $query->first();

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
            'device_code' => $device->device_code,
            'history' => $readings,
        ]);
    }

    /**
     * Mobile App API Login (Untuk dibawa ke aplikasi Android / iOS).
     */
    public function mobileLogin(Request $request)
    {
        $validated = $request->validate([
            'login' => ['required', 'string'], // email or username
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['login'])
            ->orWhere('username', $validated['login'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email/Username atau password salah',
            ], 401);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        $devices = $user->isAdmin()
            ? Device::all()
            : $user->devices;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'devices' => $devices->map(fn ($d) => [
                'device_code' => $d->device_code,
                'api_key' => $d->api_key,
                'name' => $d->name,
                'location' => $d->location,
                'temperature' => (float) $d->temperature,
                'tds' => (float) $d->tds,
                'has_tds' => (bool) $d->has_tds,
                'has_temp' => (bool) $d->has_temp,
                'has_pump' => (bool) $d->has_pump,
                'has_auto_mode' => (bool) $d->has_auto_mode,
                'pump_status' => (bool) $d->pump_status,
                'auto_mode' => (bool) $d->auto_mode,
                'is_online' => $d->isOnline(),
            ]),
        ]);
    }
}
