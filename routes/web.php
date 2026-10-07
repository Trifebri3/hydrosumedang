<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Authentication Routes (Publik / Halaman Masuk Akun)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/quick-login/{role}', [AuthController::class, 'quickLogin'])->name('quick-login');
Route::any('/logout', [AuthController::class, 'logout'])->name('logout');

// Area Tertutup (Wajib Login - Tidak Ada Akses Publik)
Route::middleware('auth')->group(function () {
    // Main HydroSense Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Admin Management Routes (Membuat Alat Baru, Mengatur Fitur, Mengelola User Petani)
    Route::prefix('admin')->group(function () {
        Route::post('/devices', [AdminController::class, 'storeDevice'])->name('admin.devices.store');
        Route::put('/devices/{device}', [AdminController::class, 'updateDevice'])->name('admin.devices.update');
        Route::delete('/devices/{device}', [AdminController::class, 'deleteDevice'])->name('admin.devices.destroy');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    });
});
