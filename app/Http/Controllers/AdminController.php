<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * Store new device with custom device_code and feature toggles.
     */
    public function storeDevice(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'device_code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('devices', 'device_code')],
            'location' => ['required', 'string', 'max:100'],
            'user_id' => ['nullable', 'exists:users,id'],
            'target_tds' => ['nullable', 'numeric', 'min:100', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:255'],
            'has_tds' => ['nullable', 'boolean'],
            'has_temp' => ['nullable', 'boolean'],
            'has_pump' => ['nullable', 'boolean'],
            'has_auto_mode' => ['nullable', 'boolean'],
        ]);

        $hasTds = $request->boolean('has_tds', true);
        $hasTemp = $request->boolean('has_temp', true);
        $hasPump = $request->boolean('has_pump', true);
        $hasAuto = $request->boolean('has_auto_mode', true);

        $device = Device::create([
            'user_id' => $validated['user_id'] ?? null,
            'name' => $validated['name'],
            'device_code' => strtolower($validated['device_code']),
            'api_key' => strtolower($validated['device_code']),
            'location' => $validated['location'],
            'target_tds' => $validated['target_tds'] ?? 800.0,
            'auto_mode' => $hasAuto,
            'pump_status' => false,
            'temperature' => 25.0,
            'tds' => 800.0,
            'voltage' => 1.6,
            'has_tds' => $hasTds,
            'has_temp' => $hasTemp,
            'has_pump' => $hasPump,
            'has_auto_mode' => $hasAuto,
            'status' => 'offline',
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Instalasi baru "'.$device->name.'" ('.$device->device_code.') berhasil didaftarkan ke sistem.');
    }

    /**
     * Update device features and assigned user.
     */
    public function updateDevice(Request $request, Device $device)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:100'],
            'user_id' => ['nullable', 'exists:users,id'],
            'target_tds' => ['nullable', 'numeric', 'min:100', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $hasTds = $request->has('has_tds');
        $hasTemp = $request->has('has_temp');
        $hasPump = $request->has('has_pump');
        $hasAuto = $request->has('has_auto_mode');

        $device->update([
            'name' => $validated['name'],
            'location' => $validated['location'],
            'user_id' => $validated['user_id'] ?? null,
            'target_tds' => $validated['target_tds'] ?? $device->target_tds,
            'notes' => $validated['notes'] ?? null,
            'has_tds' => $hasTds,
            'has_temp' => $hasTemp,
            'has_pump' => $hasPump,
            'has_auto_mode' => $hasAuto,
        ]);

        return back()->with('success', 'Pengaturan dan fitur instalasi "'.$device->name.'" berhasil diperbarui.');
    }

    /**
     * Delete device.
     */
    public function deleteDevice(Device $device)
    {
        $name = $device->name;
        $device->delete();

        return back()->with('success', 'Instalasi "'.$name.'" berhasil dihapus.');
    }

    /**
     * Store new client/farmer user account.
     */
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => strtolower($validated['email']),
            'role' => 'user',
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Akun petani "'.$user->name.'" berhasil dibuat! Silakan hubungkan dengan instalasi kebun.');
    }
}
