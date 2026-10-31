<?php

namespace Tests\Unit;

use App\Models\GroundingUkur;
use PHPUnit\Framework\TestCase;

class GroundingTest extends TestCase
{
    public function test_status_pengukuran_terhadap_batas(): void
    {
        $this->assertSame('baik', GroundingUkur::hitungStatus(3.9, 5));
        $this->assertSame('waspada', GroundingUkur::hitungStatus(4.0, 5)); // tepat 80% batas
        $this->assertSame('waspada', GroundingUkur::hitungStatus(5.0, 5)); // sama dengan batas masih diterima
        $this->assertSame('melebihi', GroundingUkur::hitungStatus(5.01, 5));
    }

    public function test_menu_dan_form_offline_terpasang(): void
    {
        $root = dirname(__DIR__, 2);
        $this->assertStringContainsString('data-offline-queue="grounding_ukur"', file_get_contents($root.'/resources/views/grounding/ukur-form.blade.php'));
        $this->assertStringContainsString('data-offline-queue="grounding_ronde"', file_get_contents($root.'/resources/views/grounding/ronde.blade.php'));
        $r = file_get_contents($root.'/routes/web.php');
        $this->assertLessThan(strpos($r, "'/grounding/wilayah/{groundingWilayah}'"), strpos($r, "'/grounding/wilayah/create'"));
        $this->assertLessThan(strpos($r, "'/grounding/titik/{groundingTitik}'"), strpos($r, "'/grounding/titik/create'"));
    }

    public function test_rute_tahap_c_terdaftar(): void
    {
        $r = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        foreach (['grounding.laporan', 'grounding.jadwal', 'grounding.titik.kartu', 'grounding.ukur.edit', 'grounding.ukur.update'] as $n) {
            $this->assertStringContainsString("'".$n."'", $r);
        }
    }
}
