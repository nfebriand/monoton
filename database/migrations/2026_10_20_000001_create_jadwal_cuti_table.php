<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('jadwal_cuti')) return;
        Schema::create('jadwal_cuti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->string('jenis', 20)->default('cuti'); // cuti | izin | sakit | dinas_luar
            $table->string('keterangan', 200)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'tanggal'], 'unique_cuti_per_hari');
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_cuti');
    }
};
