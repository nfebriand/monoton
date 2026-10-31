<?php
namespace App\Http\Controllers;

use App\Models\Eviden;
use App\Models\EvidenFoto;
use App\Helpers\ImageHelper;
use App\Models\User;
use App\Models\Lokasi;
use App\Models\AppSetting;
use App\Models\PerawatanRutin;
use App\Models\GroundingTitik;
use App\Models\GroundingWilayah;
use App\Support\Akses;
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
        if ($request->filled('user_id')) {
            $filterUid = (int) $request->user_id;
            $query->where(function($q) use ($filterUid) {
                $q->where('user_id', $filterUid)
                  ->orWhereHas('operators', function($q2) use ($filterUid) {
                      $q2->where('users.id', $filterUid);
                  });
            });
        }

        $evidens   = $query->paginate(12)->withQueryString();
        $operators = $this->allOperators();

        return view('eviden.index', compact('evidens','operators'));
    }

    /**
     * Jadwal perawatan rutin yang menjadi asal pembuatan eviden (?perawatan=ID), bila pengguna berhak.
     * Return [PerawatanRutin|null, array isian awal form].
     */
    private function konteksPerawatan($id): array
    {
        if (!$id) return [null, []];
        $user = auth()->user();
        if (!Akses::perawatanMenu($user)) return [null, []];
        $p = PerawatanRutin::with(['pemancar', 'gensetUnit'])->find((int) $id);
        if (!$p) return [null, []];
        $lokasi = $p->objek_tipe === 'pemancar' ? ($p->pemancar->lokasi ?? null) : null;
        if (!Akses::perawatanLihat($user, $p->objek_tipe, $lokasi)) return [null, []];

        $hm = $p->hmSekarang();
        $deskripsi = "Pelaksanaan perawatan rutin: {$p->nama_tugas}\n"
            . "Objek: {$p->nama_objek}".($p->lokasi_objek ? " ({$p->lokasi_objek})" : '')."\n"
            . ($hm !== null ? "HM unit saat ini: {$hm}\n" : '')
            . "\nUraian pekerjaan / hasil:\n- ";

        return [$p, [
            'judul'     => "Perawatan Rutin: {$p->nama_tugas} - {$p->nama_objek}",
            'deskripsi' => $deskripsi,
            'divisi'    => in_array($p->objek_tipe, ['genset', 'grounding'], true) ? 'sarana' : 'transmisi',
            'lokasi'    => $p->lokasi_objek,
        ]];
    }

    /**
     * Asal pembuatan eviden dari pengukuran grounding: ?grounding_titik=ID (satuan) atau ?grounding_wilayah=ID (ronde).
     * Return [['jenis'=>'titik|wilayah','id'=>int]|null, isian awal].
     */
    private function konteksGrounding($titikId, $wilayahId): array
    {
        $user = auth()->user();
        if (!Akses::groundingMasuk($user)) return [null, []];
        if ($titikId) {
            $t = GroundingTitik::with('wilayah')->find((int) $titikId);
            if (!$t) return [null, []];
            return [['jenis' => 'titik', 'id' => $t->id, 'label' => $t->kode.' - '.$t->nama], [
                'judul' => "Pengukuran Grounding: {$t->kode} - {$t->nama}",
                'deskripsi' => "Pengukuran tahanan grounding titik {$t->kode} ({$t->nama}), wilayah {$t->wilayah->nama}.\nBatas maksimum: ".rtrim(rtrim(number_format((float) $t->batas_ohm, 2, ',', ''), '0'), ',')." \u{03A9}\n\nUraian pekerjaan / hasil:\n- ",
                'divisi' => 'sarana', 'lokasi' => $t->wilayah->lokasi ?? $t->wilayah->nama,
            ]];
        }
        if ($wilayahId) {
            $w = GroundingWilayah::with(['titiks' => fn($q) => $q->where('status', 'aktif')])->find((int) $wilayahId);
            if (!$w) return [null, []];
            return [['jenis' => 'wilayah', 'id' => $w->id, 'label' => $w->nama], [
                'judul' => "Ronde Pengukuran Grounding: {$w->nama}",
                'deskripsi' => "Ronde pengukuran tahanan grounding wilayah {$w->nama}.\nTitik: ".$w->titiks->pluck('kode')->implode(', ')."\n\nUraian pekerjaan / hasil:\n- ",
                'divisi' => 'sarana', 'lokasi' => $w->lokasi ?? $w->nama,
            ]];
        }
        return [null, []];
    }

    public function create(Request $request)
    {
        $operators    = $this->allOperators();
        $lokasiList   = $this->lokasiList();
        $divisiDefault = $this->userDivisi();
        [$perawatan, $prefill] = $this->konteksPerawatan($request->query('perawatan'));
        if ($perawatan && auth()->user()->isAdmin()) $divisiDefault = $prefill['divisi'];
        [$grounding, $prefillG] = $this->konteksGrounding($request->query('grounding_titik'), $request->query('grounding_wilayah'));
        if ($grounding) { $prefill = $prefillG; if (auth()->user()->isAdmin()) $divisiDefault = 'sarana'; }
        return view('eviden.create', compact('operators','lokasiList','divisiDefault','perawatan','prefill','grounding'));
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
            'foto_keterangan.*' => 'nullable|string|max:255',
            'perawatan_rutin_id' => 'nullable|integer',
            'grounding_titik_id' => 'nullable|integer',
            'grounding_wilayah_id' => 'nullable|integer',
        ]);
        $gTitik = $validated['grounding_titik_id'] ?? null; $gWilayah = $validated['grounding_wilayah_id'] ?? null;
        $perawatanId = $validated['perawatan_rutin_id'] ?? null;
        unset($validated['perawatan_rutin_id'], $validated['foto_keterangan'], $validated['grounding_titik_id'], $validated['grounding_wilayah_id']);

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
                    'keterangan' => $this->bersihKet($request->input("foto_keterangan.{$idx}")),
                    'urutan'     => $idx,
                ]);
            }
        }

        // Dibuat dari jadwal perawatan rutin: lanjut ke pencatatan pelaksanaan dengan eviden ini terpilih
        [$perawatan] = $this->konteksPerawatan($perawatanId);
        if ($perawatan) {
            return redirect()->to(route('perawatan.show', ['perawatanRutin' => $perawatan, 'eviden' => $eviden->id]).'#catat')
                ->with('success', 'Eviden tersimpan. Satu langkah lagi: catat pelaksanaan perawatan agar jadwal berikutnya dihitung.');
        }

        // Dibuat dari pengukuran grounding: kembali ke form pengukuran dengan eviden ini terpilih
        [$grounding] = $this->konteksGrounding($gTitik, $gWilayah);
        if ($grounding) {
            $to = $grounding['jenis'] === 'titik'
                ? route('grounding.ukur.create', ['groundingTitik' => $grounding['id'], 'eviden' => $eviden->id])
                : route('grounding.ronde', ['groundingWilayah' => $grounding['id'], 'eviden' => $eviden->id]);
            return redirect()->to($to)->with('success', 'Eviden tersimpan. Lanjutkan mengisi hasil pengukuran; eviden ini sudah terpilih.');
        }

        return redirect()->route('eviden.show', $eviden)
            ->with('success', 'Catatan eviden berhasil disimpan.');
    }

    public function show(Eviden $eviden)
    {
        $this->authorizeEviden($eviden);
        $eviden->load(['user','operators','fotos','perawatanLogs.perawatan','groundingUkurs.titik']);
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
            'fotos'          => 'nullable|array',
            'fotos.*'        => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'foto_keterangan_baru'   => 'nullable|array',
            'foto_keterangan_baru.*' => 'nullable|string|max:255',
            'foto_keterangan_lama'   => 'nullable|array',
            'foto_keterangan_lama.*' => 'nullable|string|max:255',
            'hapus_foto'     => 'nullable|array',
            'hapus_foto.*'   => 'integer',
        ]);
        unset($validated['fotos'], $validated['foto_keterangan_baru'], $validated['foto_keterangan_lama'], $validated['hapus_foto']);

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
                    'keterangan' => $this->bersihKet($request->input("foto_keterangan_baru.{$idx}")),
                    'urutan'     => $last + $idx + 1,
                ]);
            }
        }

        // Perbarui keterangan foto yang sudah ada
        foreach ((array) $request->input('foto_keterangan_lama', []) as $fotoId => $ket) {
            EvidenFoto::where('eviden_id', $eviden->id)->where('id', (int) $fotoId)
                ->update(['keterangan' => $this->bersihKet($ket)]);
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
        // Riwayat perawatan rutin tetap ada, hanya tautan buktinya dilepas
        if (\Illuminate\Support\Facades\Schema::hasColumn('perawatan_rutin_log', 'eviden_id')) {
            \App\Models\PerawatanRutinLog::where('eviden_id', $eviden->id)->update(['eviden_id' => null]);
        }
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
        $koordinator     = AppSetting::getKoordinator($eviden->divisi ?? 'transmisi', AppSetting::tanggalAcuan($eviden->tanggal));

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
        if ($request->filled('user_id')) {
            $filterUid = (int) $request->user_id;
            $query->where(function($q) use ($filterUid) {
                $q->where('user_id', $filterUid)
                  ->orWhereHas('operators', function($q2) use ($filterUid) {
                      $q2->where('users.id', $filterUid);
                  });
            });
        }
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
        // Kunci: "divisi|tanggal-acuan" -> pejabat yang menjabat pada tanggal eviden tsb
        $koordinatorCache = [];
        foreach ($evidens as $ev) {
            $div = $ev->divisi ?? 'transmisi';
            $acuan = AppSetting::tanggalAcuan($ev->tanggal);
            $koordinatorCache[$div.'|'.$acuan] ??= AppSetting::getKoordinator($div, $acuan);
        }
        $koordinatorCache['transmisi'] = AppSetting::getKoordinator('transmisi');

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
    
    private function bersihKet($v): ?string
    {
        $v = trim((string) $v);
        return $v === '' ? null : mb_substr($v, 0, 255);
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