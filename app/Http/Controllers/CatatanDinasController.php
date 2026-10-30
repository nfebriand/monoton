<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\AppSetting;
use App\Models\CatatanDinas;
use App\Models\CatatanDinasFoto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Catatan Dinas — narasi kejadian/kondisi/situasi saat dinas + foto berketerangan.
 * Semua pengguna login boleh menulis; yang dapat DILIHAT diatur CatatanDinas::scopeDapatDiakses().
 */
class CatatanDinasController extends Controller
{
    private function rules(): array
    {
        return [
            'tanggal'     => 'required|date',
            'jam_mulai'   => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i',
            'kategori'    => 'required|in:'.implode(',', array_keys(CatatanDinas::KATEGORI)),
            'judul'       => 'required|string|max:255',
            'lokasi'      => 'nullable|string|max:255',
            'narasi'      => 'required|string|max:100000',
            'fotos'       => 'nullable|array|max:30',
            'fotos.*'     => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'foto_keterangan'        => 'nullable|array',
            'foto_keterangan.*'      => 'nullable|string|max:255',
            'foto_keterangan_baru'   => 'nullable|array',
            'foto_keterangan_baru.*' => 'nullable|string|max:255',
            'foto_keterangan_lama'   => 'nullable|array',
            'foto_keterangan_lama.*' => 'nullable|string|max:255',
            'hapus_foto'             => 'nullable|array',
            'hapus_foto.*'           => 'integer',
        ];
    }

    private function bersih($v): ?string
    {
        $v = trim((string) $v);
        return $v === '' ? null : mb_substr($v, 0, 255);
    }

    /** Simpan file foto; satu foto gagal tidak membatalkan catatan. Mengembalikan jumlah foto gagal. */
    private function simpanFoto(CatatanDinas $c, Request $request, string $capField, int $mulaiUrutan): int
    {
        if (!$request->hasFile('fotos')) return 0;
        $files = $request->file('fotos');
        $files = is_array($files) ? $files : [$files];
        $gagal = 0;
        foreach ($files as $idx => $file) {
            if (!$file) continue;
            try {
                if (!$file->isValid()) { $gagal++; continue; }
                $saved = ImageHelper::saveWithThumbnail($file, 'catatan-dinas');
                CatatanDinasFoto::create([
                    'catatan_dinas_id' => $c->id,
                    'path'       => $saved['original'],
                    'thumb_path' => $saved['thumbnail'],
                    'keterangan' => $this->bersih($request->input("{$capField}.{$idx}")),
                    'urutan'     => $mulaiUrutan + (int) $idx,
                ]);
            } catch (\Throwable $e) {
                $gagal++;
                \Log::error('Catatan Dinas: gagal simpan foto: '.$e->getMessage());
            }
        }
        return $gagal;
    }

