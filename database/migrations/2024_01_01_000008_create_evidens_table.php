<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::dropIfExists('eviden_operators');
        Schema::dropIfExists('eviden_fotos');
        Schema::dropIfExists('evidens');
        Schema::create('evidens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('lokasi')->nullable();
            $table->string('supervisi')->nullable()->comment('Nama pengelola/koordinator yang hadir');
            $table->timestamps();
        });
        Schema::create('eviden_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eviden_id')->constrained()->onDelete('cascade');
            $table->string('path');
            $table->string('keterangan')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
        Schema::create('eviden_operators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eviden_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('eviden_operators');
        Schema::dropIfExists('eviden_fotos');
        Schema::dropIfExists('evidens');
    }
};
