<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Jika kolom role masih enum('admin','operator'), ubah jadi string
        // agar bisa menyimpan nilai baru 'admin_divisi'.
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(20) NOT NULL DEFAULT 'operator'");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('operator')->change();
            });
        }
    }

    public function down(): void
    {
        // Tidak perlu rollback spesifik
    }
};
