<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Antena: gain & tinggi
        if (Schema::hasTable('pemancar_sistem')) {
            Schema::table('pemancar_sistem', function (Blueprint $t) {
                if (!Schema::hasColumn('pemancar_sistem', 'antena_gain'))   $t->decimal('antena_gain', 6, 2)->nullable()->after('antena_max_power');   // dBd
                if (!Schema::hasColumn('pemancar_sistem', 'antena_tinggi')) $t->decimal('antena_tinggi', 8, 2)->nullable()->after('antena_gain');      // meter
            });
        }

        // 2) Sumber audio: lebih dari satu (utama/cadangan)
        if (!Schema::hasTable('pemancar_audio_sumber')) {
            Schema::create('pemancar_audio_sumber', function (Blueprint $t) {
                $t->id();
                $t->foreignId('pemancar_id')->constrained('pemancars')->onDelete('cascade');
                $t->unsignedTinyInteger('urutan')->default(0);
                $t->string('peran', 30)->nullable();            // Utama | Cadangan
                $t->string('sumber', 30)->nullable();           // STL | DVB | Streaming | Lainnya
                $t->string('konektor', 60)->nullable();
                $t->string('merk', 100)->nullable();
                $t->string('tipe', 100)->nullable();
                $t->decimal('stl_frekuensi', 10, 3)->nullable(); // MHz
                $t->string('catatan', 200)->nullable();
                $t->timestamps();
                $t->index('pemancar_id');
            });
        }

        // Salin data audio tunggal dari batch sebelumnya (jika sudah ada), lalu hapus kolom lamanya
        if (Schema::hasTable('pemancar_sistem') && Schema::hasColumn('pemancar_sistem', 'audio_sumber')) {
            $rows = DB::table('pemancar_sistem')->where(function ($q) {
                $q->whereNotNull('audio_sumber')->orWhereNotNull('audio_konektor')
                  ->orWhereNotNull('audio_perangkat_merk')->orWhereNotNull('audio_perangkat_tipe')
                  ->orWhereNotNull('stl_frekuensi');
            })->get();
            foreach ($rows as $r) {
                $sudah = DB::table('pemancar_audio_sumber')->where('pemancar_id', $r->pemancar_id)->exists();
                if ($sudah) continue;
                DB::table('pemancar_audio_sumber')->insert([
                    'pemancar_id' => $r->pemancar_id, 'urutan' => 0, 'peran' => 'Utama',
                    'sumber' => $r->audio_sumber, 'konektor' => $r->audio_konektor,
                    'merk' => $r->audio_perangkat_merk, 'tipe' => $r->audio_perangkat_tipe,
                    'stl_frekuensi' => $r->stl_frekuensi,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            Schema::table('pemancar_sistem', function (Blueprint $t) {
                $t->dropColumn(['audio_sumber', 'audio_konektor', 'audio_perangkat_merk', 'audio_perangkat_tipe', 'stl_frekuensi']);
            });
        }

        // 3) Dokumen / manual / skematik
        if (!Schema::hasTable('pemancar_dokumen')) {
            Schema::create('pemancar_dokumen', function (Blueprint $t) {
                $t->id();
                $t->foreignId('pemancar_id')->constrained('pemancars')->onDelete('cascade');
                $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('kategori', 30)->default('lainnya');
                $t->string('judul', 150);
                $t->string('path', 255);            // relatif terhadap storage/app (disk local, privat)
                $t->string('nama_asli', 255);
                $t->string('mime', 100)->nullable();
                $t->unsignedBigInteger('ukuran')->default(0);
                $t->timestamps();
                $t->index(['pemancar_id', 'kategori']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pemancar_dokumen');
        Schema::dropIfExists('pemancar_audio_sumber');
        if (Schema::hasTable('pemancar_sistem')) {
            Schema::table('pemancar_sistem', function (Blueprint $t) {
                foreach (['antena_gain', 'antena_tinggi'] as $c) if (Schema::hasColumn('pemancar_sistem', $c)) $t->dropColumn($c);
            });
        }
    }
};
