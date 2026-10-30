<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('catatan_dinas')) {
            Schema::create('catatan_dinas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->string('divisi', 30)->nullable();      // divisi pembuat saat dicatat
                $t->string('lokasi_dinas')->nullable();    // lokasi dinas pembuat (dasar visibilitas serah terima)
                $t->string('lokasi')->nullable();          // tempat kejadian (teks bebas)
                $t->date('tanggal');
                $t->time('jam_mulai')->nullable();
                $t->time('jam_selesai')->nullable();
                $t->string('kategori', 20)->default('biasa');
                $t->string('judul');
                $t->longText('narasi');                    // narasi panjang (hasil ketik / dikte)
                $t->timestamps();
                $t->index(['tanggal', 'user_id']);
                $t->index('divisi');
                $t->index('lokasi_dinas');
            });
        }

        if (!Schema::hasTable('catatan_dinas_fotos')) {
            Schema::create('catatan_dinas_fotos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('catatan_dinas_id')->constrained('catatan_dinas')->cascadeOnDelete();
                $t->string('path');
                $t->string('thumb_path')->nullable();
                $t->string('keterangan', 255)->nullable();
                $t->unsignedInteger('urutan')->default(0);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catatan_dinas_fotos');
        Schema::dropIfExists('catatan_dinas');
    }
};
