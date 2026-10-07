<?php

use App\Http\Controllers\Api\HydroSenseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Mobile App Authentication Endpoint
Route::post('/auth/login', [HydroSenseApiController::class, 'mobileLogin']);

// HydroSense IoT API Routes (Global & Per-Device)
Route::prefix('sensor')->group(function () {
    // 1. Per-Device Custom Slug Endpoints (misal: /api/sensor/alat1sumedang/data)
    Route::post('/{device_code}/data', [HydroSenseApiController::class, 'recordTelemetry']);
    Route::get('/{device_code}/latest', [HydroSenseApiController::class, 'getLatest']);
    Route::post('/{device_code}/control', [HydroSenseApiController::class, 'updateControl']);
    Route::get('/{device_code}/history', [HydroSenseApiController::class, 'getHistory']);

    // 2. Global Endpoints (device_code dikirim di JSON body atau query param)
    Route::post('/data', [HydroSenseApiController::class, 'recordTelemetry']);
    Route::get('/latest', [HydroSenseApiController::class, 'getLatest']);
    Route::post('/control', [HydroSenseApiController::class, 'updateControl']);
    Route::get('/history', [HydroSenseApiController::class, 'getHistory']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load('devices');
    });
});
