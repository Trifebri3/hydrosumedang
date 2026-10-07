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
        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'last_payload')) {
                $table->json('last_payload')->nullable()->after('extra_sensors');
            }
        });

        Schema::table('sensor_readings', function (Blueprint $table) {
            if (! Schema::hasColumn('sensor_readings', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('pump_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            if (Schema::hasColumn('sensor_readings', 'raw_payload')) {
                $table->dropColumn('raw_payload');
            }
        });

        Schema::table('devices', function (Blueprint $table) {
            if (Schema::hasColumn('devices', 'last_payload')) {
                $table->dropColumn('last_payload');
            }
        });
    }
};
