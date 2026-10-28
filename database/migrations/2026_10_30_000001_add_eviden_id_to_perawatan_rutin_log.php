<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('perawatan_rutin_log') || Schema::hasColumn('perawatan_rutin_log', 'eviden_id')) return;

        Schema::table('perawatan_rutin_log', function (Blueprint $t) {
            $t->unsignedBigInteger('eviden_id')->nullable()->after('catatan')->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('perawatan_rutin_log') && Schema::hasColumn('perawatan_rutin_log', 'eviden_id')) {
            Schema::table('perawatan_rutin_log', function (Blueprint $t) {
                $t->dropColumn('eviden_id');
            });
        }
    }
};
