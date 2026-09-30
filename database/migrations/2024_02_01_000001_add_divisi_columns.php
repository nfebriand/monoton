<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tambah kolom divisi ke users — fondasi untuk Studio & Sarana Prasarana
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'divisi')) {
                $table->string('divisi')->default('transmisi')->after('lokasi_dinas');
            }
        });

        // Tambah kolom divisi ke eviden — agar eviden bisa lintas divisi
        Schema::table('evidens', function (Blueprint $table) {
            if (!Schema::hasColumn('evidens', 'divisi')) {
                $table->string('divisi')->nullable()->after('lokasi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('divisi');
        });
        Schema::table('evidens', function (Blueprint $table) {
            $table->dropColumn('divisi');
        });
    }
};
