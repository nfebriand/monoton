<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('perawatan_rutin_log') && !Schema::hasColumn('perawatan_rutin_log', 'grounding_ukur_id')) {
            Schema::table('perawatan_rutin_log', function (Blueprint $t) {
                $t->unsignedBigInteger('grounding_ukur_id')->nullable()->index()->after('eviden_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('perawatan_rutin_log', 'grounding_ukur_id')) {
            Schema::table('perawatan_rutin_log', function (Blueprint $t) { $t->dropColumn('grounding_ukur_id'); });
        }
    }
};
