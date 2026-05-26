<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('operasional_logs');
        Schema::create('operasional_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemancar_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('jadwal_shift_id')->nullable()->constrained('jadwal_shifts')->onDelete('set null');
            $table->dateTime('dicatat_pada');
            $table->decimal('output_final_pa', 10, 2)->nullable();
            $table->decimal('output_driver', 10, 2)->nullable();
            $table->decimal('output_exciter', 10, 2)->nullable();
            $table->decimal('reflect_final', 10, 2)->nullable();
            $table->decimal('reject_final', 10, 2)->nullable();
            $table->decimal('vswr_final', 7, 4)->nullable();
            $table->decimal('return_loss_final', 7, 3)->nullable();
            $table->decimal('suhu_pemancar', 5, 1)->nullable();
            $table->decimal('suhu_ruangan', 5, 1)->nullable();
            $table->decimal('kelembaban', 5, 1)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->index(['pemancar_id', 'dicatat_pada']);
            $table->index(['user_id', 'dicatat_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operasional_logs');
    }
};
