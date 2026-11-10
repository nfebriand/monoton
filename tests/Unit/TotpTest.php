<?php

namespace Tests\Unit;

use App\Support\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    // Vektor uji RFC 6238 (SHA-1, secret ASCII "12345678901234567890")
    private const SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public function test_vektor_rfc6238(): void
    {
        $this->assertSame('287082', Totp::codeAt(self::SECRET, intdiv(59, 30)));
        $this->assertSame('081804', Totp::codeAt(self::SECRET, intdiv(1111111109, 30)));
        $this->assertSame('050471', Totp::codeAt(self::SECRET, intdiv(1111111111, 30)));
    }

    public function test_base32_bolak_balik(): void
    {
        $raw = random_bytes(20);
        $this->assertSame($raw, Totp::base32Decode(Totp::base32Encode($raw)));
    }

    public function test_verify_dan_replay(): void
    {
        $step = Totp::verify(self::SECRET, '287082', null, 1, 59);
        $this->assertSame(1, $step);
        $this->assertNull(Totp::verify(self::SECRET, '287082', 1, 1, 59), 'kode yang sama tidak boleh dipakai ulang');
        $this->assertNull(Totp::verify(self::SECRET, '000000', null, 1, 59));
        $this->assertNull(Totp::verify(self::SECRET, 'abc', null, 1, 59));
    }
}
