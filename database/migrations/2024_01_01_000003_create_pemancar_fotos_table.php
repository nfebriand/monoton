<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pemancar_fotos');
        Schema::create('pemancar_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemancar_id')->constrained()->onDelete('cascade');
            $table->string('path');
            $table->string('keterangan')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemancar_fotos');
    }
};
