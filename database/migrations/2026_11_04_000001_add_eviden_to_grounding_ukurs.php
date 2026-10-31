<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grounding_ukurs') && !Schema::hasColumn('grounding_ukurs', 'eviden_id')) {
            Schema::table('grounding_ukurs', function (Blueprint $t) {
                $t->unsignedBigInteger('eviden_id')->nullable()->index()->after('keterangan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('grounding_ukurs', 'eviden_id')) {
            Schema::table('grounding_ukurs', function (Blueprint $t) { $t->dropColumn('eviden_id'); });
        }
    }
};
