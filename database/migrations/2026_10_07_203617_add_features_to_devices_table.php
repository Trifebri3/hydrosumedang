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
            $table->boolean('has_tds')->default(true)->after('voltage');
            $table->boolean('has_temp')->default(true)->after('has_tds');
            $table->boolean('has_pump')->default(true)->after('has_temp');
            $table->boolean('has_auto_mode')->default(true)->after('has_pump');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['has_tds', 'has_temp', 'has_pump', 'has_auto_mode']);
        });
    }
};
