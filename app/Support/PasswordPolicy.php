<?php

namespace App\Support;

class PasswordPolicy
{
    /** Apakah password (teks asli) termasuk password standar yang diberikan admin? */
    public static function standar(?string $plain): bool
    {
        if ($plain === null || $plain === '') return false;
        foreach ((array) config('monoton.password_standar', []) as $s) {
            if (hash_equals(mb_strtolower($s), mb_strtolower($plain))) return true;
        }
        return false;
    }
}
