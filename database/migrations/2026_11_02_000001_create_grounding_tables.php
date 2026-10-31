<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('grounding_wilayahs')) {
            Schema::create('grounding_wilayahs', function (Blueprint $t) {
                $t->id();
                $t->string('nama');
                $t->string('lokasi')->nullable();
                $t->text('keterangan')->nullable();
                $t->string('peta_path')->nullable();      // denah / foto situasi (diunggah)
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('grounding_titiks')) {
            Schema::create('grounding_titiks', function (Blueprint $t) {
                $t->id();
                $t->foreignId('wilayah_id')->constrained('grounding_wilayahs')->cascadeOnDelete();
                $t->string('kode', 40);
                $t->string('nama');
                $t->string('peruntukan', 60)->nullable();       // pemancar, tower, panel, genset, penangkal petir, dll
                $t->decimal('pos_x', 6, 3)->nullable();         // posisi pada denah, persen 0-100
                $t->decimal('pos_y', 6, 3)->nullable();
                $t->decimal('latitude', 10, 7)->nullable();
                $t->decimal('longitude', 10, 7)->nullable();
                $t->string('tipe', 40)->nullable();             // rod, plat, mesh, ring, kombinasi, ...
                $t->decimal('kedalaman_m', 7, 2)->nullable();
                $t->string('bahan', 60)->nullable();            // tembaga, BC, galvanis, ...
                $t->decimal('diameter_mm', 7, 2)->nullable();
                $t->decimal('panjang_kabel_m', 8, 2)->nullable();
                $t->decimal('ukuran_kabel_mm2', 7, 2)->nullable();
                $t->unsignedSmallInteger('jumlah_elektroda')->nullable();
                $t->unsignedSmallInteger('tahun_pasang')->nullable();
                $t->decimal('batas_ohm', 8, 2)->default(5);     // batas maksimum tahanan
                $t->string('status', 12)->default('aktif');     // aktif | rusak | dibongkar
                $t->text('catatan')->nullable();
                $t->timestamps();
                $t->unique(['wilayah_id', 'kode']);
            });
        }

        if (!Schema::hasTable('grounding_titik_fotos')) {
            Schema::create('grounding_titik_fotos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('titik_id')->constrained('grounding_titiks')->cascadeOnDelete();
                $t->string('path');
                $t->string('thumb_path')->nullable();
                $t->string('keterangan', 255)->nullable();
                $t->unsignedInteger('urutan')->default(0);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('grounding_ukurs')) {
            Schema::create('grounding_ukurs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('titik_id')->constrained('grounding_titiks')->cascadeOnDelete();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->date('tanggal');
                $t->time('jam')->nullable();
                $t->decimal('nilai_ohm', 8, 2);
                $t->decimal('batas_ohm', 8, 2);                 // salinan batas saat diukur
                $t->string('status', 10);                       // baik | waspada | melebihi
                $t->string('kondisi_tanah', 10)->nullable();    // kering | lembab | basah
                $t->string('alat', 80)->nullable();
                $t->string('metode', 60)->nullable();
                $t->text('keterangan')->nullable();
                $t->timestamps();
                $t->index(['titik_id', 'tanggal']);
            });
        }

        if (!Schema::hasTable('grounding_ukur_fotos')) {
            Schema::create('grounding_ukur_fotos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('ukur_id')->constrained('grounding_ukurs')->cascadeOnDelete();
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
        Schema::dropIfExists('grounding_ukur_fotos');
        Schema::dropIfExists('grounding_ukurs');
        Schema::dropIfExists('grounding_titik_fotos');
        Schema::dropIfExists('grounding_titiks');
        Schema::dropIfExists('grounding_wilayahs');
    }
};
