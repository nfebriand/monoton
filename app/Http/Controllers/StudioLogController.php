<?php
namespace App\Http\Controllers;

use App\Models\StudioLog;
use App\Models\StudioLogFoto;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudioLogController extends Controller
{
    private function checkAccess(): void
    {
        $user = auth()->user();
        if (!$user->canAccessStudio()) {
            abort(403, 'Menu ini hanya untuk Divisi Studio atau Operator Way Kanan.');
        }
    }

    private function canEdit(StudioLog $log): bool
    {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isAdminDivisi()) return true;
        return auth()->id() === $log->user_id;
    }

    private function saveFotos(StudioLog $log, Request $request, string $field = 'fotos', int $startUrutan = 0): void
    {
        if (!$request->hasFile($field)) return;
        $fotos = is_array($request->file($field)) ? $request->file($field) : [$request->file($field)];
        foreach ($fotos as $idx => $foto) {
            if (!$foto || !$foto->isValid()) continue;
            $path = $foto->store('studio/logbook', 'public');
            StudioLogFoto::create([
                'studio_log_id' => $log->id,
                'path'          => $path,
                'keterangan'    => $request->input("foto_keterangan.{$idx}"),
                'urutan'        => $startUrutan + $idx,
            ]);
        }
    }

    public function index(Request $request)
    {
        $this->checkAccess();

        $query = StudioLog::with('user')->orderByDesc('tanggal')->orderByDesc('jam_mulai');

        if ($request->filled('tanggal_dari'))   $query->where('tanggal', '>=', $request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $query->where('tanggal', '<=', $request->tanggal_sampai);
        if ($request->filled('shift'))          $query->where('shift', $request->shift);
        if ($request->filled('kondisi') && $request->kondisi === 'gangguan') {
            $query->where(function($q) {
                foreach (array_keys(StudioLog::CHECKLIST_ITEMS) as $key) {
                    $q->orWhere($key, 'gangguan');
                }
            });
        }

            if ($request->filled('user_id')) $query->where('user_id', $request->user_id);

        $logs = $query->paginate(15)->withQueryString();

        $bulanIni  = StudioLog::whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year);
        $ringkasan = [
            'total'    => (clone $bulanIni)->count(),
            'gangguan' => (clone $bulanIni)->get()->filter(fn($l) => $l->ada_gangguan)->count(),
            'pagi'     => (clone $bulanIni)->where('shift','pagi')->count(),
            'siang'    => (clone $bulanIni)->where('shift','siang')->count(),
            'malam'    => (clone $bulanIni)->where('shift','malam')->count(),
        ];

        $operators = \App\Models\User::where('is_active', true)
            ->where(function($q) {
                $q->where('divisi', 'studio')
                  ->orWhere(function($q2) {
                      $q2->where('divisi', 'transmisi')
                         ->whereRaw("LOWER(TRIM(lokasi_dinas)) = 'way kanan'");
                  });
            })
            ->orderBy('name')->get();

        return view('studio.logbook.index', compact('logs', 'ringkasan', 'operators'));
    }

    public function create()
    {
        $this->checkAccess();
        return view('studio.logbook.create');
    }

    public function store(Request $request)
    {
        $this->checkAccess();

        $rules = [
            'tanggal'         => 'required|date',
            'shift'           => 'required|in:pagi,siang,sore,malam,mcr',
            'jam_mulai'       => 'required|date_format:H:i',
            'jam_selesai'     => 'nullable|date_format:H:i',
            'catatan_petugas' => 'nullable|string',
            'fotos'           => 'nullable|array',
            'fotos.*'         => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'foto_keterangan' => 'nullable|array',
        ];
        foreach (array_keys(StudioLog::CHECKLIST_ITEMS) as $key) {
            $rules[$key]        = 'required|in:baik,gangguan,tidak_ada';
            $rules[$key.'_ket'] = 'nullable|string|max:500';
        }

        $v            = $request->validate($rules);
        $v['user_id'] = auth()->id();

        // Hapus key foto dari data yang masuk ke model
        unset($v['fotos'], $v['foto_keterangan']);

        $log = StudioLog::create($v);

        $this->saveFotos($log, $request);

        return redirect()->route('studio.logbook.show', $log)->with('success', 'Logbook studio berhasil disimpan.');
    }

    public function show(StudioLog $studioLog)
    {
        $this->checkAccess();
        $studioLog->load(['user','fotos']);
        return view('studio.logbook.show', compact('studioLog'));
    }

    public function edit(StudioLog $studioLog)
    {
        $this->checkAccess();
        if (!$this->canEdit($studioLog)) abort(403, 'Anda hanya dapat mengedit log milik Anda sendiri.');
        $studioLog->load('fotos');
        return view('studio.logbook.edit', compact('studioLog'));
    }

    public function update(Request $request, StudioLog $studioLog)
    {
        $this->checkAccess();
        if (!$this->canEdit($studioLog)) abort(403);

        $rules = [
            'tanggal'         => 'required|date',
            'shift'           => 'required|in:pagi,siang,sore,malam,mcr',
            'jam_mulai'       => 'required|date_format:H:i',
            'jam_selesai'     => 'nullable|date_format:H:i',
            'catatan_petugas' => 'nullable|string',
            'fotos'           => 'nullable|array',
            'fotos.*'         => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'foto_keterangan' => 'nullable|array',
            'hapus_foto'      => 'nullable|array',
        ];
        foreach (array_keys(StudioLog::CHECKLIST_ITEMS) as $key) {
            $rules[$key]        = 'required|in:baik,gangguan,tidak_ada';
            $rules[$key.'_ket'] = 'nullable|string|max:500';
        }

        $v = $request->validate($rules);
        unset($v['fotos'], $v['foto_keterangan'], $v['hapus_foto']);

        $studioLog->update($v);

        // Hapus foto yang dipilih
        if ($request->filled('hapus_foto')) {
            foreach ($request->hapus_foto as $fotoId) {
                $f = StudioLogFoto::where('studio_log_id', $studioLog->id)->find($fotoId);
                if ($f) { Storage::disk('public')->delete($f->path); $f->delete(); }
            }
        }

        // Tambah foto baru
        $startUrutan = $studioLog->fotos()->max('urutan') ?? -1;
        $this->saveFotos($studioLog, $request, 'fotos', $startUrutan + 1);

        return redirect()->route('studio.logbook.show', $studioLog)->with('success', 'Logbook berhasil diperbarui.');
    }

    public function destroy(StudioLog $studioLog)
    {
        $this->checkAccess();
        if (!$this->canEdit($studioLog)) abort(403);
        foreach ($studioLog->fotos as $f) Storage::disk('public')->delete($f->path);
        $studioLog->delete();
        return redirect()->route('studio.logbook.index')->with('success', 'Logbook berhasil dihapus.');
    }


    public function bulkCetak(Request $request)
    {
        $this->checkAccess();

        $query = StudioLog::with(['user','fotos'])->orderBy('tanggal')->orderBy('jam_mulai');

        if ($request->filled('tanggal_dari'))   $query->where('tanggal', '>=', $request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $query->where('tanggal', '<=', $request->tanggal_sampai);
        if ($request->filled('shift'))          $query->where('shift', $request->shift);
        if ($request->filled('user_id'))        $query->where('user_id', $request->user_id);

        $logs = $query->limit(50)->get();

        if ($logs->isEmpty()) {
            return back()->withErrors(['pdf' => 'Tidak ada data logbook untuk dicetak.']);
        }

        $settings       = AppSetting::allKeyed();
        $koordinator    = AppSetting::getKoordinator('studio');
        $kabid          = AppSetting::getKabid();
        $operatorFilter = $request->filled('user_id')
            ? \App\Models\User::find($request->user_id)
            : null;

        $dari   = $request->filled('tanggal_dari')   ? '-'.$request->tanggal_dari   : '';
        $sampai = $request->filled('tanggal_sampai') ? 's'.$request->tanggal_sampai : '';
        $opName = $operatorFilter ? '-'.\Illuminate\Support\Str::slug($operatorFilter->name) : '';
        $filename = 'bulk-logbook-studio'.$dari.$sampai.$opName.'('.$logs->count().'log)';

        $html = view('studio.laporan.pdf-logbook-harian',
            compact('logs','settings','koordinator','kabid','operatorFilter')
        )->with('tanggal', $request->get('tanggal_dari', now()->toDateString()))
         ->render();

        return $this->streamPdf($html, $filename, 'portrait');
    }

    public function cetakHarian(Request $request)
    {
        $this->checkAccess();
        $tanggal     = $request->get('tanggal', now()->toDateString());
        $query = StudioLog::with(['user','fotos'])->where('tanggal', $tanggal)->orderBy('jam_mulai');
        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        $logs = $query->get();
        $operatorFilter = $request->filled('user_id') ? \App\Models\User::find($request->user_id) : null;
        $settings    = AppSetting::allKeyed();
        $koordinator = AppSetting::getKoordinator('studio');
        $kabid       = AppSetting::getKabid();
        $html   = view('studio.laporan.pdf-logbook-harian', compact('logs','tanggal','settings','koordinator','kabid','operatorFilter'))->render();
        $suffix = $operatorFilter ? '-'.\Illuminate\Support\Str::slug($operatorFilter->name) : '';
        return $this->streamPdf($html, 'logbook-harian-studio-'.$tanggal.$suffix, 'portrait');
    }

    public function cetakBulanan(Request $request)
    {
        $this->checkAccess();
        $bulan       = $request->get('bulan', now()->format('Y-m'));
        [$year, $month] = explode('-', $bulan);
        $qBulanan = StudioLog::with('user')->whereYear('tanggal', $year)->whereMonth('tanggal', $month)
                        ->orderBy('tanggal')->orderBy('jam_mulai');
        if ($request->filled('user_id')) $qBulanan->where('user_id', $request->user_id);
        $logs = $qBulanan->get();
        $operatorFilter = $request->filled('user_id') ? \App\Models\User::find($request->user_id) : null;
        $settings    = AppSetting::allKeyed();
        $koordinator = AppSetting::getKoordinator('studio');
        $kabid       = AppSetting::getKabid();
        $html   = view('studio.laporan.pdf-rekap-bulanan', compact('logs','bulan','year','month','settings','koordinator','kabid','operatorFilter'))->render();
        $suffix = $operatorFilter ? '-'.\Illuminate\Support\Str::slug($operatorFilter->name) : '';
        return $this->streamPdf($html, 'rekap-bulanan-studio-'.$bulan.$suffix, 'landscape');
    }

    private function streamPdf(string $html, string $filename, string $orientation = 'portrait')
    {
        $fontDir   = storage_path('app/dompdf/fonts');
        $fontCache = storage_path('app/dompdf/font-cache');
        $tempDir   = storage_path('app/dompdf/tmp');
        foreach ([$fontDir, $fontCache, $tempDir] as $dir) {
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
        $dompdf = new \Dompdf\Dompdf($opt);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
        ]);
    }
}