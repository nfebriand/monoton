<?php

namespace App\Support;

/**
 * Perhitungan status perawatan rutin (murni PHP, tanpa database) agar mudah diuji.
 *
 * Input $r: basis (tanggal|hm|keduanya), interval_hari, interval_hm, terakhir_tanggal (Y-m-d|null), terakhir_hm (float|null)
 * Status: terlambat > segera > belum (belum ada acuan) > ok
 */
class PerawatanHitung
{
    public const RANK = ['ok' => 1, 'belum' => 2, 'segera' => 3, 'terlambat' => 4];

    public static function ambangHari(int $interval): int
    {
        return (int) min(14, max(3, round($interval * 0.1)));
    }

    public static function ambangHm(float $interval): float
    {
        return max(5.0, round($interval * 0.1, 1));
    }

    /** @return array{status:string,sisa_hari:?int,sisa_hm:?float,jatuh_tempo:?string,jatuh_tempo_hm:?float,ringkas:string} */
    public static function hitung(array $r, ?float $hmSekarang, ?string $hariIni = null): array
    {
        $basis = $r['basis'] ?? 'tanggal';
        $hariIni = $hariIni ?: date('Y-m-d');
        $hasil = ['status' => 'ok', 'sisa_hari' => null, 'sisa_hm' => null, 'jatuh_tempo' => null, 'jatuh_tempo_hm' => null, 'ringkas' => ''];
        $bagian = [];
        $status = [];

        if (in_array($basis, ['tanggal', 'keduanya'], true) && (int) ($r['interval_hari'] ?? 0) > 0) {
            $iv = (int) $r['interval_hari'];
            if (empty($r['terakhir_tanggal'])) {
                $status[] = 'belum';
                $bagian[] = 'Belum ada acuan tanggal';
            } else {
                $jt = (new \DateTime($r['terakhir_tanggal']))->modify("+{$iv} days");
                $sisa = (int) (new \DateTime($hariIni))->diff($jt)->format('%r%a');
                $hasil['jatuh_tempo'] = $jt->format('Y-m-d');
                $hasil['sisa_hari'] = $sisa;
                if ($sisa < 0) { $status[] = 'terlambat'; $bagian[] = 'Terlambat '.abs($sisa).' hari (jatuh tempo '.$jt->format('d/m/Y').')'; }
                elseif ($sisa <= self::ambangHari($iv)) { $status[] = 'segera'; $bagian[] = ($sisa === 0 ? 'Jatuh tempo hari ini' : "$sisa hari lagi")." (".$jt->format('d/m/Y').')'; }
                else { $status[] = 'ok'; $bagian[] = "$sisa hari lagi (".$jt->format('d/m/Y').')'; }
            }
        }

        if (in_array($basis, ['hm', 'keduanya'], true) && (float) ($r['interval_hm'] ?? 0) > 0) {
            $iv = (float) $r['interval_hm'];
            if (!isset($r['terakhir_hm']) || $r['terakhir_hm'] === '' || $r['terakhir_hm'] === null) {
                $status[] = 'belum'; $bagian[] = 'Belum ada acuan HM';
            } elseif ($hmSekarang === null) {
                $status[] = 'belum'; $bagian[] = 'HM unit belum tercatat di log genset';
            } else {
                $jt = (float) $r['terakhir_hm'] + $iv;
                $sisa = round($jt - $hmSekarang, 1);
                $hasil['jatuh_tempo_hm'] = $jt;
                $hasil['sisa_hm'] = $sisa;
                if ($sisa < 0) { $status[] = 'terlambat'; $bagian[] = 'Terlambat '.abs($sisa).' jam HM (jatuh tempo HM '.$jt.')'; }
                elseif ($sisa <= self::ambangHm($iv)) { $status[] = 'segera'; $bagian[] = "$sisa jam HM lagi (HM $jt)"; }
                else { $status[] = 'ok'; $bagian[] = "$sisa jam HM lagi (HM $jt)"; }
            }
        }

        if (!$status) { $hasil['status'] = 'belum'; $hasil['ringkas'] = 'Interval belum diatur'; return $hasil; }
        usort($status, fn($a, $b) => self::RANK[$b] <=> self::RANK[$a]);
        $hasil['status'] = $status[0];
        $hasil['ringkas'] = implode(' / ', $bagian);
        return $hasil;
    }

    public const LABEL = ['terlambat' => 'Terlambat', 'segera' => 'Segera', 'belum' => 'Belum ada acuan', 'ok' => 'Aman'];
    public const WARNA = ['terlambat' => '#ee5a24', 'segera' => '#ff9f43', 'belum' => '#636e72', 'ok' => '#10ac84'];
}
