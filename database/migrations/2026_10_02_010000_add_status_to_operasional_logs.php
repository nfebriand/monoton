<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operasional_logs', function (Blueprint $table) {
            $table->enum('status', ['on', 'off'])->default('on')->after('pemancar_id')
                ->comment('Status pemancar saat dicatat. OFF = pemancar sedang tidak mengudara (bergantian dengan unit lain) dan TIDAK dihitung dalam rata-rata rekap.');
        });
    }

    public function down(): void
    {
        Schema::table('operasional_logs', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
