<?php
namespace App\Support;

use ZipArchive;

/**
 * Penulis .xlsx minimalis tanpa dependensi (hanya butuh ext-zip).
 * Fitur: banyak sheet, style (font/fill/border/align), merge, lebar kolom,
 * tinggi baris, setup halaman A4. Cukup untuk laporan/jadwal tabel.
 */
class SimpleXlsx
{
    private array $sheets = [];
    private array $fonts   = [['b'=>false,'sz'=>11,'color'=>'000000','i'=>false]];
    private array $fills   = [];          // fill selain 2 bawaan
    private array $xfs     = [];          // style terdaftar
    private array $xfIndex = [];

    public function __construct()
    {
        // xf 0 = default
        $this->xfs[]   = ['font'=>0,'fill'=>0,'border'=>0,'h'=>null,'v'=>null,'wrap'=>false];
        $this->xfIndex[json_encode($this->xfs[0])] = 0;
    }

    /** Daftarkan style, kembalikan index. Opsi: b,i,sz,color,fill,h,v,wrap,border */
    public function style(array $o): int
    {
        $font = ['b'=>!empty($o['b']),'sz'=>$o['sz'] ?? 11,'color'=>strtoupper($o['color'] ?? '000000'),'i'=>!empty($o['i'])];
        $fi = array_search($font, $this->fonts, true);
        if ($fi === false) { $this->fonts[] = $font; $fi = count($this->fonts) - 1; }

        $fillId = 0;
        if (!empty($o['fill'])) {
            $c = strtoupper($o['fill']);
            $k = array_search($c, $this->fills, true);
            if ($k === false) { $this->fills[] = $c; $k = count($this->fills) - 1; }
            $fillId = $k + 2; // 0,1 = bawaan
        }
        $xf = ['font'=>$fi,'fill'=>$fillId,'border'=>!empty($o['border']) ? 1 : 0,
               'h'=>$o['h'] ?? null,'v'=>$o['v'] ?? null,'wrap'=>!empty($o['wrap'])];
        $key = json_encode($xf);
        if (!isset($this->xfIndex[$key])) {
            $this->xfs[] = $xf;
            $this->xfIndex[$key] = count($this->xfs) - 1;
        }
        return $this->xfIndex[$key];
    }

    public function addSheet(string $name): SimpleXlsxSheet
    {
        $name = trim(preg_replace('/[\[\]\:\*\?\/\\\\]/', ' ', $name)) ?: 'Sheet';
        $name = mb_substr($name, 0, 31);
        $base = $name; $i = 2;
        $dipakai = fn(string $n) => (bool) array_filter($this->sheets, fn($s) => mb_strtolower($s->name) === mb_strtolower($n));
        while ($dipakai($name)) {
            $name = mb_substr($base, 0, 28).' '.$i++;
        }
        $s = new SimpleXlsxSheet($name);
        $this->sheets[] = $s;
        return $s;
    }

    public static function colName(int $n): string  // 1 => A
    {
        $s = '';
        while ($n > 0) { $m = ($n - 1) % 26; $s = chr(65 + $m).$s; $n = intdiv($n - 1, 26); }
        return $s;
    }

