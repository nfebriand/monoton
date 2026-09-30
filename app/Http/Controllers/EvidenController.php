<?php
namespace App\Http\Controllers;

use App\Models\Eviden;
use App\Models\EvidenFoto;
use App\Helpers\ImageHelper;
use App\Models\User;
use App\Models\Lokasi;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class EvidenController extends Controller
{
    /**
     * Semua personil yang bisa dipilih sebagai "terlibat" dalam eviden:
     * - Operator (semua divisi/lokasi, termasuk tenaga bantuan)
     * - Admin Divisi (mereka juga turun langsung ke lapangan)
     * Super Admin tidak diikutkan karena perannya administratif sistem.
     */
    private function allOperators()
    {
        return User::whereIn('role', [User::ROLE_OPERATOR, User::ROLE_ADMIN_DIVISI])
            ->where('is_active', true)
            ->orderBy('divisi')
            ->orderBy('lokasi_dinas')
            ->orderBy('name')
            ->get();
    }

    /** Lokasi dari tabel lokasis (bukan hardcode/query pemancar/user) */
    private function lokasiList(?string $divisi = null)
    {
        return Lokasi::listAktif($divisi);
    }

    private function userDivisi(): string
    {
        return auth()->user()->divisi ?? 'transmisi';
    }

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Eviden::with(['user','operators','fotos'])
            ->orderByDesc('tanggal')->orderByDesc('jam_mulai');
      
        if ($user->isAdmin()) {
            if ($request->filled('divisi')) $query->where('divisi', $request->divisi);
        } elseif ($user->isAdminDivisi()) {
            // Admin Divisi: lihat eviden divisi sendiri ATAU yang ia terlibat
            $uid = $user->id;
            $divisi = $user->divisi;
            $query->where(function($q) use ($uid, $divisi) {
                $q->where('divisi', $divisi)
                  ->orWhereHas('operators', function($q2) use ($uid) {
                      $q2->where('users.id', $uid);
                  });
            });
        } else {
            // Operator: eviden milik sendiri ATAU yang ia terlibat (lintas divisi)
            $uid = $user->id;
            $query->where(function($q) use ($uid) {
                $q->where('user_id', $uid)
                  ->orWhereHas('operators', function($q2) use ($uid) {
                      $q2->where('users.id', $uid);
                  });
            });
        }

        if ($request->filled('tanggal_dari'))   $query->where('tanggal', '>=', $request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $query->where('tanggal', '<=', $request->tanggal_sampai);
        if ($request->filled('user_id'))        $query->where('user_id', $request->user_id);

        $evidens   = $query->paginate(12)->withQueryString();
        $operators = $this->allOperators();

        return view('eviden.index', compact('evidens','operators'));
    }

    public function create()
    {
        $operators    = $this->allOperators();
        $lokasiList   = $this->lokasiList();
        $divisiDefault = $this->userDivisi();
        return view('eviden.create', compact('operators','lokasiList','divisiDefault'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul'          => 'required|string|max:255',
            'deskripsi'      => 'nullable|string',
            'tanggal'        => 'required|date',
            'jam_mulai'      => 'required|date_format:H:i',
            'jam_selesai'    => 'required|date_format:H:i',
            'lokasi'         => 'nullable|string|max:255',
            'divisi'         => 'required|in:transmisi,studio,sarana',
            'supervisi'      => 'nullable|string|max:255',
            'operator_ids'   => 'nullable|array',
            'operator_ids.*' => 'exists:users,id',
            'fotos'          => 'nullable|array',
            'fotos.*'        => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'foto_keterangan'=> 'nullable|array',
        ]);

        $eviden = Eviden::create(array_merge($validated, ['user_id' => auth()->id()]));

        if (!empty($validated['operator_ids'])) {
            $eviden->operators()->sync($validated['operator_ids']);
        }

        if ($request->hasFile('fotos')) {
            $fotos = is_array($request->file('fotos'))
                ? $request->file('fotos')
                : [$request->file('fotos')];
            foreach ($fotos as $idx => $foto) {
                if (!$foto || !$foto->isValid()) continue;
                $saved = ImageHelper::saveWithThumbnail($foto, 'eviden');
                EvidenFoto::create([
                    'eviden_id'  => $eviden->id,
                    'path'       => $saved['original'],
                    'thumb_path' => $saved['thumbnail'],
                    'keterangan' => $request->input("foto_keterangan.{$idx}"),
                    'urutan'     => $idx,
                ]);
            }
        }

        return redirect()->route('eviden.show', $eviden)
            ->with('success', 'Catatan eviden berhasil disimpan.');
    }

    public function show(Eviden $eviden)
    {
        $this->authorizeEviden($eviden);
        $eviden->load(['user','operators','fotos']);
        return view('eviden.show', compact('eviden'));
    }

    public function edit(Eviden $eviden)
    {
        $this->authorizeEviden($eviden, true);
        $eviden->load(['operators','fotos']);
        $operators     = $this->allOperators();
        $lokasiList    = $this->lokasiList();
        $divisiDefault = $eviden->divisi ?? $this->userDivisi();
        return view('eviden.edit', compact('eviden','operators','lokasiList','divisiDefault'));
    }

    public function update(Request $request, Eviden $eviden)
    {
        $this->authorizeEviden($eviden, true);

        $validated = $request->validate([
            'judul'          => 'required|string|max:255',
            'deskripsi'      => 'nullable|string',
            'tanggal'        => 'required|date',
            'jam_mulai'      => 'required|date_format:H:i',
            'jam_selesai'    => 'required|date_format:H:i',
            'lokasi'         => 'nullable|string|max:255',
            'divisi'         => 'required|in:transmisi,studio,sarana',
            'supervisi'      => 'nullable|string|max:255',
            'operator_ids'   => 'nullable|array',
            'operator_ids.*' => 'exists:users,id',
        ]);

        // Admin Divisi tidak boleh memindahkan eviden keluar dari divisinya sendiri
        // (authorizeEviden di atas hanya mengecek divisi LAMA sebelum update).
        $user = auth()->user();
        if ($user->isAdminDivisi() && $validated['divisi'] !== $user->divisi) {
            return back()->withErrors(['divisi' => 'Anda tidak dapat memindahkan eviden ke divisi lain.'])->withInput();
        }

        $eviden->update($validated);
        $eviden->operators()->sync($validated['operator_ids'] ?? []);
        if ($request->hasFile('fotos')) {
            $last  = $eviden->fotos()->max('urutan') ?? -1;
            $fotos = is_array($request->file('fotos'))
                ? $request->file('fotos')
                : [$request->file('fotos')];
            foreach ($fotos as $idx => $foto) {
                if (!$foto || !$foto->isValid()) continue;
                $saved = ImageHelper::saveWithThumbnail($foto, 'eviden');
                EvidenFoto::create([
                    'eviden_id'  => $eviden->id,
                    'path'       => $saved['original'],
                    'thumb_path' => $saved['thumbnail'],
                    'keterangan' => $request->input("foto_keterangan_baru.{$idx}"),
                    'urutan'     => $last + $idx + 1,
                ]);
            }
        }

        if ($request->has('hapus_foto')) {
            foreach ($request->hapus_foto as $fotoId) {
                $f = EvidenFoto::where('eviden_id', $eviden->id)->find($fotoId);
                if ($f) { ImageHelper::deleteWithThumbnail($f->path); $f->delete(); }
            }
        }

        return redirect()->route('eviden.show', $eviden)
            ->with('success', 'Eviden berhasil diperbarui.');
    }

    public function destroy(Eviden $eviden)
    {
        $this->authorizeEviden($eviden, true);
        foreach ($eviden->fotos as $f) ImageHelper::deleteWithThumbnail($f->path);
        $eviden->delete();
        return redirect()->route('eviden.index')->with('success', 'Eviden berhasil dihapus.');
    }

    public function cetak(Eviden $eviden)
    {
        $this->authorizeEviden($eviden);
        $eviden->load(['user','operators','fotos']);
        $settings        = AppSetting::allKeyed();
        $satkerName      = $settings['satuan_kerja'] ?? '';
        $koordinatorName = $settings['koordinator']  ?? '';
        $appVersion      = $settings['app_version']  ?? '1.0.0';
        $koordinator     = AppSetting::getKoordinator($eviden->divisi ?? 'transmisi');

        $html = view('eviden.pdf', compact(
            'eviden','satkerName','koordinatorName','appVersion','koordinator'
        ))->render();

        try {
            $dompdf = new \Dompdf\Dompdf($this->dompdfOptions());
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();
            $filename = 'eviden-'.Str::slug($eviden->judul).'-'.$eviden->tanggal->format('Ymd').'.pdf';
            return response($dompdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Gagal cetak eviden PDF: '.$e->getMessage());
            return back()->withErrors(['pdf' => 'Gagal membuat PDF: '.$e->getMessage()]);
        }
    }


    /**
     * Bulk cetak semua eviden (sesuai filter) menjadi 1 file PDF.
     * Setiap eviden = 1 halaman, dengan page break.
     */
    public function bulkCetak(Request $request)
    {
        $user  = auth()->user();
        $query = Eviden::with(['user','operators','fotos'])
            ->orderBy('tanggal')->orderBy('jam_mulai');

       // Scope akses sama seperti index()
        if ($user->isAdmin()) {
            if ($request->filled('divisi')) $query->where('divisi', $request->divisi);
        } elseif ($user->isAdminDivisi()) {
            $uid = $user->id;
            $divisi = $user->divisi;
            $query->where(function($q) use ($uid, $divisi) {
                $q->where('divisi', $divisi)
                  ->orWhereHas('operators', function($q2) use ($uid) {
                      $q2->where('users.id', $uid);
                  });
            });
        } else {
            $uid = $user->id;
            $query->where(function($q) use ($uid) {
                $q->where('user_id', $uid)
                  ->orWhereHas('operators', function($q2) use ($uid) {
                      $q2->where('users.id', $uid);
                  });
            });
        }

        // Filter tambahan
        if ($request->filled('tanggal_dari'))   $query->where('tanggal', '>=', $request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $query->where('tanggal', '<=', $request->tanggal_sampai);
        if ($request->filled('user_id'))        $query->where('user_id', $request->user_id);
        if ($request->filled('divisi_filter'))  $query->where('divisi', $request->divisi_filter);

        // Batasi max 100 eviden sekaligus agar tidak timeout
        $evidens = $query->limit(100)->get();

        if ($evidens->isEmpty()) {
            return back()->withErrors(['pdf' => 'Tidak ada data eviden untuk dicetak.']);
        }

        $settings   = AppSetting::allKeyed();
        $satkerName = $settings['satuan_kerja'] ?? '';
        $appVersion = $settings['app_version']  ?? '1.0.0';

        // Ambil koordinator per divisi (cache agar tidak query berulang)
        $koordinatorCache = [];
        $koordinatorCache['transmisi'] = AppSetting::getKoordinator('transmisi');
        $koordinatorCache['studio']    = AppSetting::getKoordinator('studio');
        $koordinatorCache['sarana']    = AppSetting::getKoordinator('sarana');

        $html = view('eviden.pdf-bulk', compact(
            'evidens','satkerName','appVersion','koordinatorCache','settings'
        ))->render();

        try {
            $dompdf = new \Dompdf\Dompdf($this->dompdfOptions());
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();

            $dari   = $request->filled('tanggal_dari')   ? '-'.$request->tanggal_dari   : '';
            $sampai = $request->filled('tanggal_sampai') ? 's'.$request->tanggal_sampai : '';
            $filename = 'eviden-bulk'.$dari.$sampai.'-('.count($evidens).'eviden).pdf';

            return response($dompdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Gagal bulk cetak eviden PDF: '.$e->getMessage());
            return back()->withErrors(['pdf' => 'Gagal membuat PDF: '.$e->getMessage()]);
        }
    }
    
    private function authorizeEviden(Eviden $eviden, bool $forEdit = false): void
    {
        $user = auth()->user();
        
        if ($user->isAdmin()) return;

        $milik    = $eviden->user_id === $user->id;
        $terlibat = $eviden->operators()->where('users.id', $user->id)->exists();

        if ($user->isAdminDivisi()) {
            $satuDivisi = $eviden->divisi === $user->divisi;

            if ($forEdit) {
                // Saat mencoba Edit/Update/Delete: WAJIB satu divisi
                if (!$satuDivisi) {
                    abort(403, 'Anda tidak dapat mengubah atau menghapus eviden dari divisi lain.');
                }
            } else {
                // Saat melihat (Show/Cetak): Boleh jika satu divisi, miliknya, ATAU ia terlibat
                if (!$satuDivisi && !$milik && !$terlibat) {
                    abort(403);
                }
            }
            return;
        }

        // Operator: bisa lihat/cetak eviden yg ia buat ATAU terlibat — lintas divisi
        if ($forEdit && !$milik) abort(403, 'Anda hanya dapat mengubah eviden milik Anda sendiri.');
        if (!$forEdit && !$milik && !$terlibat) abort(403);
    }

    private function dompdfOptions(): \Dompdf\Options
    {
        $fontDir   = storage_path('app/dompdf/fonts');
        $fontCache = storage_path('app/dompdf/font-cache');
        $tempDir   = storage_path('app/dompdf/tmp');
        foreach ([$fontDir,$fontCache,$tempDir] as $dir) {
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
        }
        $opt = new \Dompdf\Options();
        $opt->setIsRemoteEnabled(false); // dimatikan: cegah SSRF via fetch remote di dompdf
        $opt->setIsHtml5ParserEnabled(true);
        $opt->setDefaultFont('dejavu sans');
        $opt->setFontDir($fontDir);
        $opt->setFontCache($fontCache);
        $opt->setTempDir($tempDir);
        $opt->setChroot([base_path(), public_path(), storage_path()]);
        return $opt;
    }
}
