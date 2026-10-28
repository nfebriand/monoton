<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Menjaga sambungan antrian offline (form -> offline-forms.js -> sync manager -> service worker). */
class OfflineQueueWiringTest extends TestCase
{
    private function baca(string $rel): string
    {
        return file_get_contents(dirname(__DIR__, 2).'/'.$rel);
    }

    public function test_form_tambah_data_masuk_antrian_offline(): void
    {
        $form = [
            'resources/views/operasional/create.blade.php'       => 'operasional',
            'resources/views/studio/logbook/create.blade.php'    => 'studio_logbook',
            'resources/views/studio/maintenance/create.blade.php'=> 'studio_maintenance',
            'resources/views/maintenance/create.blade.php'       => 'maintenance_aset',
            'resources/views/genset/create.blade.php'            => 'genset',
        ];
        foreach ($form as $file => $tipe) {
            $this->assertStringContainsString('data-offline-queue="'.$tipe.'"', $this->baca($file), $file);
        }
    }

    public function test_sync_manager_memanggil_fungsi_yang_benar_ada(): void
    {
        $sync  = $this->baca('public/js/offline-sync.js');
        $forms = $this->baca('public/js/offline-forms.js');
        preg_match_all('/OfflineForms\.(\w+)\(/', $sync, $m);
        $this->assertNotEmpty($m[1]);
        foreach (array_unique($m[1]) as $fn) {
            $this->assertMatchesRegularExpression('/window\.OfflineForms\s*=\s*\{[^}]*\b'.$fn.'\b/', $forms, "OfflineForms.$fn tidak diekspor");
        }
    }

    public function test_layout_memuat_script_dan_meta_user(): void
    {
        $layout = $this->baca('resources/views/layouts/app.blade.php');
        $this->assertStringContainsString('/js/offline-forms.js', $layout);
        $this->assertStringContainsString('name="user-id"', $layout);
    }

    public function test_service_worker_menyimpan_halaman_form_dan_script(): void
    {
        $sw = $this->baca('public/sw.js');
        foreach (['/js/offline-forms.js', '/studio/logbook/create', '/studio/maintenance/create', '/operasional/create', '/maintenance/create'] as $u) {
            $this->assertStringContainsString("'$u'", $sw, $u);
        }
    }

    public function test_middleware_idempotensi_terdaftar_di_grup_web(): void
    {
        $this->assertStringContainsString('OfflineIdempotency::class', $this->baca('app/Http/Kernel.php'));
    }
}
