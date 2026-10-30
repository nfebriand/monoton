<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Menjaga sambungan Catatan Dinas & keterangan foto Eviden (tanpa database). */
class CatatanDinasWiringTest extends TestCase
{
    private function baca(string $rel): string
    {
        return file_get_contents(dirname(__DIR__, 2).'/'.$rel);
    }

    public function test_form_catatan_dinas_masuk_antrian_offline_dan_sidebar(): void
    {
        $this->assertStringContainsString('data-offline-queue="catatan_dinas"', $this->baca('resources/views/catatan-dinas/create.blade.php'));
        $this->assertStringContainsString("route('catatan-dinas.index')", $this->baca('resources/views/layouts/app.blade.php'));
        $this->assertStringContainsString('/catatan-dinas/create', $this->baca('public/sw.js'));
    }

    public function test_route_statis_sebelum_wildcard(): void
    {
        $r = $this->baca('routes/web.php');
        $this->assertLessThan(strpos($r, "'/catatan-dinas/{catatanDinas}'"), strpos($r, "'/catatan-dinas/create'"));
    }

    public function test_komponen_foto_memakai_keterangan_berurutan_dan_eviden_memakainya(): void
    {
        $k = $this->baca('resources/views/components/foto-multi-ket.blade.php');
        $this->assertStringContainsString("ta.name = CAP + '[' + i + ']'", $k);
        $this->assertStringContainsString('foto_keterangan_lama[', $k);
        foreach (['eviden/create', 'eviden/edit', 'catatan-dinas/_form'] as $v) {
            $this->assertStringContainsString("components.foto-multi-ket", $this->baca("resources/views/$v.blade.php"), $v);
        }
        $c = $this->baca('app/Http/Controllers/EvidenController.php');
        $this->assertStringContainsString('foto_keterangan_lama', $c);
    }

    public function test_logbook_sarana_terpasang(): void
    {
        $this->assertStringContainsString('data-offline-queue="sarana_logbook"', $this->baca('resources/views/sarana/logbook/create.blade.php'));
        $this->assertStringContainsString("route('sarana.logbook.index')", $this->baca('resources/views/layouts/app.blade.php'));
        $r = $this->baca('routes/web.php');
        $this->assertLessThan(strpos($r, "'/sarana/logbook/{saranaLog}'"), strpos($r, "'/sarana/logbook/cetak-bulanan'"));
        $m = $this->baca('app/Models/SaranaLog.php');
        $this->assertStringContainsString("where('is_active', true)", $m); // baris genset mengikuti master unit
        $this->assertCount(7, array_filter(['alih_tugas','listrik_pln','listrik_kantor','telepon_hybrid','internet','ups','ac'], fn($k) => str_contains($m, "'$k'")));
    }
}
