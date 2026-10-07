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

    // Admin Management Central Routes (Panel Manajemen Khusus Admin)
    Route::prefix('admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('admin.manage');
        Route::post('/devices', [AdminController::class, 'storeDevice'])->name('admin.devices.store');
        Route::put('/devices/{device}', [AdminController::class, 'updateDevice'])->name('admin.devices.update');
        Route::post('/devices/{device}/simulate', [AdminController::class, 'simulatePayload'])->name('admin.devices.simulate');
        Route::delete('/devices/{device}', [AdminController::class, 'deleteDevice'])->name('admin.devices.destroy');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('admin.users.destroy');
    });
});
