<?php

namespace Tests\Unit;

use App\Models\SkemaShift;
use PHPUnit\Framework\TestCase;

class SkemaLokasiTest extends TestCase
{
    public function test_slug_lokasi(): void
    {
        $this->assertSame('gedung_air', SkemaShift::slugLokasi('Gedung Air'));
        $this->assertSame('bakauheni', SkemaShift::slugLokasi('  Bakauheni '));
        $this->assertSame('way_kanan', SkemaShift::slugLokasi('Way-Kanan'));
        $this->assertSame('', SkemaShift::slugLokasi(null));
    }
}
