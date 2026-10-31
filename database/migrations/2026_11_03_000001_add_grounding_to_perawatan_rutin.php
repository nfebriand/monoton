<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('perawatan_rutin') && !Schema::hasColumn('perawatan_rutin', 'grounding_titik_id')) {
            Schema::table('perawatan_rutin', function (Blueprint $t) {
                $t->unsignedBigInteger('grounding_titik_id')->nullable()->index()->after('genset_unit_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('perawatan_rutin', 'grounding_titik_id')) {
            Schema::table('perawatan_rutin', function (Blueprint $t) { $t->dropColumn('grounding_titik_id'); });
        }
    }
};
