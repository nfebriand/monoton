<?php
namespace App\Http\Controllers;

use App\Models\StudioPerangkat;
use App\Models\StudioPerangkatFoto;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class StudioPerangkatController extends Controller
{
    private function checkAccess(): void
    {
        $user = auth()->user();
        if (!$user->canAccessStudio()) {
            abort(403, 'Menu ini hanya untuk Divisi Studio atau Operator Way Kanan.');
        }
    }

    private function checkAdminAccess(): void
    {
        $user = auth()->user();
        // Izinkan: Super Admin, Admin Divisi Studio, atau Admin Divisi Way Kanan
        $boleh = $user->isAdmin()
            || ($user->isAdminDivisi() && $user->isDivisi('studio'))
            || ($user->isAdminDivisi() && $user->isWayKanan());
        if (!$boleh) {
            abort(403, 'Hanya Admin Divisi Studio atau Admin Way Kanan yang dapat mengelola master perangkat.');
        }
    }

    private function saveFotos(StudioPerangkat $perangkat, Request $request, int $startUrutan = 0): void
    {
        if (!$request->hasFile('fotos')) return;
        $fotos = is_array($request->file('fotos')) ? $request->file('fotos') : [$request->file('fotos')];
        foreach ($fotos as $idx => $foto) {
            if (!$foto || !$foto->isValid()) continue;
            $path = $foto->store('studio/perangkat', 'public');
            StudioPerangkatFoto::create([
                'studio_perangkat_id' => $perangkat->id,
                'path'                => $path,
                'keterangan'          => $request->input("foto_keterangan.{$idx}"),
                'urutan'              => $startUrutan + $idx,
            ]);
        }
    }

    public function index(Request $request)
    {
        $this->checkAccess();
        $user  = auth()->user();
        $query = StudioPerangkat::orderBy('lokasi')->orderBy('kategori')->orderBy('nama');
        if ($request->filled('kategori')) $query->where('kategori', $request->kategori);
        if ($request->filled('kondisi'))  $query->where('kondisi', $request->kondisi);
        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('lokasi'))   $query->where('lokasi', $request->lokasi);
        // Way Kanan: default filter lokasi Way Kanan
        if (!$request->filled('lokasi') && $user->isWayKanan() && !$user->isAdmin() && !$user->isDivisi('studio')) {
            $query->where('lokasi', 'Way Kanan');
        }
        $perangkats     = $query->paginate(20)->withQueryString();
        $perluPerhatian = StudioPerangkat::where('status','aktif')->get()
            ->filter(fn($p) => in_array($p->status_maintenance, ['jatuh_tempo','terlambat']))
            ->sortBy(fn($p) => $p->jatuh_tempo_maintenance);
        $ringkasan = [
            'total'     => StudioPerangkat::count(),
            'aktif'     => StudioPerangkat::where('status','aktif')->count(),
            'baik'      => StudioPerangkat::where('kondisi','baik')->count(),
            'perhatian' => $perluPerhatian->count(),
        ];
        $lokasisStudio = ['Pahoman', 'Way Kanan'];
        return view('studio.perangkat.index', compact('perangkats','perluPerhatian','ringkasan','lokasisStudio'));
    }

    public function create()
    {
        $this->checkAdminAccess();
        $lokasisStudio = ['Pahoman', 'Way Kanan'];
        $defaultLokasi = auth()->user()->isWayKanan() ? 'Way Kanan' : 'Pahoman';
        return view('studio.perangkat.create', compact('lokasisStudio', 'defaultLokasi'));
    }

    public function store(Request $request)
    {
        $this->checkAdminAccess();
        $v = $request->validate([
            'nama'                      => 'required|string|max:255',
            'lokasi'                    => 'required|string|max:100',
            'kode_inventaris'           => 'nullable|string|max:100|unique:studio_perangkat,kode_inventaris',
            'kategori'                  => 'nullable|string|max:100',
            'merk'                      => 'nullable|string|max:100',
            'tipe'                      => 'nullable|string|max:100',
            'no_seri'                   => 'nullable|string|max:100',
            'tahun_pengadaan'           => 'nullable|integer|min:1990|max:'.date('Y'),
            'kondisi'                   => 'required|in:baik,rusak_ringan,rusak_berat',
            'status'                    => 'required|in:aktif,tidak_aktif,disposal',
            'interval_maintenance_hari' => 'nullable|integer|min:1',
            'keterangan'                => 'nullable|string',
            'fotos'                     => 'nullable|array',
            'fotos.*'                   => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'foto_keterangan'           => 'nullable|array',
        ]);
        unset($v['fotos'], $v['foto_keterangan']);
        $perangkat = StudioPerangkat::create($v);
        $this->saveFotos($perangkat, $request);
        return redirect()->route('studio.perangkat.show', $perangkat)->with('success', 'Perangkat studio berhasil ditambahkan.');
    }

    public function show(StudioPerangkat $studioPerangkat)
    {
        $this->checkAccess();
        $studioPerangkat->load(['maintenances.user','fotos']);
        return view('studio.perangkat.show', compact('studioPerangkat'));
    }

    public function edit(StudioPerangkat $studioPerangkat)
    {
        $this->checkAdminAccess();
        $studioPerangkat->load('fotos');
        $lokasisStudio = ['Pahoman', 'Way Kanan'];
        return view('studio.perangkat.edit', compact('studioPerangkat', 'lokasisStudio'));
    }

    public function update(Request $request, StudioPerangkat $studioPerangkat)
    {
        $this->checkAdminAccess();
        $v = $request->validate([
            'nama'                      => 'required|string|max:255',
			'lokasi'					=> 'required|string|max:100',            
            'kode_inventaris'           => 'nullable|string|max:100|unique:studio_perangkat,kode_inventaris,'.$studioPerangkat->id,
            'kategori'                  => 'nullable|string|max:100',
            'merk'                      => 'nullable|string|max:100',
            'tipe'                      => 'nullable|string|max:100',
            'no_seri'                   => 'nullable|string|max:100',
            'tahun_pengadaan'           => 'nullable|integer|min:1990|max:'.date('Y'),
            'kondisi'                   => 'required|in:baik,rusak_ringan,rusak_berat',
            'status'                    => 'required|in:aktif,tidak_aktif,disposal',
            'interval_maintenance_hari' => 'nullable|integer|min:1',
            'keterangan'                => 'nullable|string',
            'fotos'                     => 'nullable|array',
            'fotos.*'                   => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'foto_keterangan'           => 'nullable|array',
            'hapus_foto'                => 'nullable|array',
        ]);
        unset($v['fotos'], $v['foto_keterangan'], $v['hapus_foto']);
       $studioPerangkat->update($v);

        if ($request->filled('hapus_foto')) {
            foreach ($request->hapus_foto as $fotoId) {
                $f = StudioPerangkatFoto::where('studio_perangkat_id', $studioPerangkat->id)->find($fotoId);
                if ($f) { Storage::disk('public')->delete($f->path); $f->delete(); }
            }
        }

        $start = $studioPerangkat->fotos()->max('urutan') ?? -1;
        $this->saveFotos($studioPerangkat, $request, $start + 1);

        return redirect()->route('studio.perangkat.show', $studioPerangkat)->with('success', 'Perangkat berhasil diperbarui.');
    }

    public function destroy(StudioPerangkat $studioPerangkat)
    {
        $this->checkAdminAccess();
        if ($studioPerangkat->maintenances()->exists()) {
            return back()->withErrors(['perangkat' => 'Perangkat ini memiliki riwayat maintenance dan tidak dapat dihapus.']);
        }
        foreach ($studioPerangkat->fotos as $f) Storage::disk('public')->delete($f->path);
        $studioPerangkat->delete();
        return redirect()->route('studio.perangkat.index')->with('success', 'Perangkat berhasil dihapus.');
    }

    public function cetakInventaris()
    {
        $this->checkAccess();
        $perangkats  = StudioPerangkat::orderBy('kategori')->orderBy('nama')->get();
        $settings    = AppSetting::allKeyed();
        $koordinator = AppSetting::getKoordinator('studio');
        $kabid       = AppSetting::getKabid();
        $html = view('studio.laporan.pdf-inventaris', compact('perangkats','settings','koordinator','kabid'))->render();
        return $this->streamPdf($html, 'inventaris-perangkat-studio-'.now()->format('Ymd'));
    }

    private function streamPdf(string $html, string $filename)
    {
        $fontDir   = storage_path('app/dompdf/fonts');
        $fontCache = storage_path('app/dompdf/font-cache');
        $tempDir   = storage_path('app/dompdf/tmp');
        foreach ([$fontDir, $fontCache, $tempDir] as $dir) {
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
        }
        $opt = new \Dompdf\Options();
        $opt->setIsRemoteEnabled(true);
        $opt->setIsHtml5ParserEnabled(true);
        $opt->setDefaultFont('dejavu sans');
        $opt->setFontDir($fontDir);
        $opt->setFontCache($fontCache);
        $opt->setTempDir($tempDir);
        $opt->setChroot([base_path(), public_path(), storage_path()]);
        $dompdf = new \Dompdf\Dompdf($opt);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
        ]);
    }
}