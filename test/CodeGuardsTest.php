<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * "Penjaga regresi": pemeriksaan statis pada source code untuk bug yang pernah
 * benar-benar terjadi di proyek ini. Tidak butuh database / Laravel.
 */
class CodeGuardsTest extends TestCase
{
    private function root(): string { return dirname(__DIR__, 2); }

    private function files(string $dir, string $suffix): array
    {
        $out = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root().'/'.$dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), $suffix)) $out[] = $f->getPathname();
        }
        sort($out);
        return $out;
    }

    /** Layar putih saat unduh PDF: output/BOM sebelum <?php atau setelah ?> merusak stream. */
    public function test_file_php_tidak_punya_bom_atau_output_liar(): void
    {
        $bermasalah = [];
        foreach (array_merge($this->files('app', '.php'), $this->files('routes', '.php'), $this->files('config', '.php')) as $f) {
            $c = file_get_contents($f);
            if (str_starts_with($c, "\xEF\xBB\xBF")) $bermasalah[] = "BOM: $f";
            elseif (!str_starts_with($c, '<?php')) $bermasalah[] = "Tidak diawali <?php: $f";
            if (preg_match('/\?>\s+\z/', $c)) $bermasalah[] = "Whitespace setelah ?>: $f";
        }
        $this->assertSame([], $bermasalah, implode("\n", $bermasalah));
    }

    /** Jalur lama public/uploads sudah tidak ada; view tidak boleh membangun URL ke sana. */
    public function test_view_tidak_memakai_asset_uploads(): void
    {
        $bermasalah = [];
        foreach ($this->files('resources/views', '.blade.php') as $f) {
            if (preg_match("/asset\(\s*['\"]uploads\//", file_get_contents($f))) $bermasalah[] = $f;
        }
        $this->assertSame([], $bermasalah, "Pakai ImageHelper::url() / asset('storage/..'):\n".implode("\n", $bermasalah));
    }

    /** Bug 500 batch 15: accessor yang memanggil ImageHelper::url() (bisa null) tidak boleh bertipe `: string`. */
    public function test_accessor_url_foto_boleh_null(): void
    {
        $bermasalah = [];
        foreach (glob($this->root().'/app/Models/*Foto.php') as $f) {
            $c = file_get_contents($f);
            if (str_contains($c, 'ImageHelper::url') && preg_match('/function get(Url|ThumbUrl)Attribute\(\)\s*:\s*string\b/', $c)) {
                $bermasalah[] = basename($f);
            }
        }
        $this->assertSame([], $bermasalah, 'Ganti ke ?string: '.implode(', ', $bermasalah));
    }

    /** Regresi batch 22: thumb_path hilang saat PemancarFoto::create tertimpa patch lain. */
    public function test_pemancar_controller_menyimpan_thumb_path_di_setiap_create_foto(): void
    {
        $c = file_get_contents($this->root().'/app/Http/Controllers/PemancarController.php');
        $jumlahCreate = preg_match_all('/PemancarFoto::create\(\[(.*?)\]\)/s', $c, $m);
        $this->assertGreaterThanOrEqual(2, $jumlahCreate);
        foreach ($m[1] as $i => $isi) {
            $this->assertStringContainsString("'thumb_path'", $isi, 'PemancarFoto::create #'.($i + 1).' tanpa thumb_path');
        }
    }

    /** Wildcard 2-segmen (/x/{id}) tidak boleh mendahului route statis (/x/create, /x/export, ...). */
    public function test_route_statis_didaftarkan_sebelum_wildcard(): void
    {
        $lines = file($this->root().'/routes/web.php');
        $static = []; $wild = [];
        foreach ($lines as $n => $line) {
            if (!preg_match("/Route::get\(\s*'(\/[^']*)'/", $line, $m)) continue;
            $path = $m[1];
            if (preg_match('#^/([^/{}]+)/\{[^/]+\}$#', $path, $w)) {
                $wild[$w[1]] = $wild[$w[1]] ?? $n;                 // wildcard pertama per prefix
            } elseif (preg_match('#^/([^/{}]+)/([^/{}]+)$#', $path, $s)) {
                $static[] = [$s[1], $path, $n];
            }
        }
        $salah = [];
        foreach ($static as [$prefix, $path, $n]) {
            if (isset($wild[$prefix]) && $wild[$prefix] < $n) {
                $salah[] = "$path (baris ".($n + 1).") didaftarkan SETELAH wildcard /$prefix/{..} (baris ".($wild[$prefix] + 1).')';
            }
        }
        $this->assertSame([], $salah, implode("\n", $salah));
    }

    /** Rata-rata/rekap tidak boleh menyertakan baris OFF (batch 13). */
    public function test_laporan_dan_statistik_mengecualikan_status_off(): void
    {
        foreach (['LaporanController', 'PemancarController'] as $ctl) {
            $c = file_get_contents($this->root()."/app/Http/Controllers/$ctl.php");
            $this->assertTrue(
                str_contains($c, "'off'") || str_contains($c, 'onOnly('),
                "$ctl tidak lagi memfilter status ON/OFF"
            );
        }
    }

    /** Foto upload form: input foto tidak boleh lagi memaksa kamera pada tombol galeri (batch 4). */
    public function test_tombol_galeri_tanpa_capture(): void
    {
        $c = file_get_contents($this->root().'/resources/views/components/foto-upload.blade.php');
        preg_match_all('/<input[^>]*foto-input-gallery[^>]*>/s', $c, $m);
        $this->assertNotEmpty($m[0]);
        foreach ($m[0] as $tag) $this->assertStringNotContainsString('capture=', $tag);
    }
}
