<?php

use App\Http\Controllers\Api\HydroSenseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// HydroSense IoT API Routes
Route::prefix('sensor')->group(function () {
    // ESP32 sends telemetry & gets latest pump/mode/target controls
    Route::post('/data', [HydroSenseApiController::class, 'recordTelemetry']);

    // Web Dashboard reads real-time status
    Route::get('/latest', [HydroSenseApiController::class, 'getLatest']);

    // Web Dashboard commands (Pump ON/OFF, AUTO/MANUAL, Target TDS)
    Route::post('/control', [HydroSenseApiController::class, 'updateControl']);

    // Historical telemetry for charts & logs
    Route::get('/history', [HydroSenseApiController::class, 'getHistory']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
