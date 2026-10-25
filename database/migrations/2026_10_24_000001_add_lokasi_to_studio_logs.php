<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('studio_logs', 'lokasi')) {
            Schema::table('studio_logs', function (Blueprint $t) {
                $t->string('lokasi', 30)->nullable()->after('user_id')->index();
            });
        }

        // Data lama: Way Kanan bila petugasnya berlokasi dinas Way Kanan, selain itu Pahoman
        DB::statement("UPDATE studio_logs SET lokasi = 'Way Kanan'
            WHERE lokasi IS NULL AND user_id IN (SELECT id FROM users WHERE LOWER(TRIM(lokasi_dinas)) = 'way kanan')");
        DB::statement("UPDATE studio_logs SET lokasi = 'Pahoman' WHERE lokasi IS NULL");
    }

    public function down(): void
    {
        if (Schema::hasColumn('studio_logs', 'lokasi')) {
            Schema::table('studio_logs', function (Blueprint $t) {
                $t->dropIndex(['lokasi']);
                $t->dropColumn('lokasi');
            });
        }
    }
};
