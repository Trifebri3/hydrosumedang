<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HydroSenseApiController extends Controller
{
    /**
     * Endpoint for ESP32/IoT to send sensor data & receive latest control states.
     * Supports POST /api/sensor/{device_code}/data or POST /api/sensor/data
     * Modular: Automatically discovers and accepts extra sensors (pH, water_level) and multiple pumps!
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
            'ph' => ['nullable', 'numeric'],
            'voltage' => ['nullable', 'numeric'],
            'pump' => ['nullable', 'boolean'],
            'pumps' => ['nullable'],
            'sensors' => ['nullable'],
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

        $rawPayload = $request->all();

        // Modular Sensors Extraction
        $tempValue = $validated['temperature'] ?? $request->input('sensors.temperature') ?? $device->temperature ?? 25.0;
        $tdsValue = $validated['tds'] ?? $request->input('sensors.tds') ?? $device->tds ?? 800.0;
        $phValue = $validated['ph'] ?? $request->input('sensors.ph') ?? $device->ph;

        // Auto-discover any custom NoSQL sensor attributes sent in JSON
        $systemKeys = ['device_code', 'api_key', 'temperature', 'tds', 'ph', 'voltage', 'pump', 'pumps', 'sensors', 'auto', 'ip_address', 'wifi_ssid', '_token', 'code'];
        $customSensors = [];
        foreach ($rawPayload as $k => $v) {
            if (! in_array(strtolower($k), $systemKeys) && (is_numeric($v) || is_string($v) || is_bool($v))) {
                $customSensors[$k] = $v;
            }
        }
        if (is_array($request->input('sensors'))) {
            $customSensors = array_merge($customSensors, $request->input('sensors'));
        }
        $finalExtraSensors = ! empty($customSensors) ? $customSensors : ($device->extra_sensors ?? []);

        // Modular Pumps Discovery & Sync
        $existingPumps = $device->getPumpsList();
        $incomingPumps = $request->input('pumps');

        if (is_array($incomingPumps)) {
            // Can be key-value map {"pompa_sirkulasi": true, "pompa_pupuk_a": false} or indexed array
            foreach ($incomingPumps as $k => $v) {
                $pumpKey = is_string($k) ? $k : ($v['key'] ?? 'pump_'.$k);
                $pumpStatus = is_bool($v) ? $v : (bool) ($v['status'] ?? false);

                $found = false;
                foreach ($existingPumps as &$ep) {
                    if ($ep['key'] === $pumpKey) {
                        // Keep server state if in manual mode, or accept device state if auto mode
                        $ep['status'] = $device->auto_mode ? $pumpStatus : $ep['status'];
                        $found = true;
                        break;
                    }
                }
                unset($ep);

                if (! $found) {
                    $friendlyName = Str::headline(str_replace(['_', '-'], ' ', $pumpKey));
                    $existingPumps[] = [
                        'key' => $pumpKey,
                        'name' => $friendlyName,
                        'status' => $pumpStatus,
                    ];
                }
            }
        } elseif ($request->has('pump') && empty($existingPumps)) {
            $existingPumps = [
                [
                    'key' => 'pompa_sirkulasi',
                    'name' => 'Pompa Sirkulasi',
                    'status' => (bool) $request->input('pump'),
                ],
            ];
        }

        $mainPumpStatus = ! empty($existingPumps) ? (bool) ($existingPumps[0]['status'] ?? false) : false;
        if ($request->has('pump') && $device->auto_mode) {
            $mainPumpStatus = (bool) $request->input('pump');
            if (! empty($existingPumps)) {
                $existingPumps[0]['status'] = $mainPumpStatus;
            }
        }

        $hasPhDetected = $phValue !== null || isset($rawPayload['ph']) || isset($rawPayload['sensors']['ph']);
        $hasPumpDetected = ! empty($existingPumps) || $request->has('pump');

        $device->update([
            'temperature' => (float) $tempValue,
            'tds' => (float) $tdsValue,
            'ph' => $phValue !== null ? (float) $phValue : null,
            'voltage' => $validated['voltage'] ?? $device->voltage ?? 0.0,
            'has_ph' => $hasPhDetected || (bool) $device->has_ph,
            'has_pump' => $hasPumpDetected || (bool) $device->has_pump,
            'pump_count' => count($existingPumps),
            'pump_status' => $mainPumpStatus,
            'pump_controls' => $existingPumps,
            'extra_sensors' => $finalExtraSensors,
            'last_payload' => $rawPayload,
            'ip_address' => $validated['ip_address'] ?? $device->ip_address,
            'wifi_ssid' => $validated['wifi_ssid'] ?? $device->wifi_ssid,
            'status' => 'online',
            'last_seen_at' => now(),
        ]);

        // Record historical telemetry (NoSQL Raw Document + Processed Columns)
        $device->readings()->create([
            'temperature' => (float) $tempValue,
            'tds' => (float) $tdsValue,
            'ph' => $phValue !== null ? (float) $phValue : null,
            'voltage' => $validated['voltage'] ?? 0.0,
            'pump_status' => $device->pump_status,
            'auto_mode' => $device->auto_mode,
            'sensor_data' => $finalExtraSensors,
            'pump_data' => $existingPumps,
            'raw_payload' => $rawPayload,
        ]);

        // Build associative map for easy ESP32 pump lookup
        $pumpsMap = [];
        foreach ($existingPumps as $p) {
            $pumpsMap[$p['key']] = (bool) $p['status'];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Telemetry saved successfully (NoSQL Document stored)',
            'device_code' => $device->device_code,
            'server_time' => now()->toDateTimeString(),
            'control' => [
                'pump' => $device->has_pump ? (bool) $device->pump_status : false,
                'auto' => $device->has_auto_mode ? (bool) $device->auto_mode : false,
                'target_tds' => (float) $device->target_tds,
                'pumps' => $pumpsMap,
            ],
            'pumps_list' => $existingPumps,
            'extra_sensors' => $finalExtraSensors,
            'last_payload' => $rawPayload,
            'features' => [
                'has_tds' => (bool) $device->has_tds,
                'has_temp' => (bool) $device->has_temp,
                'has_ph' => $device->hasPh(),
                'has_pump' => $device->has_pump,
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
        $pumpsList = $device->getPumpsList();

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
                'ph' => $device->ph !== null ? (float) $device->ph : null,
                'has_ph' => $device->hasPh(),
                'voltage' => (float) ($device->voltage ?? 0),
                'has_tds' => (bool) $device->has_tds,
                'has_temp' => (bool) $device->has_temp,
                'has_pump' => (bool) $device->has_pump,
                'has_auto_mode' => (bool) $device->has_auto_mode,
                'pump_status' => (bool) $device->pump_status,
                'pumps_list' => $pumpsList,
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
     * Update pump, specific pump key, auto mode, or target TDS.
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
            'pump_key' => ['nullable', 'string'],
            'pump_state' => ['nullable', 'boolean'],
            'pumps' => ['nullable'],
            'auto' => ['nullable', 'boolean'],
            'target_tds' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ]);

        $updateData = [];

        if (isset($validated['auto'])) {
            $updateData['auto_mode'] = (bool) $validated['auto'];
        }

        if (isset($validated['target_tds'])) {
            $updateData['target_tds'] = (float) $validated['target_tds'];
        }

        $pumpsList = $device->getPumpsList();

        // Single specific pump toggle (e.g. pump_pupuk_a = true)
        if ($request->has('pump_key') && $request->has('pump_state')) {
            $key = $request->input('pump_key');
            $state = (bool) $request->input('pump_state');

            foreach ($pumpsList as &$p) {
                if ($p['key'] === $key) {
                    $p['status'] = $state;
                    break;
                }
            }
            unset($p);

            $updateData['pump_controls'] = $pumpsList;
            if (! empty($pumpsList) && $pumpsList[0]['key'] === $key) {
                $updateData['pump_status'] = $state;
            }
        } elseif (isset($validated['pump'])) {
            $state = (bool) $validated['pump'];
            $updateData['pump_status'] = $state;
            if (! empty($pumpsList)) {
                $pumpsList[0]['status'] = $state;
                $updateData['pump_controls'] = $pumpsList;
            }
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
                'pumps_list' => $device->getPumpsList(),
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
            return response()->json(['status' => 'error', 'message' => 'Device not found'], 404);
        }

        $readings = $device->readings()
            ->latest('id')
            ->take(20)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($r) => [
                'time' => $r->created_at->format('H:i:s'),
                'temperature' => (float) $r->temperature,
                'tds' => (float) $r->tds,
                'ph' => $r->ph !== null ? (float) $r->ph : null,
                'pump_status' => (bool) $r->pump_status,
                'auto_mode' => (bool) $r->auto_mode,
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
            'login' => ['required', 'string'],
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
                'ph' => $d->ph !== null ? (float) $d->ph : null,
                'has_ph' => $d->hasPh(),
                'has_tds' => (bool) $d->has_tds,
                'has_temp' => (bool) $d->has_temp,
                'has_pump' => (bool) $d->has_pump,
                'has_auto_mode' => (bool) $d->has_auto_mode,
                'pumps_list' => $d->getPumpsList(),
                'pump_status' => (bool) $d->pump_status,
                'auto_mode' => (bool) $d->auto_mode,
                'is_online' => $d->isOnline(),
            ]),
        ]);
    }
}
