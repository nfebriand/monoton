<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pejabat_riwayat')) return;

        Schema::create('pejabat_riwayat', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 20)->index();           // transmisi|studio|studio_wk|sarana|kabid|kepala
            $t->string('nama', 100);
            $t->string('nip', 30)->nullable();
            $t->string('jabatan', 100)->nullable();
            $t->string('ttd_path', 500)->nullable();
            $t->date('berlaku_mulai')->nullable();     // null = sejak awal
            $t->date('berlaku_sampai');
            $t->string('keterangan', 200)->nullable();
            $t->timestamps();
            $t->index(['kode', 'berlaku_sampai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pejabat_riwayat');
    }
};