    private function cek(CatatanDinas $c, bool $ubah = false): void
    {
        $u = auth()->user();
        if (!($ubah ? $c->dapatDiubah($u) : $c->dapatDilihat($u))) {
            abort(403, $ubah ? 'Anda tidak dapat mengubah catatan ini.' : 'Anda tidak berhak melihat catatan ini.');
        }
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $q = CatatanDinas::with(['user'])->withCount('fotos')->dapatDiakses($user)
            ->orderByDesc('tanggal')->orderByDesc('id');

        if ($request->filled('tanggal_dari'))   $q->where('tanggal', '>=', $request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $q->where('tanggal', '<=', $request->tanggal_sampai);
        if ($request->filled('kategori') && isset(CatatanDinas::KATEGORI[$request->kategori])) $q->where('kategori', $request->kategori);
        if ($request->filled('user_id') && ($user->isAdmin() || $user->isAdminDivisi())) $q->where('user_id', (int) $request->user_id);
        if ($request->filled('divisi') && $user->isAdmin()) $q->where('divisi', $request->divisi);
        if ($request->filled('q')) {
            $kata = '%'.str_replace(['%','_'], ['\%','\_'], trim($request->q)).'%';
            $q->where(function ($w) use ($kata) {
                $w->where('judul', 'like', $kata)->orWhere('narasi', 'like', $kata)->orWhere('lokasi', 'like', $kata);
            });
        }

        $catatan   = $q->paginate(12)->withQueryString();
        $operators = collect();
        if ($user->isAdmin() || $user->isAdminDivisi()) {
            $operators = User::where('is_active', true)
                ->when(!$user->isAdmin(), fn($w) => $w->where('divisi', $user->divisi))
                ->orderBy('name')->get(['id','name']);
        }
        return view('catatan-dinas.index', compact('catatan', 'operators'));
    }

    public function create()
    {
        $u = auth()->user();
        return view('catatan-dinas.create', ['lokasiDefault' => $u->lokasi_dinas]);
    }

    public function store(Request $request)
    {
        $v = $request->validate($this->rules());
        $u = auth()->user();

        $c = CatatanDinas::create([
            'user_id'     => $u->id,
            'divisi'      => $u->divisi,
            'lokasi_dinas'=> $u->lokasi_dinas,
            'lokasi'      => $v['lokasi'] ?? null,
            'tanggal'     => $v['tanggal'],
            'jam_mulai'   => $v['jam_mulai'] ?? null,
            'jam_selesai' => $v['jam_selesai'] ?? null,
            'kategori'    => $v['kategori'],
            'judul'       => $v['judul'],
            'narasi'      => $v['narasi'],
        ]);

        $gagal = $this->simpanFoto($c, $request, 'foto_keterangan', 0);

        $r = redirect()->route('catatan-dinas.show', $c)->with('success', 'Catatan dinas berhasil disimpan.');
        return $gagal ? $r->with('warning', "$gagal foto gagal diunggah. Buka Edit untuk menambahkannya lagi.") : $r;
    }

    public function show(CatatanDinas $catatanDinas)
    {
        $this->cek($catatanDinas);
        $catatanDinas->load(['user', 'fotos']);
        return view('catatan-dinas.show', ['c' => $catatanDinas]);
    }

    public function edit(CatatanDinas $catatanDinas)
    {
        $this->cek($catatanDinas, true);
        $catatanDinas->load('fotos');
        return view('catatan-dinas.edit', ['c' => $catatanDinas]);
    }

    public function update(Request $request, CatatanDinas $catatanDinas)
    {
        $this->cek($catatanDinas, true);
        $v = $request->validate($this->rules());

        $catatanDinas->update([
            'lokasi'      => $v['lokasi'] ?? null,
            'tanggal'     => $v['tanggal'],
            'jam_mulai'   => $v['jam_mulai'] ?? null,
            'jam_selesai' => $v['jam_selesai'] ?? null,
            'kategori'    => $v['kategori'],
            'judul'       => $v['judul'],
            'narasi'      => $v['narasi'],
        ]);

        // keterangan foto lama
        foreach ((array) $request->input('foto_keterangan_lama', []) as $id => $ket) {
            CatatanDinasFoto::where('catatan_dinas_id', $catatanDinas->id)->where('id', (int) $id)
                ->update(['keterangan' => $this->bersih($ket)]);
        }
        // hapus foto
        foreach ((array) $request->input('hapus_foto', []) as $id) {
            $f = CatatanDinasFoto::where('catatan_dinas_id', $catatanDinas->id)->find((int) $id);
            if ($f) { ImageHelper::deleteWithThumbnail($f->path); $f->delete(); }
        }
        // foto baru
        $terakhir = (int) ($catatanDinas->fotos()->max('urutan') ?? -1);
        $gagal = $this->simpanFoto($catatanDinas, $request, 'foto_keterangan_baru', $terakhir + 1);

        $r = redirect()->route('catatan-dinas.show', $catatanDinas)->with('success', 'Catatan dinas berhasil diperbarui.');
        return $gagal ? $r->with('warning', "$gagal foto gagal diunggah. Coba tambahkan lagi.") : $r;
    }

    public function destroy(CatatanDinas $catatanDinas)
    {
        $this->cek($catatanDinas, true);
        foreach ($catatanDinas->fotos as $f) ImageHelper::deleteWithThumbnail($f->path);
        $catatanDinas->delete();
        return redirect()->route('catatan-dinas.index')->with('success', 'Catatan dinas dihapus.');
    }

    public function cetak(CatatanDinas $catatanDinas)
    {
        $this->cek($catatanDinas);
        $catatanDinas->load(['user', 'fotos']);

        $settings   = AppSetting::allKeyed();
        $satkerName = $settings['satuan_kerja'] ?? '';
        $appVersion = $settings['app_version'] ?? '1.0.0';
        $divisi     = $catatanDinas->divisi ?: ($catatanDinas->user->divisi ?? 'transmisi');
        $koordinator = AppSetting::getKoordinator($divisi, AppSetting::tanggalAcuan($catatanDinas->tanggal));

        $html = view('catatan-dinas.pdf', ['c' => $catatanDinas, 'satkerName' => $satkerName,
            'appVersion' => $appVersion, 'koordinator' => $koordinator])->render();

        try {
            $dompdf = new \Dompdf\Dompdf($this->dompdfOptions());
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();
            $nama = 'catatan-dinas-'.Str::slug(Str::limit($catatanDinas->judul, 40, '')).'-'.$catatanDinas->tanggal->format('Ymd').'.pdf';
            return response($dompdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$nama.'"',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Gagal cetak catatan dinas PDF: '.$e->getMessage());
            return back()->withErrors(['pdf' => 'Gagal membuat PDF: '.$e->getMessage()]);
        }
    }

    private function dompdfOptions(): \Dompdf\Options
    {
        $dirs = [storage_path('app/dompdf/fonts'), storage_path('app/dompdf/font-cache'), storage_path('app/dompdf/tmp')];
        foreach ($dirs as $d) { if (!is_dir($d)) @mkdir($d, 0775, true); }
        $opt = new \Dompdf\Options();
        $opt->setIsRemoteEnabled(false);
        $opt->setIsHtml5ParserEnabled(true);
        $opt->setDefaultFont('dejavu sans');
        $opt->setFontDir($dirs[0]);
        $opt->setFontCache($dirs[1]);
        $opt->setTempDir($dirs[2]);
        $opt->setChroot([base_path(), public_path(), storage_path()]);
        return $opt;
    }
}
