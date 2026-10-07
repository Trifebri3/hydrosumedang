<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['login'])
            ->orWhere('username', $credentials['login'])
            ->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang, '.$user->name.'!');
        }

        return back()->withErrors([
            'login' => 'Email/Username atau password yang dimasukkan salah.',
        ])->onlyInput('login');
    }

    public function quickLogin(string $userType)
    {
        if ($userType === 'admin') {
            $user = User::where('role', 'admin')->first();
        } else {
            $user = User::where('email', 'sumedang@agronex.id')->first()
                ?? User::where('role', 'user')->first();
        }

        if ($user) {
            Auth::login($user);
            request()->session()->regenerate();

            return redirect()->route('dashboard')
                ->with('success', 'Berhasil login sebagai '.$user->name);
        }

        return redirect()->route('login')->with('error', 'Akun tidak ditemukan.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }
}
