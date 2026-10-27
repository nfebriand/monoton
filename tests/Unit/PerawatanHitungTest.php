<?php

namespace Tests\Unit;

use App\Support\PerawatanHitung as H;
use PHPUnit\Framework\TestCase;

class PerawatanHitungTest extends TestCase
{
    public function test_tanggal(): void
    {
        $r = ['basis' => 'tanggal', 'interval_hari' => 90, 'terakhir_tanggal' => '2026-07-01'];
        $this->assertSame('terlambat', H::hitung($r, null, '2026-10-26')['status']);   // jatuh tempo 29/09
        $this->assertSame('segera', H::hitung($r, null, '2026-09-25')['status']);      // 4 hari lagi (ambang 9)
        $this->assertSame('ok', H::hitung($r, null, '2026-08-01')['status']);
        $this->assertSame(-27, H::hitung($r, null, '2026-10-26')['sisa_hari']);
        $this->assertSame('belum', H::hitung(['basis' => 'tanggal', 'interval_hari' => 90], null, '2026-10-26')['status']);
    }

    public function test_hm(): void
    {
        $r = ['basis' => 'hm', 'interval_hm' => 250, 'terakhir_hm' => 1000.0];
        $this->assertSame('ok', H::hitung($r, 1100.0, '2026-10-26')['status']);        // sisa 150
        $this->assertSame('segera', H::hitung($r, 1230.0, '2026-10-26')['status']);    // sisa 20 (ambang 25)
        $this->assertSame('terlambat', H::hitung($r, 1260.5, '2026-10-26')['status']);
        $this->assertSame(-10.5, H::hitung($r, 1260.5, '2026-10-26')['sisa_hm']);
        $this->assertSame('belum', H::hitung($r, null, '2026-10-26')['status']);       // HM unit tak diketahui
    }

    public function test_keduanya_mengambil_yang_terburuk(): void
    {
        $r = ['basis' => 'keduanya', 'interval_hari' => 180, 'interval_hm' => 250, 'terakhir_tanggal' => '2026-10-01', 'terakhir_hm' => 1000.0];
        $this->assertSame('ok', H::hitung($r, 1050.0, '2026-10-26')['status']);
        $this->assertSame('terlambat', H::hitung($r, 1300.0, '2026-10-26')['status']); // HM lewat walau tanggal aman
        $this->assertSame('terlambat', H::hitung($r, 1050.0, '2027-05-01')['status']); // tanggal lewat walau HM aman
    }

    public function test_tanpa_interval(): void
    {
        $this->assertSame('belum', H::hitung(['basis' => 'tanggal'], null, '2026-10-26')['status']);
    }
}
