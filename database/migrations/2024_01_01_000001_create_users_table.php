<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nip')->unique()->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'operator'])->default('operator');
            $table->enum('lokasi_dinas', [
                'Gedung Air', 'Sukarame', 'Bakauheni', 'Way Kanan', 'Relay'
            ])->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
