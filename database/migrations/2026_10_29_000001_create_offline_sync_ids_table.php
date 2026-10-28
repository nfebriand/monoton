<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('offline_sync_ids')) return;

        Schema::create('offline_sync_ids', function (Blueprint $t) {
            $t->id();
            $t->string('uuid', 64)->unique();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('uri', 190)->nullable();
            $t->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sync_ids');
    }
};
