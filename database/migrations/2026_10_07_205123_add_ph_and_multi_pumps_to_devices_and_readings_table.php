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
            if (! Schema::hasColumn('devices', 'ph')) {
                $table->float('ph')->nullable()->after('tds');
            }
            if (! Schema::hasColumn('devices', 'has_ph')) {
                $table->boolean('has_ph')->default(false)->after('ph');
            }
            if (! Schema::hasColumn('devices', 'pump_count')) {
                $table->integer('pump_count')->default(1)->after('has_pump');
            }
            if (! Schema::hasColumn('devices', 'pump_labels')) {
                $table->json('pump_labels')->nullable()->after('pump_count');
            }
            if (! Schema::hasColumn('devices', 'pump_states')) {
                $table->json('pump_states')->nullable()->after('pump_labels');
            }
            if (! Schema::hasColumn('devices', 'sensor_schema')) {
                $table->json('sensor_schema')->nullable()->after('has_auto_mode');
            }
            if (! Schema::hasColumn('devices', 'pump_controls')) {
                $table->json('pump_controls')->nullable()->after('sensor_schema');
            }
            if (! Schema::hasColumn('devices', 'extra_sensors')) {
                $table->json('extra_sensors')->nullable()->after('pump_controls');
            }
        });

        Schema::table('sensor_readings', function (Blueprint $table) {
            if (! Schema::hasColumn('sensor_readings', 'ph')) {
                $table->float('ph')->nullable()->after('tds');
            }
            if (! Schema::hasColumn('sensor_readings', 'sensor_data')) {
                $table->json('sensor_data')->nullable()->after('auto_mode');
            }
            if (! Schema::hasColumn('sensor_readings', 'pump_data')) {
                $table->json('pump_data')->nullable()->after('sensor_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $cols = array_filter(['ph', 'sensor_data', 'pump_data'], fn ($c) => Schema::hasColumn('sensor_readings', $c));
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('devices', function (Blueprint $table) {
            $cols = array_filter(['ph', 'sensor_schema', 'pump_controls', 'extra_sensors'], fn ($c) => Schema::hasColumn('devices', $c));
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
