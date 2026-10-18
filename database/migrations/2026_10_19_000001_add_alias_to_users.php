<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'alias')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('alias', 30)->nullable()->after('name')
                    ->comment('Nama panggilan singkat; dipakai di kolom jadwal shift (export Excel).');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'alias')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('alias');
            });
        }
    }
};
