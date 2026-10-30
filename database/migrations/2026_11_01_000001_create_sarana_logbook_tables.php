<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sarana_logs')) {
            Schema::create('sarana_logs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->date('tanggal');
                $t->unsignedTinyInteger('shift');          // nomor shift skema Sarana (JadwalShift::SKEMA_SARANA)
                $t->time('jam_mulai')->nullable();
                $t->time('jam_selesai')->nullable();
                $t->longText('catatan_petugas')->nullable(); // info untuk petugas berikutnya
                $t->timestamps();
                $t->index(['tanggal', 'shift']);
                $t->index('user_id');
            });
        }

        // Satu baris per butir pemeriksaan. Label disalin (snapshot) agar riwayat tetap utuh
        // walau nama/kapasitas genset di master berubah.
        if (!Schema::hasTable('sarana_log_items')) {
            Schema::create('sarana_log_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('sarana_log_id')->constrained('sarana_logs')->cascadeOnDelete();
                $t->string('kunci', 40);                   // alih_tugas, listrik_pln, ..., genset_{unit_id}
                $t->unsignedBigInteger('genset_unit_id')->nullable();
                $t->string('label');
                $t->string('deskripsi')->nullable();
                $t->string('kondisi', 10)->default('normal'); // normal | kendala
                $t->text('keterangan')->nullable();
                $t->unsignedSmallInteger('urutan')->default(0);
                $t->index(['sarana_log_id', 'urutan']);
            });
        }

        if (!Schema::hasTable('sarana_log_fotos')) {
            Schema::create('sarana_log_fotos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('sarana_log_id')->constrained('sarana_logs')->cascadeOnDelete();
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
        Schema::dropIfExists('sarana_log_fotos');
        Schema::dropIfExists('sarana_log_items');
        Schema::dropIfExists('sarana_logs');
    }
};
