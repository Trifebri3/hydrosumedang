<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_code')->unique()->index();
            $table->string('name')->default('HydroSense Sumedang');
            $table->string('location')->default('Greenhouse 01 - Sumedang');
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('tds', 7, 2)->nullable();
            $table->decimal('voltage', 4, 2)->nullable();
            $table->boolean('pump_status')->default(false);
            $table->boolean('auto_mode')->default(false);
            $table->decimal('target_tds', 7, 2)->default(800.0);
            $table->string('ip_address')->nullable();
            $table->string('wifi_ssid')->nullable();
            $table->string('status')->default('offline');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
