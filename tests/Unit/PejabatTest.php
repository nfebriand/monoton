<?php

namespace Tests\Unit;

use App\Models\AppSetting;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/** Aturan tanggal acuan penandatangan & pemetaan key pejabat (tanpa database). */
class PejabatTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_tanggal_acuan_memakai_akhir_periode_bila_sudah_lewat(): void
    {
        Carbon::setTestNow('2026-10-28 10:00:00');
        $this->assertSame('2026-09-30', AppSetting::tanggalAcuan('2026-09-30'));
    }

    public function test_tanggal_acuan_dibatasi_hari_ini_untuk_periode_berjalan(): void
    {
        Carbon::setTestNow('2026-10-28 10:00:00');
        $this->assertSame('2026-10-28', AppSetting::tanggalAcuan('2026-10-31'));
    }

    public function test_tanggal_acuan_kosong_atau_tidak_valid_berarti_pejabat_saat_ini(): void
    {
        $this->assertNull(AppSetting::tanggalAcuan(null));
        $this->assertNull(AppSetting::tanggalAcuan(''));
        $this->assertNull(AppSetting::tanggalAcuan('bukan-tanggal'));
    }

    public function test_key_pejabat_kompatibel_dengan_data_lama(): void
    {
        $this->assertSame('kepala_stasiun', AppSetting::kunciPejabat('kepala')['nama']);
        $this->assertSame('kabid_nama', AppSetting::kunciPejabat('kabid')['nama']);
        $this->assertSame('koordinator_studio_wk_ttd', AppSetting::kunciPejabat('studio_wk')['ttd']);
        $this->assertSame('pejabat_transmisi_mulai', AppSetting::kunciPejabat('transmisi')['mulai']);
    }

    public function test_semua_kode_punya_jabatan_default(): void
    {
        foreach (AppSetting::KODE_PEJABAT as $kode) {
            $this->assertArrayHasKey($kode, AppSetting::JABATAN_DEFAULT);
        }
    }
}
