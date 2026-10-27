<?php

namespace Tests\Unit;

use App\Support\SimpleXlsx;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/** Tanpa Laravel & tanpa database: murni uji penulis .xlsx. */
class SimpleXlsxTest extends TestCase
{
    // PNG 1x1 transparan
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private function buka(string $path): ZipArchive
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true, 'xlsx harus berupa zip yang valid');
        return $zip;
    }

    public function test_workbook_valid_dengan_banyak_sheet_dan_karakter_khusus(): void
    {
        $x = new SimpleXlsx();
        $st = $x->style(['b' => true, 'fill' => 'FFD83D', 'border' => true, 'h' => 'center']);
        $s1 = $x->addSheet('Gedung Air');
        $s1->set(1, 1, 'Joni & "Nanda" <tes>', $st)->merge(1, 1, 1, 3);
        $s1->set(2, 1, 42);
        $x->addSheet('Gedung Air'); // nama kembar harus dibuat unik
        $x->addSheet('Nama/Terlarang:*?[]');

        $file = tempnam(sys_get_temp_dir(), 'xl').'.xlsx';
        $x->save($file);
        $zip = $this->buka($file);

        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet1.xml'));
        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet3.xml'));

        $sheet1 = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertStringContainsString('Joni &amp; &quot;Nanda&quot; &lt;tes&gt;', $sheet1);
        $this->assertStringContainsString('<mergeCell ref="A1:C1"/>', $sheet1);
        $this->assertStringContainsString('<v>42</v>', $sheet1);

        $wb = $zip->getFromName('xl/workbook.xml');
        $this->assertSame(3, substr_count($wb, '<sheet '));
        $this->assertStringNotContainsString('Terlarang:', $wb);
        // nama sheet kembar tidak boleh sama
        preg_match_all('/<sheet name="([^"]+)"/', $wb, $m);
        $this->assertSame(count($m[1]), count(array_unique(array_map('mb_strtolower', $m[1]))));
        $zip->close();
        @unlink($file);
    }

    public function test_gambar_tertanam_di_drawing_dan_content_types(): void
    {
        $x = new SimpleXlsx();
        $s = $x->addSheet('Ttd');
        $s->set(1, 1, 'x');
        $s->addImage(base64_decode(self::PNG), 'png', 2, 1, 10, 4, 120, 60);

        $file = tempnam(sys_get_temp_dir(), 'xl').'.xlsx';
        $x->save($file);
        $zip = $this->buka($file);

        $this->assertNotFalse($zip->locateName('xl/media/image1.png'));
        $this->assertNotFalse($zip->locateName('xl/drawings/drawing1.xml'));
        $this->assertNotFalse($zip->locateName('xl/worksheets/_rels/sheet1.xml.rels'));
        $this->assertStringContainsString('<drawing r:id="rId1"/>', $zip->getFromName('xl/worksheets/sheet1.xml'));
        $this->assertStringContainsString('/xl/drawings/drawing1.xml', $zip->getFromName('[Content_Types].xml'));
        $this->assertStringContainsString('<xdr:row>2</xdr:row>', $zip->getFromName('xl/drawings/drawing1.xml'));
        $zip->close();
        @unlink($file);
    }

    public function test_nama_kolom(): void
    {
        $this->assertSame('A', SimpleXlsx::colName(1));
        $this->assertSame('Z', SimpleXlsx::colName(26));
        $this->assertSame('AA', SimpleXlsx::colName(27));
    }
}
