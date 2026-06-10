<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('jadwal_shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('jadwal_shifts', 'skema')) {
                $table->string('skema')->default('default')->after('shift');
            }
        });
    }
    public function down(): void {
        Schema::table('jadwal_shifts', function (Blueprint $table) {
            $table->dropColumn('skema');
        });
    }
};
