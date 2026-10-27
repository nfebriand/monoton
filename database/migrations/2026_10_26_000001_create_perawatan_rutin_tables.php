<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('perawatan_rutin')) {
            Schema::create('perawatan_rutin', function (Blueprint $t) {
                $t->id();
                $t->string('objek_tipe', 20);                         // pemancar | genset
                $t->unsignedBigInteger('pemancar_id')->nullable()->index();
                $t->unsignedBigInteger('genset_unit_id')->nullable()->index();
                $t->string('nama_tugas', 150);
                $t->string('basis', 12)->default('tanggal');          // tanggal | hm | keduanya
                $t->unsignedInteger('interval_hari')->nullable();
                $t->decimal('interval_hm', 10, 1)->nullable();
                $t->date('terakhir_tanggal')->nullable();
                $t->decimal('terakhir_hm', 10, 1)->nullable();
                $t->boolean('aktif')->default(true);
                $t->text('catatan')->nullable();
                $t->unsignedBigInteger('dibuat_oleh')->nullable();
                $t->timestamps();
                $t->index(['objek_tipe', 'aktif']);
            });
        }

        if (!Schema::hasTable('perawatan_rutin_log')) {
            Schema::create('perawatan_rutin_log', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('perawatan_rutin_id')->index();
                $t->unsignedBigInteger('user_id')->nullable();
                $t->date('tanggal');
                $t->decimal('hm', 10, 1)->nullable();
                $t->text('catatan')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('perawatan_rutin_log');
        Schema::dropIfExists('perawatan_rutin');
    }
};
