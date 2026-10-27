<?php

namespace Tests\Feature;

use App\Support\JadwalExcel;
use Tests\TestCase;
use ZipArchive;

/** Tidak menyentuh database (data jadwal dikirim sebagai array). */
class JadwalExcelTest extends TestCase
{
    private function data(int $bulan, int $tahun, array $sheets): array
    {
        return [
            'divisi_label' => 'Transmisi', 'bulan' => $bulan, 'tahun' => $tahun,
            'shifts' => [
                1 => ['label' => 'Pagi',  'mulai' => '08:15', 'selesai' => '15:45'],
                2 => ['label' => 'Sore',  'mulai' => '16:15', 'selesai' => '23:45'],
                3 => ['label' => 'Malam', 'mulai' => '00:15', 'selesai' => '07:45'],
            ],
            'sheets' => $sheets,
            'koordinator' => ['jabatan' => 'Pengelola', 'nama' => 'A', 'nip' => '1'],
            'kabid'       => ['jabatan' => 'Ketua Tim', 'nama' => 'B', 'nip' => '2'],
            'kepala'      => ['jabatan' => 'Kepala Stasiun', 'nama' => 'C', 'nip' => '3'],
        ];
    }

    private function sheetXml(array $data, int $no = 1): string
    {
        $file = tempnam(sys_get_temp_dir(), 'jx').'.xlsx';
        JadwalExcel::build($data)->save($file);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($file) === true);
        $xml = $zip->getFromName("xl/worksheets/sheet{$no}.xml");
        $zip->close();
        @unlink($file);
        return (string) $xml;
    }

    public function test_tanggal_tanda_tangan_adalah_akhir_bulan_sebelumnya(): void
    {
        $oktober = $this->sheetXml($this->data(10, 2026, [['lokasi' => 'Gedung Air', 'jadwal' => []]]));
        $this->assertStringContainsString('Bandar Lampung, 30 September 2026', $oktober);

        $januari = $this->sheetXml($this->data(1, 2027, [['lokasi' => 'Gedung Air', 'jadwal' => []]]));
        $this->assertStringContainsString('Bandar Lampung, 31 Desember 2026', $januari);
    }

    public function test_kolom_shift_urut_jam_mulai_dan_nama_operator_tampil(): void
    {
        $xml = $this->sheetXml($this->data(10, 2026, [[
            'lokasi' => 'Gedung Air',
            'jadwal' => ['2026-10-01' => [3 => ['Sutrisno'], 1 => ['Joni'], 2 => ['Fauzan']]],
        ]]));
        $this->assertLessThan(strpos($xml, 'PAGI'), strpos($xml, 'MALAM'));
        $this->assertLessThan(strpos($xml, 'SORE'), strpos($xml, 'PAGI'));
        foreach (['SUTRISNO', 'JONI', 'FAUZAN'] as $n) $this->assertStringContainsString($n, $xml);
    }

    public function test_satu_sheet_per_lokasi(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'jx').'.xlsx';
        JadwalExcel::build($this->data(10, 2026, [
            ['lokasi' => 'Gedung Air', 'jadwal' => []],
            ['lokasi' => 'Way Kanan',  'jadwal' => []],
        ]))->save($file);
        $zip = new ZipArchive();
        $zip->open($file);
        $wb = $zip->getFromName('xl/workbook.xml');
        $zip->close();
        @unlink($file);
        $this->assertStringContainsString('Gedung Air', $wb);
        $this->assertStringContainsString('Way Kanan', $wb);
    }
}
