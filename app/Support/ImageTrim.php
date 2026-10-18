<?php
namespace App\Support;

/**
 * Potong margin kosong (transparan / hampir putih) di sekeliling gambar tanda tangan.
 * Hasil selalu PNG (mendukung transparansi). Null bila gagal -> pakai gambar asli.
 */
class ImageTrim
{
    public static function trim(string $bytes): ?array
    {
        if (!function_exists('imagecreatefromstring')) return null;
        $im = @imagecreatefromstring($bytes);
        if (!$im) return null;
        $w = imagesx($im); $h = imagesy($im);
        if ($w < 2 || $h < 2) { imagedestroy($im); return null; }

        // Batasi ukuran kerja (hemat memori/waktu)
        if ($w > 1200) {
            $nh = max(1, (int) round($h * 1200 / $w));
            $rs = imagecreatetruecolor(1200, $nh);
            imagealphablending($rs, false); imagesavealpha($rs, true);
            imagefill($rs, 0, 0, imagecolorallocatealpha($rs, 255, 255, 255, 127));
            imagecopyresampled($rs, $im, 0, 0, 0, 0, 1200, $nh, $w, $h);
            imagedestroy($im); $im = $rs; $w = 1200; $h = $nh;
        }

        $minX = $w; $minY = $h; $maxX = -1; $maxY = -1;
        $true = imageistruecolor($im);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($im, $x, $y);
                if ($true) { $a = ($c >> 24) & 0x7F; $r = ($c >> 16) & 0xFF; $g = ($c >> 8) & 0xFF; $b = $c & 0xFF; }
                else { $p = imagecolorsforindex($im, $c); $a = $p['alpha']; $r = $p['red']; $g = $p['green']; $b = $p['blue']; }
                if ($a < 100 && ($r < 235 || $g < 235 || $b < 235)) {
                    if ($x < $minX) $minX = $x; if ($x > $maxX) $maxX = $x;
                    if ($y < $minY) $minY = $y; if ($y > $maxY) $maxY = $y;
                }
            }
        }
        if ($maxX < 0) { imagedestroy($im); return null; } // kosong/putih semua

        $m  = 4;
        $x0 = max(0, $minX - $m); $y0 = max(0, $minY - $m);
        $x1 = min($w - 1, $maxX + $m); $y1 = min($h - 1, $maxY + $m);
        $cw = $x1 - $x0 + 1; $ch = $y1 - $y0 + 1;

        $out = imagecreatetruecolor($cw, $ch);
        imagealphablending($out, false); imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 255, 255, 255, 127));
        imagecopy($out, $im, 0, 0, $x0, $y0, $cw, $ch);
        ob_start(); imagepng($out); $png = (string) ob_get_clean();
        imagedestroy($im); imagedestroy($out);
        return $png !== '' ? ['bytes' => $png, 'ext' => 'png'] : null;
    }
}
