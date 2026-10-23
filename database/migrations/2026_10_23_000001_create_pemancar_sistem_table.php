<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pemancar_sistem')) return;
        Schema::create('pemancar_sistem', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pemancar_id')->unique()->constrained('pemancars')->onDelete('cascade');

            // Antena
            $t->string('antena_merk', 100)->nullable();
            $t->string('antena_jenis', 100)->nullable();       // mis. panel, dipole, circular, yagi
            $t->unsignedSmallInteger('antena_bay')->nullable();
            $t->decimal('antena_max_power', 10, 2)->nullable(); // Watt

            // Kabel (feeder) pemancar -> antena
            $t->string('kabel_jenis', 100)->nullable();         // mis. 7/8" Heliax, 1-5/8", RG-213
            $t->decimal('kabel_panjang', 8, 2)->nullable();     // meter

            // Konektor antar komponen
            $t->string('konektor_exciter_driver', 60)->nullable();
            $t->string('konektor_driver_final', 60)->nullable();
            $t->string('konektor_final_antena', 60)->nullable();

            // Sumber audio
            $t->string('audio_sumber', 30)->nullable();         // STL | DVB | Streaming | Lainnya
            $t->string('audio_konektor', 60)->nullable();       // XLR, BNC, RCA, ...
            $t->string('audio_perangkat_merk', 100)->nullable();
            $t->string('audio_perangkat_tipe', 100)->nullable();
            $t->decimal('stl_frekuensi', 10, 3)->nullable();    // MHz

            // Audio processor
            $t->boolean('ap_terpasang')->nullable();
            $t->string('ap_merk', 100)->nullable();
            $t->string('ap_tipe', 100)->nullable();
            $t->text('ap_spek')->nullable();

            $t->text('catatan')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemancar_sistem');
    }
};
