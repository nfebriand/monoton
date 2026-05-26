<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('suhu_logs');
        Schema::create('suhu_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemancar_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('suhu_ruangan', 5, 1);
            $table->decimal('suhu_pemancar', 5, 1)->nullable();
            $table->decimal('kelembaban', 5, 1)->nullable();
            $table->dateTime('dicatat_pada');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->index(['pemancar_id', 'dicatat_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suhu_logs');
    }
};
