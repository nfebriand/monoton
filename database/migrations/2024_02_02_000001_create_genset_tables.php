<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Master data unit genset per lokasi
        Schema::create('genset_units', function (Blueprint $table) {
            $table->id();
            $table->string('nama_unit');           // e.g. "Genset 1 - Gedung Air"
            $table->string('lokasi')->nullable();  // Gedung Air / Sukarame / Bakauheni
            $table->string('merk')->nullable();
            $table->string('tipe')->nullable();
            $table->integer('kapasitas_kva')->nullable();
            $table->decimal('kapasitas_tangki_liter', 8, 2)->nullable();
            $table->integer('tahun_pembuatan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Logbook operasional genset
        Schema::create('genset_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('genset_unit_id')->constrained('genset_units')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai')->nullable();
            $table->enum('alasan', ['pln_mati','maintenance','test_rutin','lainnya'])->default('pln_mati');

            // Hour Meter (jam operasi mesin)
            $table->decimal('hm_awal', 10, 1)->nullable();
            $table->decimal('hm_akhir', 10, 1)->nullable();

            // BBM (liter)
            $table->decimal('bbm_awal', 8, 2)->nullable();
            $table->decimal('bbm_isi', 8, 2)->nullable();   // pengisian BBM saat itu
            $table->decimal('bbm_akhir', 8, 2)->nullable();

            $table->enum('kondisi', ['normal','gangguan'])->default('normal');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['tanggal','genset_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genset_logs');
        Schema::dropIfExists('genset_units');
    }
};
