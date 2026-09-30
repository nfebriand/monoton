<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aset_kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->integer('interval_maintenance_hari')->nullable();
            $table->timestamps();
        });

        Schema::create('asets', function (Blueprint $table) {
            $table->id();
            $table->string('kode_aset',20)->unique();
            $table->string('nama',150);
            $table->foreignId('aset_kategori_id')->nullable()->constrained('aset_kategoris')->nullOnDelete();
            $table->string('lokasi',100)->nullable();
            $table->string('merk',100)->nullable();
            $table->string('tipe_model',100)->nullable();
            $table->string('no_seri',100)->nullable();
            $table->date('tanggal_perolehan')->nullable();
            $table->decimal('harga_perolehan',14,2)->nullable();
            $table->enum('kondisi',['baik','rusak_ringan','rusak_berat','hilang'])->default('baik');
            $table->enum('status',['aktif','nonaktif','dihapus'])->default('aktif');
            $table->text('keterangan')->nullable();
            $table->integer('interval_maintenance_hari')->nullable();
            $table->date('maintenance_terakhir')->nullable();
            $table->timestamps();
        });

        Schema::create('aset_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('asets')->cascadeOnDelete();
            $table->string('path');
            $table->string('keterangan')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('asets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->date('tanggal');
            $table->enum('jenis',['preventif','korektif','inspeksi'])->default('preventif');
            $table->text('uraian_pekerjaan');
            $table->enum('hasil',['selesai','sebagian','tertunda'])->default('selesai');
            $table->decimal('biaya',12,2)->nullable();
            $table->json('sparepart_terpakai')->nullable();
            $table->date('rencana_maintenance_berikutnya')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->index(['aset_id','tanggal']);
        });

        Schema::create('maintenance_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_log_id')->constrained('maintenance_logs')->cascadeOnDelete();
            $table->string('path');
            $table->enum('tipe',['sebelum','sesudah','lainnya'])->default('lainnya');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_fotos');
        Schema::dropIfExists('maintenance_logs');
        Schema::dropIfExists('aset_fotos');
        Schema::dropIfExists('asets');
        Schema::dropIfExists('aset_kategoris');
    }
};
