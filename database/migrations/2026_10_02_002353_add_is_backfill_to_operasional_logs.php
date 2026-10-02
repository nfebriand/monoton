<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operasional_logs', function (Blueprint $table) {
            $table->boolean('is_backfill')->default(false)->after('keterangan')
                ->comment('True jika baris ini diisi lewat menu Isi Data Susulan, bukan dicatat saat shift berlangsung');
        });
    }

    public function down(): void
    {
        Schema::table('operasional_logs', function (Blueprint $table) {
            $table->dropColumn('is_backfill');
        });
    }
};
