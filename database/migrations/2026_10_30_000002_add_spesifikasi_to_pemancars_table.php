<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pemancars')) return;

        Schema::table('pemancars', function (Blueprint $t) {
            if (!Schema::hasColumn('pemancars', 'tahun_pembuatan')) {
                $t->unsignedSmallInteger('tahun_pembuatan')->nullable();
            }
            if (!Schema::hasColumn('pemancars', 'kapasitas_output_driver')) {
                $t->decimal('kapasitas_output_driver', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('pemancars', 'kapasitas_output_exciter')) {
                $t->decimal('kapasitas_output_exciter', 10, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        // kolom dibiarkan (data tidak dihapus)
    }
};
