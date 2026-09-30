<?php
namespace App\Http\Controllers;

use App\Models\StudioMaintenance;
use App\Models\StudioPerangkat;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class StudioMaintenanceController extends Controller
{
    private function checkAccess(): void
    {
        $user = auth()->user();
        if (!$user->canAccessStudio()) {
            abort(403, 'Menu ini hanya untuk Divisi Studio atau Operator Way Kanan.');
        }
    }

    private function canDelete(): bool
    {
        $user = auth()->user();
        return $user->isAdmin() || ($user->isAdminDivisi() && $user->isDivisi('studio'));
    }

    public function index(Request $request)
    {
        $this->checkAccess();

        $query = StudioMaintenance::with(['perangkat','user'])->orderByDesc('tanggal');

        if ($request->filled('studio_perangkat_id')) $query->where('studio_perangkat_id', $request->studio_perangkat_id);
        if ($request->filled('jenis'))               $query->where('jenis', $request->jenis);
        if ($request->filled('tanggal_dari'))        $query->where('tanggal', '>=', $request->tanggal_dari);
        if ($request->filled('tanggal_sampai'))      $query->where('tanggal', '<=', $request->tanggal_sampai);

        $logs      = $query->paginate(15)->withQueryString();
        $perangkats = StudioPerangkat::where('status', 'aktif')->orderBy('nama')->get();

        $perluPerhatian = StudioPerangkat::where('status', 'aktif')->get()
            ->filter(fn($p) => in_array($p->status_maintenance, ['jatuh_tempo', 'terlambat']))
            ->sortBy(fn($p) => $p->jatuh_tempo_maintenance);

        return view('studio.maintenance.index', compact('logs', 'perangkats', 'perluPerhatian'));
    }

    public function create(Request $request)
    {
        $this->checkAccess();
        $perangkats      = StudioPerangkat::where('status', 'aktif')->orderBy('nama')->get();
        $perangkatTerpilih = $request->filled('studio_perangkat_id')
            ? StudioPerangkat::find($request->studio_perangkat_id)
            : null;
        return view('studio.maintenance.create', compact('perangkats', 'perangkatTerpilih'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();

        $v = $request->validate([
            'studio_perangkat_id'            => 'required|exists:studio_perangkat,id',
            'tanggal'                        => 'required|date',
            'jenis'                          => 'required|in:preventif,korektif,inspeksi',
            'uraian_pekerjaan'               => 'required|string',
            'hasil'                          => 'required|in:selesai,sebagian,tertunda',
            'biaya'                          => 'nullable|numeric|min:0',
            'sparepart_nama'                 => 'nullable|array',
            'sparepart_jumlah'               => 'nullable|array',
            'sparepart_satuan'               => 'nullable|array',
            'rencana_maintenance_berikutnya' => 'nullable|date',
            'keterangan'                     => 'nullable|string',
        ]);

        $sparepartTerpakai = [];
        if ($request->filled('sparepart_nama')) {
            foreach ($request->sparepart_nama as $idx => $nama) {
                if (!trim($nama)) continue;
                $sparepartTerpakai[] = [
                    'nama'   => $nama,
                    'jumlah' => $request->sparepart_jumlah[$idx] ?? 1,
                    'satuan' => $request->sparepart_satuan[$idx] ?? 'pcs',
                ];
            }
        }

        $log = StudioMaintenance::create([
            'studio_perangkat_id'            => $v['studio_perangkat_id'],
            'user_id'                        => auth()->id(),
            'tanggal'                        => $v['tanggal'],
            'jenis'                          => $v['jenis'],
            'uraian_pekerjaan'               => $v['uraian_pekerjaan'],
            'hasil'                          => $v['hasil'],
            'biaya'                          => $v['biaya'] ?? null,
            'sparepart_terpakai'             => $sparepartTerpakai ?: null,
            'rencana_maintenance_berikutnya' => $v['rencana_maintenance_berikutnya'] ?? null,
            'keterangan'                     => $v['keterangan'] ?? null,
        ]);

        // Update tanggal maintenance terakhir jika selesai
        if ($v['hasil'] === 'selesai') {
            StudioPerangkat::where('id', $v['studio_perangkat_id'])
                ->update(['maintenance_terakhir' => $v['tanggal']]);
        }

        return redirect()->route('studio.maintenance.show', $log)->with('success', 'Log maintenance berhasil disimpan.');
    }

    public function show(StudioMaintenance $studioMaintenance)
    {
        $this->checkAccess();
        $studioMaintenance->load(['perangkat', 'user']);
        return view('studio.maintenance.show', compact('studioMaintenance'));
    }

    public function destroy(StudioMaintenance $studioMaintenance)
    {
        if (!$this->canDelete()) abort(403, 'Hanya Admin Divisi Studio yang dapat menghapus log maintenance.');
        $studioMaintenance->delete();
        return redirect()->route('studio.maintenance.index')->with('success', 'Log maintenance berhasil dihapus.');
    }

    // ── PDF Laporan Maintenance ──
    public function cetakMaintenance(Request $request)
    {
        $this->checkAccess();
        $tanggalDari   = $request->get('tanggal_dari', now()->startOfMonth()->toDateString());
        $tanggalSampai = $request->get('tanggal_sampai', now()->toDateString());
        $logs = StudioMaintenance::with(['perangkat','user'])
                    ->whereBetween('tanggal', [$tanggalDari, $tanggalSampai])
                    ->orderBy('tanggal')->get();
        $settings    = AppSetting::allKeyed();
        $koordinator = AppSetting::getKoordinator('studio');
        $kabid       = AppSetting::getKabid();
        $html = view('studio.laporan.pdf-maintenance', compact('logs','tanggalDari','tanggalSampai','settings','koordinator','kabid'))->render();
        return $this->streamPdf($html, 'laporan-maintenance-studio-'.now()->format('Ymd'));
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