    public function save(string $path): void
    {
        if (!$this->sheets) $this->addSheet('Sheet1');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Tidak dapat membuat file xlsx.');
        }
        $n = count($this->sheets);

        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $n; $i++) $ct .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $zip->addFromString('[Content_Types].xml', $ct.'</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');

        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($this->sheets as $i => $s) {
            $id = $i + 1;
            $wb   .= '<sheet name="'.self::esc($s->name).'" sheetId="'.$id.'" r:id="rId'.$id.'"/>';
            $rels .= '<Relationship Id="rId'.$id.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$id.'.xml"/>';
            $zip->addFromString('xl/worksheets/sheet'.$id.'.xml', $s->toXml());
        }
        $rels .= '<Relationship Id="rId'.($n + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $zip->addFromString('xl/workbook.xml', $wb.'</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rels);
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->close();
    }

    private function stylesXml(): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $x .= '<fonts count="'.count($this->fonts).'">';
        foreach ($this->fonts as $f) {
            $x .= '<font>'.($f['b'] ? '<b/>' : '').($f['i'] ? '<i/>' : '').'<sz val="'.$f['sz'].'"/><color rgb="FF'.$f['color'].'"/><name val="Calibri"/></font>';
        }
        $x .= '</fonts><fills count="'.(count($this->fills) + 2).'"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>';
        foreach ($this->fills as $c) $x .= '<fill><patternFill patternType="solid"><fgColor rgb="FF'.$c.'"/><bgColor indexed="64"/></patternFill></fill>';
        $x .= '</fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FF000000"/></left><right style="thin"><color rgb="FF000000"/></right><top style="thin"><color rgb="FF000000"/></top><bottom style="thin"><color rgb="FF000000"/></bottom><diagonal/></border></borders>';
        $x .= '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="'.count($this->xfs).'">';
        foreach ($this->xfs as $xf) {
            $al = '';
            if ($xf['h'] || $xf['v'] || $xf['wrap']) {
                $al = '<alignment'.($xf['h'] ? ' horizontal="'.$xf['h'].'"' : '').($xf['v'] ? ' vertical="'.$xf['v'].'"' : '').($xf['wrap'] ? ' wrapText="1"' : '').'/>';
            }
            $x .= '<xf numFmtId="0" fontId="'.$xf['font'].'" fillId="'.$xf['fill'].'" borderId="'.$xf['border'].'" xfId="0" applyFont="1" applyFill="1" applyBorder="1"'.($al ? ' applyAlignment="1">'.$al.'</xf>' : '/>');
        }
        return $x.'</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    public static function esc(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

class SimpleXlsxSheet
{
    public string $name;
    private array $cells = [];   // [row][col] => [value, style]
    private array $merges = [];
    private array $colW = [];
    private array $rowH = [];

    public function __construct(string $name) { $this->name = $name; }

    public function set(int $row, int $col, $value, int $style = 0): self
    {
        $this->cells[$row][$col] = [$value, $style];
        return $this;
    }

    /** Isi style ke seluruh area (untuk border sel yang di-merge) */
    public function fillStyle(int $r1, int $c1, int $r2, int $c2, int $style): self
    {
        for ($r = $r1; $r <= $r2; $r++) for ($c = $c1; $c <= $c2; $c++) {
            if (!isset($this->cells[$r][$c])) $this->cells[$r][$c] = [null, $style];
        }
        return $this;
    }

    public function merge(int $r1, int $c1, int $r2, int $c2): self
    {
        $this->merges[] = SimpleXlsx::colName($c1).$r1.':'.SimpleXlsx::colName($c2).$r2;
        return $this;
    }

    public function colWidth(int $col, float $w): self { $this->colW[$col] = $w; return $this; }
    public function rowHeight(int $row, float $h): self { $this->rowH[$row] = $h; return $this; }

    public function toXml(): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><sheetViews><sheetView workbookViewId="0" showGridLines="0"/></sheetViews><sheetFormatPr defaultRowHeight="15"/>';
        if ($this->colW) {
            ksort($this->colW);
            $x .= '<cols>';
            foreach ($this->colW as $c => $w) $x .= '<col min="'.$c.'" max="'.$c.'" width="'.$w.'" customWidth="1"/>';
            $x .= '</cols>';
        }
        $x .= '<sheetData>';
        $rows = array_unique(array_merge(array_keys($this->cells), array_keys($this->rowH)));
        sort($rows);
        foreach ($rows as $r) {
            $x .= '<row r="'.$r.'"'.(isset($this->rowH[$r]) ? ' ht="'.$this->rowH[$r].'" customHeight="1"' : '').'>';
            $cols = $this->cells[$r] ?? [];
            ksort($cols);
            foreach ($cols as $c => [$v, $s]) {
                $ref = SimpleXlsx::colName($c).$r;
                $sa = $s ? ' s="'.$s.'"' : '';
                if ($v === null || $v === '') $x .= '<c r="'.$ref.'"'.$sa.'/>';
                elseif (is_int($v) || is_float($v)) $x .= '<c r="'.$ref.'"'.$sa.'><v>'.$v.'</v></c>';
                else $x .= '<c r="'.$ref.'"'.$sa.' t="inlineStr"><is><t xml:space="preserve">'.SimpleXlsx::esc((string)$v).'</t></is></c>';
            }
            $x .= '</row>';
        }
        $x .= '</sheetData>';
        if ($this->merges) {
            $x .= '<mergeCells count="'.count($this->merges).'">';
            foreach ($this->merges as $m) $x .= '<mergeCell ref="'.$m.'"/>';
            $x .= '</mergeCells>';
        }
        return $x.'<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/><pageSetup paperSize="9" orientation="portrait" fitToWidth="1" fitToHeight="0"/></worksheet>';
    }
}
