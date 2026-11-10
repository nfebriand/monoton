<?php

namespace Tests\Unit;

use App\Http\Controllers\GensetController;
use PHPUnit\Framework\TestCase;

class GensetBbmTest extends TestCase
{
    public function test_cek_rentang_hanya_pengisian_bbm(): void
    {
        $unit = (object) ['kapasitas_tangki_liter' => 200];
        $this->assertNull(GensetController::cekRentang(['bbm_isi' => 150], $unit));
        $this->assertNull(GensetController::cekRentang(['bbm_isi' => ''], $unit));
        $this->assertNotNull(GensetController::cekRentang(['bbm_isi' => 250], $unit));
    }

    public function test_form_tanpa_hm_dan_bbm_awal_akhir(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['create', 'edit'] as $f) {
            $v = file_get_contents($root."/resources/views/genset/$f.blade.php");
            foreach (['name="hm_awal"', 'name="hm_akhir"', 'name="bbm_awal"', 'name="bbm_akhir"'] as $n) {
                $this->assertStringNotContainsString($n, $v);
            }
            $this->assertStringContainsString('name="bbm_isi"', $v);
        }
        $r = file_get_contents($root.'/routes/web.php');
        $this->assertLessThan(strpos($r, "'/genset/{genset}'"), strpos($r, "'/genset/rekap-bbm'"));
    }
}
