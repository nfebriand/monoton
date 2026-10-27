<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('studio_maintenance')) return;

        Schema::table('studio_maintenance', function (Blueprint $t) {
            if (!Schema::hasColumn('studio_maintenance', 'rencana_maintenance_berikutnya')) {
                $t->date('rencana_maintenance_berikutnya')->nullable();
            }
            if (!Schema::hasColumn('studio_maintenance', 'keterangan')) {
                $t->text('keterangan')->nullable();
            }
            if (!Schema::hasColumn('studio_maintenance', 'created_at')) {
                $t->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('studio_maintenance', 'updated_at')) {
                $t->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // kolom dibiarkan (data tidak dihapus)
    }
};
