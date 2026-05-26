<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('jadwal_shifts');
        Schema::create('jadwal_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->tinyInteger('shift')->comment('1=00:15-07:45, 2=07:45-15:45, 3=15:45-23:45');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['tanggal', 'user_id'], 'unique_operator_per_hari');
            $table->index(['tanggal', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_shifts');
    }
};
