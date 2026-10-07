<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\HydroSenseApiController;
use App\Models\Device;
use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * Display centralized Management Dashboard for Admin.
     */
    public function index(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Akses terbatas untuk administrator.');
        }

        $devices = Device::with('user')
            ->withCount('readings')
            ->latest('id')
            ->get();

        $users = User::withCount('devices')
            ->latest('id')
            ->get();

        $allPetani = User::where('role', 'user')
            ->orderBy('name')
            ->get();

        $totalReadings = SensorReading::count();
        $onlineCount = $devices->filter(fn ($d) => $d->isOnline())->count();

        return view('admin.manage', compact(
            'devices',
            'users',
            'allPetani',
            'totalReadings',
            'onlineCount'
        ));
    }

    /**
     * Store new device with mandatory user binding and custom feature configuration.
     */
    public function storeDevice(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Akses terbatas untuk administrator.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'device_code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('devices', 'device_code')],
            'location' => ['required', 'string', 'max:100'],
            'user_id' => ['required', 'exists:users,id'], // Wajib terikat ke 1 pengguna
            'target_tds' => ['nullable', 'numeric', 'min:100', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:255'],
            'pump_names' => ['nullable', 'string'],
            'has_tds' => ['nullable', 'boolean'],
            'has_temp' => ['nullable', 'boolean'],
            'has_ph' => ['nullable', 'boolean'],
            'has_pump' => ['nullable', 'boolean'],
            'has_auto_mode' => ['nullable', 'boolean'],
        ]);

        $hasTds = $request->boolean('has_tds', true);
        $hasTemp = $request->boolean('has_temp', true);
        $hasPh = $request->boolean('has_ph', false);
        $hasPump = $request->boolean('has_pump', true);
        $hasAuto = $request->boolean('has_auto_mode', true);

        // Build pumps list if provided
        $pumpsList = [];
        if ($request->filled('pump_names')) {
            $rawNames = explode(',', $request->input('pump_names'));
            foreach ($rawNames as $rName) {
                $trimmed = trim($rName);
                if (! empty($trimmed)) {
                    $slug = Str::slug($trimmed, '_');
                    $pumpsList[] = [
                        'key' => $slug,
                        'name' => $trimmed,
                        'status' => false,
                    ];
                }
            }
        }

        if (empty($pumpsList) && $hasPump) {
            $pumpsList = [
                ['key' => 'pompa_sirkulasi', 'name' => 'Pompa Sirkulasi', 'status' => false],
            ];
        }

        $device = Device::create([
            'user_id' => $validated['user_id'],
            'name' => $validated['name'],
            'device_code' => strtolower($validated['device_code']),
            'api_key' => strtolower($validated['device_code']),
            'location' => $validated['location'],
            'target_tds' => $validated['target_tds'] ?? 800.0,
            'auto_mode' => $hasAuto,
            'pump_status' => false,
            'temperature' => 25.0,
            'tds' => 800.0,
            'ph' => $hasPh ? 6.2 : null,
            'voltage' => 1.6,
            'has_tds' => $hasTds,
            'has_temp' => $hasTemp,
            'has_ph' => $hasPh,
            'has_pump' => $hasPump,
            'pump_count' => count($pumpsList),
            'pump_controls' => $pumpsList,
            'has_auto_mode' => $hasAuto,
            'status' => 'offline',
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Instalasi baru "'.$device->name.'" ('.$device->device_code.') berhasil didaftarkan dan terikat ke pengguna pilihan.');
    }

    /**
     * Update device features, assigned user, and dynamic modular pumps.
     */
    public function updateDevice(Request $request, Device $device)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Akses terbatas untuk administrator.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'device_code' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('devices', 'device_code')->ignore($device->id)],
            'location' => ['required', 'string', 'max:100'],
            'user_id' => ['required', 'exists:users,id'], // Wajib terikat ke 1 pengguna
            'target_tds' => ['nullable', 'numeric', 'min:100', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:255'],
            'pump_names' => ['nullable', 'string'],
        ]);

        $hasTds = $request->has('has_tds');
        $hasTemp = $request->has('has_temp');
        $hasPh = $request->has('has_ph');
        $hasPump = $request->has('has_pump');
        $hasAuto = $request->has('has_auto_mode');

        $updateData = [
            'name' => $validated['name'],
            'location' => $validated['location'],
            'user_id' => $validated['user_id'],
            'target_tds' => $validated['target_tds'] ?? $device->target_tds,
            'notes' => $validated['notes'] ?? null,
            'has_tds' => $hasTds,
            'has_temp' => $hasTemp,
            'has_ph' => $hasPh,
            'has_pump' => $hasPump,
            'has_auto_mode' => $hasAuto,
        ];

        if (! empty($validated['device_code'])) {
            $updateData['device_code'] = strtolower($validated['device_code']);
            $updateData['api_key'] = strtolower($validated['device_code']);
        }

        // Handle custom multi-pump list (e.g. 5 pompa pupuk)
        if ($request->filled('pump_names')) {
            $rawNames = explode(',', $request->input('pump_names'));
            $newPumps = [];
            foreach ($rawNames as $rName) {
                $trimmed = trim($rName);
                if (! empty($trimmed)) {
                    $slug = Str::slug($trimmed, '_');
                    $newPumps[] = [
                        'key' => $slug,
                        'name' => $trimmed,
                        'status' => false,
                    ];
                }
            }
            if (! empty($newPumps)) {
                $updateData['pump_controls'] = $newPumps;
                $updateData['pump_count'] = count($newPumps);
                $updateData['has_pump'] = true;
            }
        }

        $device->update($updateData);

        return back()->with('success', 'Pengaturan dan kepemilikan instalasi "'.$device->name.'" berhasil diperbarui.');
    }

    /**
     * Simulate incoming JSON telemetry from Admin testing tools.
     */
    public function simulatePayload(Request $request, Device $device)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Akses terbatas untuk administrator.');
        }

        $rawJson = $request->input('payload_json');
        $data = json_decode($rawJson, true);

        if (! is_array($data)) {
            return back()->with('error', 'Format JSON tidak valid.');
        }

        $apiController = app(HydroSenseApiController::class);
        $fakeRequest = Request::create(
            '/api/sensor/'.$device->device_code.'/data',
            'POST',
            $data,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $apiController->recordTelemetry($fakeRequest, $device->device_code);

        return back()->with('success', 'Data JSON berhasil disimulasikan! Fitur dan sensor otomatis terdeteksi.');
    }

    /**
     * Delete device.
     */
    public function deleteDevice(Device $device)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Akses terbatas untuk administrator.');
        }

        $name = $device->name;
        $device->readings()->delete();
        $device->delete();

        return back()->with('success', 'Instalasi "'.$name.'" berhasil dihapus dari sistem.');
    }

    /**
     * Store new user account.
     */
    public function storeUser(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Akses terbatas untuk administrator.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['nullable', 'in:admin,user'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => strtolower($validated['email']),
            'role' => $validated['role'] ?? 'user',
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Akun pengguna "'.$user->name.'" berhasil dibuat! Silakan hubungkan dengan instalasi kebun.');
    }

    /**
     * Update user account.
     */
    public function updateUser(Request $request, User $user)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Akses terbatas untuk administrator.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['nullable', 'in:admin,user'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => strtolower($validated['email']),
            'role' => $validated['role'] ?? $user->role,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return back()->with('success', 'Data akun pengguna "'.$user->name.'" berhasil diperbarui.');
    }

    /**
     * Delete user account.
     */
    public function deleteUser(User $user)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Akses terbatas untuk administrator.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        }

        $name = $user->name;
        // Unassign devices before deleting user
        Device::where('user_id', $user->id)->update(['user_id' => null]);
        $user->delete();

        return back()->with('success', 'Akun pengguna "'.$name.'" berhasil dihapus dari sistem.');
    }
}
