<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('skema_shifts', 'lokasi')) {
            Schema::table('skema_shifts', function (Blueprint $t) {
                $t->string('lokasi', 100)->nullable()->after('divisi')->comment('Lokasi dinas yang memakai skema ini (kosong = umum)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('skema_shifts', 'lokasi')) {
            Schema::table('skema_shifts', fn (Blueprint $t) => $t->dropColumn('lokasi'));
        }
    }
};
