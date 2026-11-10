<?php

namespace App\Support;

/** TOTP (RFC 6238) tanpa pustaka luar: SHA-1, 6 digit, langkah 30 detik. */
class Totp
{
    private const ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function base32Encode(string $bin): string
    {
        $bits = '';
        foreach (str_split($bin) as $c) $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        $out = '';
        foreach (str_split($bits, 5) as $chunk) $out .= self::ALPHA[bindec(str_pad($chunk, 5, '0'))];
        return $out;
    }

    public static function base32Decode(string $s): string
    {
        $s = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $s));
        $bits = '';
        foreach (str_split($s) as $c) {
            $p = strpos(self::ALPHA, $c);
            if ($p === false) continue;
            $bits .= str_pad(decbin($p), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) $out .= chr(bindec($byte));
        }
        return $out;
    }

    public static function codeAt(string $secret, int $step, int $digits = 6): string
    {
        $h = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);
        $o = ord($h[19]) & 0x0f;
        $v = ((ord($h[$o]) & 0x7f) << 24) | ((ord($h[$o + 1]) & 0xff) << 16)
           | ((ord($h[$o + 2]) & 0xff) << 8) | (ord($h[$o + 3]) & 0xff);
        return str_pad((string) ($v % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Cocokkan kode dengan jendela +-$window langkah. Return nomor langkah yang cocok, atau null.
     * Langkah <= $lastStep ditolak (kode yang sama tidak bisa dipakai dua kali).
     */
    public static function verify(string $secret, string $code, ?int $lastStep = null, int $window = 1, ?int $time = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) return null;
        $now = intdiv($time ?? time(), 30);
        for ($i = -$window; $i <= $window; $i++) {
            $step = $now + $i;
            if ($lastStep !== null && $step <= $lastStep) continue;
            if (hash_equals(self::codeAt($secret, $step), $code)) return $step;
        }
        return null;
    }

    public static function uri(string $secret, string $akun, string $issuer = 'MonOTOn'): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $akun)
            . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
    }
}
