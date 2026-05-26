<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pemancars');
        Schema::create('pemancars', function (Blueprint $table) {
            $table->id();
            $table->string('nama_stasiun');
            $table->string('merk');
            $table->string('tipe_unit');
            $table->enum('tipe_komponen', ['tabung', 'solid_state']);
            $table->enum('modulasi', ['AM', 'FM']);
            $table->string('lokasi')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('alamat_lokasi')->nullable();
            $table->decimal('kapasitas_output_final', 10, 2);
            $table->string('tipe_exciter')->nullable();
            $table->string('tipe_driver')->nullable();
            $table->decimal('frekuensi', 10, 3)->nullable();
            $table->string('nomor_izin')->nullable();
            $table->date('tanggal_instalasi')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemancars');
    }
};
