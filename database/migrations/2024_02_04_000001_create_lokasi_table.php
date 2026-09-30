<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lokasis', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();              // Gedung Air, Sukarame, Studio A, dll
            $table->string('divisi')->default('transmisi'); // transmisi / studio / sarana
            $table->string('alamat')->nullable();
            $table->string('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed dari data yang sudah ada di pemancar & users
        $lokasis = collect();

        // Dari pemancar.lokasi
        if (Schema::hasTable('pemancars')) {
            DB::table('pemancars')->whereNotNull('lokasi')
                ->distinct()->pluck('lokasi')
                ->each(fn($n) => $lokasis->push(['nama'=>$n,'divisi'=>'transmisi']));
        }
        // Dari users.lokasi_dinas (yang belum ada)
        if (Schema::hasTable('users')) {
            DB::table('users')->whereNotNull('lokasi_dinas')
                ->distinct()->pluck('lokasi_dinas')
                ->each(function($n) use ($lokasis) {
                    if (!$lokasis->where('nama',$n)->count()) {
                        $lokasis->push(['nama'=>$n,'divisi'=>'transmisi']);
                    }
                });
        }

        foreach ($lokasis->unique('nama') as $l) {
            DB::table('lokasis')->insertOrIgnore([
                'nama'       => $l['nama'],
                'divisi'     => $l['divisi'],
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lokasis');
    }
};
