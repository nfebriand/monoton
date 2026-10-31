<?php
namespace App\Http\Controllers\Concerns;

use App\Helpers\ImageHelper;
use Illuminate\Http\Request;

/** Menyimpan foto dari components.foto-multi-ket (fotos[] + keterangan berurutan) ke relasi hasMany. */
trait SimpanFotoKeterangan
{
    protected function bersihKet($v): ?string
    {
        $v = trim((string) $v);
        return $v === '' ? null : mb_substr($v, 0, 255);
    }

    /** Return jumlah foto yang gagal. Satu foto gagal tidak membatalkan yang lain. */
    protected function simpanFotoRelasi(Request $request, $relasi, string $folder, string $capField, int $mulaiUrutan = 0): int
    {
        if (!$request->hasFile('fotos')) return 0;
        $files = $request->file('fotos');
        $files = is_array($files) ? $files : [$files];
        $gagal = 0;
        foreach ($files as $idx => $f) {
            if (!$f) continue;
            try {
                if (!$f->isValid()) { $gagal++; continue; }
                $s = ImageHelper::saveWithThumbnail($f, $folder);
                $relasi->create([
                    'path' => $s['original'], 'thumb_path' => $s['thumbnail'],
                    'keterangan' => $this->bersihKet($request->input("{$capField}.{$idx}")),
                    'urutan' => $mulaiUrutan + (int) $idx,
                ]);
            } catch (\Throwable $e) {
                $gagal++;
                \Log::error('Grounding: gagal simpan foto: '.$e->getMessage());
            }
        }
        return $gagal;
    }

    /** Ubah koma desimal menjadi titik pada field angka tertentu. */
    protected function normalisasiAngka(Request $request, array $fields): void
    {
        $in = [];
        foreach ($fields as $f) {
            $v = $request->input($f);
            if (is_string($v) && $v !== '') $in[$f] = str_replace(',', '.', trim($v));
        }
        if ($in) $request->merge($in);
    }
}
