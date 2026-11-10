<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'must_change_password')) $t->boolean('must_change_password')->default(false);
            if (!Schema::hasColumn('users', 'password_changed_at'))  $t->timestamp('password_changed_at')->nullable();
            if (!Schema::hasColumn('users', 'two_factor_secret'))    $t->text('two_factor_secret')->nullable();
            if (!Schema::hasColumn('users', 'two_factor_confirmed_at')) $t->timestamp('two_factor_confirmed_at')->nullable();
            if (!Schema::hasColumn('users', 'two_factor_last_step')) $t->unsignedBigInteger('two_factor_last_step')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            foreach (['must_change_password','password_changed_at','two_factor_secret','two_factor_confirmed_at','two_factor_last_step'] as $c) {
                if (Schema::hasColumn('users', $c)) $t->dropColumn($c);
            }
        });
    }
};
