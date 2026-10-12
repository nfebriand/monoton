<?php
namespace App\Support;

use Carbon\Carbon;

/**
 * Susun workbook jadwal shift bulanan (format mengikuti jadwal cetak RRI):
 * baris = tanggal, kolom = shift (urut jam mulai), kolom kanan = daftar operator berwarna.
 */
class JadwalExcel
{
    private const HARI = [0=>'MINGGU',1=>'SENIN',2=>'SELASA',3=>'RABU',4=>'KAMIS',5=>'JUM\'AT',6=>'SABTU'];
    private const BULAN = [1=>'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];

    /** [warna isi, warna teks] */
    private const PALETTE = [
        ['2F7FD0','FFFFFF'], ['FFD83D','000000'], ['3FAE5A','FFFFFF'], ['E5483E','FFFFFF'],
        ['8E5AC8','FFFFFF'], ['F39C12','000000'], ['1ABC9C','000000'], ['E91E8C','FFFFFF'],
        ['8D6E63','FFFFFF'], ['95A5A6','000000'],
    ];

    public const CATATAN = [
        'OD hadir 30 menit sebelum jam dinas.',
        'Setiap pertukaran dinas harus seizin atasan langsung.',
        'Sebelum ada pengganti dilarang meninggalkan tugas.',
        'Setiap kali dinas harus membuat laporan.',
    ];

    /**
     * @param array $d [
     *   'divisi_label'=>string, 'bulan'=>int, 'tahun'=>int,
     *   'shifts'=>[no=>['label','mulai','selesai']],
     *   'sheets'=>[ ['lokasi'=>string, 'jadwal'=>[ 'Y-m-d'=>[shiftNo=>[nama,...]] ]], ... ],
     *   'koordinator'=>['jabatan','nama','nip'], 'kabid'=>['jabatan','nama','nip'],
     * ]
     */
    public static function build(array $d): SimpleXlsx
    {
        $x = new SimpleXlsx();

        // urut kolom shift berdasar jam mulai
        $shifts = $d['shifts'];
        uasort($shifts, fn($a, $b) => strcmp($a['mulai'], $b['mulai']));
        $shiftNos = array_keys($shifts);
        $nShift   = count($shiftNos);
        $colOp    = 3 + $nShift;            // kolom legenda operator
        $lastCol  = $colOp;

        $st = [
            'org'    => $x->style(['b'=>true,'sz'=>13,'h'=>'left','v'=>'center']),
            'sub'    => $x->style(['sz'=>9,'h'=>'left','v'=>'center']),
            'title'  => $x->style(['b'=>true,'sz'=>12,'border'=>true,'h'=>'center','v'=>'center','wrap'=>true]),
            'th'     => $x->style(['b'=>true,'border'=>true,'h'=>'center','v'=>'center','wrap'=>true]),
            'thsm'   => $x->style(['b'=>true,'sz'=>10,'border'=>true,'h'=>'center','v'=>'center']),
            'hari'   => $x->style(['b'=>true,'border'=>true,'h'=>'left','v'=>'center']),
            'harimerah' => $x->style(['b'=>true,'color'=>'D6336C','border'=>true,'h'=>'left','v'=>'center']),
            'tgl'    => $x->style(['b'=>true,'border'=>true,'h'=>'center','v'=>'center']),
            'kosong' => $x->style(['border'=>true,'h'=>'center','v'=>'center','wrap'=>true]),
            'catjud' => $x->style(['b'=>true]),
            'cat'    => $x->style(['sz'=>10]),
            'ttdc'   => $x->style(['h'=>'center']),
            'ttdnm'  => $x->style(['b'=>true,'h'=>'center']),
        ];

        $tgl1  = Carbon::create($d['tahun'], $d['bulan'], 1);
        $hariN = $tgl1->daysInMonth;
        $bulanNama = self::BULAN[$d['bulan']];
        // Tanggal surat = H-1 dari tanggal 1 bulan jadwal (= akhir bulan sebelumnya)
        $hMin1  = $tgl1->copy()->subDay();
        $tglTtd = $hMin1->day.' '.ucfirst(strtolower(self::BULAN[(int) $hMin1->month])).' '.$hMin1->year;

        foreach ($d['sheets'] as $sh) {
            $s = $x->addSheet($sh['lokasi']);
            $widths = [1 => 11, 2 => 13];
            for ($i = 0; $i < $nShift; $i++) $widths[3 + $i] = 20;
            $widths[$colOp] = 28;
            foreach ($widths as $c => $w) $s->colWidth($c, $w);

            // Kop
            // Logo instansi (opsional) di kiri kop; teks kop bergeser ke kolom 3 bila ada logo
            $kopKol = 1;
            $logo = $d['logo'] ?? null;
            if (!empty($logo['bytes']) && ($li = @getimagesizefromstring($logo['bytes'])) && $li[0] > 0 && $li[1] > 0) {
                $sc = min(170 / $li[0], 78 / $li[1], 1.0);
                $lw = max(1, (int) round($li[0] * $sc));
                $lh = max(1, (int) round($li[1] * $sc));
                $s->addImage($logo['bytes'], $logo['ext'] ?? 'png', 0, 0, 4, 3, $lw, $lh);
                $kopKol = 3;
            }
            $s->set(1, $kopKol, 'LEMBAGA PENYIARAN PUBLIK RADIO REPUBLIK INDONESIA', $st['org'])->merge(1, $kopKol, 1, $lastCol);
            $s->set(2, $kopKol, 'RRI BANDAR LAMPUNG', $st['org'])->merge(2, $kopKol, 2, $lastCol);
            $s->set(3, $kopKol, 'Jl. Gatot Subroto No. 26 Pahoman, Bandar Lampung', $st['sub'])->merge(3, $kopKol, 3, $lastCol);
            $s->rowHeight(1, 24)->rowHeight(2, 24)->rowHeight(3, 18);

            // Judul
            $judul = 'JADWAL OPERATOR '.mb_strtoupper($d['divisi_label']).' - '.mb_strtoupper($sh['lokasi'])
                   .' - TANGGAL 1 s.d '.$hariN.' '.$bulanNama.' '.$d['tahun'];
            $s->set(5, 1, $judul, $st['title'])->merge(5, 1, 5, $lastCol)->fillStyle(5, 1, 5, $lastCol, $st['title']);
            $s->rowHeight(5, 26);

            // Header tabel (2 baris)
            $s->set(6, 1, 'HARI', $st['th'])->merge(6, 1, 7, 1)->fillStyle(6, 1, 7, 1, $st['th']);
            $s->set(6, 2, 'TANGGAL', $st['th'])->merge(6, 2, 7, 2)->fillStyle(6, 2, 7, 2, $st['th']);
            foreach ($shiftNos as $i => $no) {
                $s->set(6, 3 + $i, mb_strtoupper($shifts[$no]['label']), $st['th']);
                $s->set(7, 3 + $i, str_replace(':', '.', $shifts[$no]['mulai']).'-'.str_replace(':', '.', $shifts[$no]['selesai']), $st['thsm']);
            }
            $s->set(6, $colOp, 'OPERATOR '.mb_strtoupper($d['divisi_label']), $st['th'])->merge(6, $colOp, 7, $colOp)->fillStyle(6, $colOp, 7, $colOp, $st['th']);

            // Warna operator: urut nama, konsisten
            $names = [];
            foreach ($sh['jadwal'] as $perShift) foreach ($perShift as $list) foreach ($list as $n) $names[$n] = true;
            $names = array_keys($names);
            sort($names, SORT_NATURAL | SORT_FLAG_CASE);
            $warna = [];
            foreach ($names as $i => $n) $warna[$n] = self::PALETTE[$i % count(self::PALETTE)];
            $stCache = [];
            $namaStyle = function(array $nm) use (&$stCache, $x, $warna, $st) {
                if (count($nm) !== 1) return $st['kosong'];
                $n = $nm[0];
                return $stCache[$n] ??= $x->style(['fill'=>$warna[$n][0],'color'=>$warna[$n][1],'border'=>true,'h'=>'center','v'=>'center','wrap'=>true]);
            };

            // Baris data
            $r0 = 8;
            for ($t = 1; $t <= $hariN; $t++) {
                $r = $r0 + $t - 1;
                $date = Carbon::create($d['tahun'], $d['bulan'], $t);
                $dow = $date->dayOfWeek;
                $s->set($r, 1, self::HARI[$dow], ($dow === 0 || $dow === 6) ? $st['harimerah'] : $st['hari']);
                $s->set($r, 2, $date->format('d-M-y'), $st['tgl']);
                $per = $sh['jadwal'][$date->toDateString()] ?? [];
                foreach ($shiftNos as $i => $no) {
                    $nm = $per[$no] ?? [];
                    $s->set($r, 3 + $i, $nm ? mb_strtoupper(implode("\n", $nm)) : '', $namaStyle($nm));
                }
                $s->rowHeight($r, count(array_filter($per, fn($l) => count($l) > 1)) ? 30 : 17);
            }
            $rEnd = $r0 + $hariN - 1;

            // Kolom legenda operator (sel kosong berborder di seluruh kolom)
            $legBorder = $x->style(['border'=>true,'v'=>'center']);
            for ($r = $r0; $r <= $rEnd; $r++) $s->set($r, $colOp, '', $legBorder);
            foreach ($names as $i => $n) {
                $ls = $x->style(['fill'=>$warna[$n][0],'color'=>$warna[$n][1],'border'=>true,'v'=>'center']);
                $s->set($r0 + $i, $colOp, ($i + 1).'. '.$n, $ls);
            }

            // Tempel gambar TTD di tengah blok kolom $c1..$c2, mulai baris $rowSpace (1-based, 4 baris kosong)
            $tempelTtd = function(?array $ttd, int $rowSpace, int $c1, int $c2) use ($s, $widths) {
                if (empty($ttd['bytes'])) return;
                $info = @getimagesizefromstring($ttd['bytes']);
                if (!$info || $info[0] < 1 || $info[1] < 1) return;
                $scale = min(170 / $info[0], 85 / $info[1], 1.0);
                $w = max(1, (int) round($info[0] * $scale));
                $h = max(1, (int) round($info[1] * $scale));
                $px = fn($c) => (int) floor(($widths[$c] ?? 8.43) * 7 + 5);
                $blok = 0; for ($c = $c1; $c <= $c2; $c++) $blok += $px($c);
                $x = max(0, (int) floor(($blok - $w) / 2));
                $col = $c1;
                while ($col < $c2 && $x >= $px($col)) { $x -= $px($col); $col++; }
                $s->addImage($ttd['bytes'], $ttd['ext'] ?? 'png', $rowSpace - 1, $col - 1, $x, 4, $w, $h);
            };

            // Catatan
            $r = $rEnd + 2;
            $s->set($r++, 1, 'Catatan:', $st['catjud']);
            foreach (self::CATATAN as $i => $c) {
                $s->set($r++, 1, ($i + 1).'. '.$c, $st['cat']);
            }

            // Tanda tangan: kiri = koordinator, kanan = kabid
            $r += 1;
            $kiriMulai = 1; $kiriAkhir = max(2, (int) floor($lastCol / 2));
            $kananMulai = $kiriAkhir + 1;
            $s->set($r, $kananMulai, 'Bandar Lampung, '.$tglTtd, $st['ttdc'])->merge($r, $kananMulai, $r, $lastCol);
            $r++;
            $k = $d['koordinator']; $b = $d['kabid'];
            $s->set($r, $kiriMulai, $k['jabatan'], $st['ttdc'])->merge($r, $kiriMulai, $r, $kiriAkhir);
            $s->set($r, $kananMulai, $b['jabatan'], $st['ttdc'])->merge($r, $kananMulai, $r, $lastCol);
            for ($i = 1; $i <= 4; $i++) $s->rowHeight($r + $i, 21);
            $tempelTtd($k['ttd'] ?? null, $r + 1, $kiriMulai, $kiriAkhir);
            $tempelTtd($b['ttd'] ?? null, $r + 1, $kananMulai, $lastCol);
            $r += 5; // ruang tanda tangan
            $s->set($r, $kiriMulai, $k['nama'] ?: '(................................)', $st['ttdnm'])->merge($r, $kiriMulai, $r, $kiriAkhir);
            $s->set($r, $kananMulai, $b['nama'] ?: '(................................)', $st['ttdnm'])->merge($r, $kananMulai, $r, $lastCol);
            $r++;
            $s->set($r, $kiriMulai, $k['nip'] ? 'NIP. '.$k['nip'] : '', $st['ttdc'])->merge($r, $kiriMulai, $r, $kiriAkhir);
            $s->set($r, $kananMulai, $b['nip'] ? 'NIP. '.$b['nip'] : '', $st['ttdc'])->merge($r, $kananMulai, $r, $lastCol);

            // Mengetahui: Kepala Stasiun (opsional, tampil bila ada di Pengaturan)
            if (!empty($d['kepala']) && ($d['kepala']['nama'] ?? '') !== '') {
                $kp = $d['kepala'];
                $r += 2;
                $s->set($r++, 1, 'Mengetahui', $st['ttdc'])->merge($r - 1, 1, $r - 1, $lastCol);
                $s->set($r, 1, $kp['jabatan'], $st['ttdc'])->merge($r, 1, $r, $lastCol);
                for ($i = 1; $i <= 4; $i++) $s->rowHeight($r + $i, 21);
                $tempelTtd($kp['ttd'] ?? null, $r + 1, 1, $lastCol);
                $r += 5;
                $s->set($r, 1, $kp['nama'], $st['ttdnm'])->merge($r, 1, $r, $lastCol);
                $r++;
                $s->set($r, 1, $kp['nip'] ? 'NIP. '.$kp['nip'] : '', $st['ttdc'])->merge($r, 1, $r, $lastCol);
            }
        }
        return $x;
    }
}