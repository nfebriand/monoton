<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\AppSetting;
use App\Models\GensetUnit;
use App\Models\JadwalShift;
use App\Models\SaranaLog;
use App\Models\SaranaLogFoto;
use App\Models\SaranaLogItem;
use App\Models\User;
use App\Support\Akses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Logbook Laporan Harian Teknik Sarana & Prasarana.
 * Default menampilkan milik sendiri; logbook rekan dapat dilihat (serah terima shift); hapus/ubah hanya milik sendiri (+admin).
 */
class SaranaLogController extends Controller
{
    private function masuk(): void
    {
        if (!Akses::saranaLogMasuk(auth()->user())) abort(403, 'Menu ini hanya untuk Divisi Sarana & Prasarana.');
    }

    private function bisaUbah(SaranaLog $l): bool
    {
        return Akses::saranaLogUbah(auth()->user(), $l->user_id);
    }

    private function bersih($v): ?string
    {
        $v = trim((string) $v);
        return $v === '' ? null : mb_substr($v, 0, 255);
    }

    private function rules(): array
    {
        return [
            'tanggal'     => 'required|date',
            'shift'       => 'required|in:'.implode(',', array_keys(JadwalShift::SKEMA_SARANA)),
            'jam_mulai'   => 'required|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i',
            'catatan_petugas' => 'nullable|string|max:100000',
            'item'        => 'required|array',
            'item.*.kondisi' => 'required|in:normal,kendala',
            'item.*.ket'  => 'nullable|string|max:1000',
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

    /** Pastikan setiap butir terisi & "Ada Kendala" wajib berketerangan. Return pesan galat atau null. */
    private function cekItem(Request $request, array $butir): ?array
    {
        $err = [];
        foreach ($butir as $b) {
            $in = $request->input('item.'.$b['kunci']);
            if (!is_array($in) || empty($in['kondisi'])) { $err[] = 'Butir "'.$b['label'].'" belum dipilih.'; continue; }
            if ($in['kondisi'] === 'kendala' && trim((string) ($in['ket'] ?? '')) === '') {
                $err[] = 'Butir "'.$b['label'].'": keterangan wajib diisi bila Ada Kendala.';
            }
        }
        return $err ?: null;
    }

    private function simpanItem(SaranaLog $log, Request $request, array $butir): void
    {
        $log->items()->delete();
        foreach ($butir as $i => $b) {
            $in = $request->input('item.'.$b['kunci'], []);
            SaranaLogItem::create([
                'sarana_log_id' => $log->id,
                'kunci'         => $b['kunci'],
                'genset_unit_id'=> $b['genset_unit_id'],
                'label'         => $b['label'],
                'deskripsi'     => $b['desc'],
                'kondisi'       => ($in['kondisi'] ?? 'normal') === 'kendala' ? 'kendala' : 'normal',
                'keterangan'    => $this->bersih($in['ket'] ?? null) ?? null,
                'urutan'        => $i,
            ]);
        }
    }

    private function simpanFoto(SaranaLog $log, Request $request, string $cap, int $mulai): int
    {
        if (!$request->hasFile('fotos')) return 0;
        $files = $request->file('fotos');
        $files = is_array($files) ? $files : [$files];
        $gagal = 0;
        foreach ($files as $idx => $f) {
            if (!$f) continue;
            try {
                if (!$f->isValid()) { $gagal++; continue; }
                $s = ImageHelper::saveWithThumbnail($f, 'sarana/logbook');
                SaranaLogFoto::create([
                    'sarana_log_id' => $log->id, 'path' => $s['original'], 'thumb_path' => $s['thumbnail'],
                    'keterangan' => $this->bersih($request->input("{$cap}.{$idx}")), 'urutan' => $mulai + (int) $idx,
                ]);
            } catch (\Throwable $e) {
                $gagal++;
                \Log::error('Logbook Sarana: gagal simpan foto: '.$e->getMessage());
            }
        }
        return $gagal;
    }

    public function index(Request $request)
    {
        $this->masuk();
        $user = auth()->user();
        $semua = $request->query('tampil') === 'semua';
        $bulan = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('bulan')) ? $request->query('bulan') : now()->format('Y-m');
        [$th, $bl] = explode('-', $bulan);

        $q = SaranaLog::with(['user', 'items'])->withCount('fotos')
            ->whereYear('tanggal', $th)->whereMonth('tanggal', $bl)
            ->orderByDesc('tanggal')->orderByDesc('shift')->orderByDesc('id');
        if (!$semua) $q->where('user_id', $user->id);
        if ($semua && $request->filled('user_id')) $q->where('user_id', (int) $request->user_id);
        if ($request->filled('shift')) $q->where('shift', (int) $request->shift);
        if ($request->query('kendala') === '1') $q->whereHas('items', fn($w) => $w->where('kondisi', 'kendala'));

        $logs = $q->paginate(15)->withQueryString();
        $petugas = User::where('is_active', true)->where('divisi', 'sarana')->orderBy('name')->get(['id', 'name']);
        return view('sarana.logbook.index', compact('logs', 'semua', 'bulan', 'petugas'));
    }

    public function create()
    {
        $this->masuk();
        if (GensetUnit::where('is_active', true)->count() === 0) {
            session()->now('warning', 'Belum ada unit genset aktif di master data; baris genset belum muncul.');
        }
        $butir = SaranaLog::daftarButir();
        $shiftDefault = SaranaLog::shiftSekarang();
        $sebelumnya = SaranaLog::with('user')->whereNotNull('catatan_petugas')->where('catatan_petugas', '!=', '')
            ->orderByDesc('tanggal')->orderByDesc('shift')->orderByDesc('id')->limit(3)->get();
        return view('sarana.logbook.create', compact('butir', 'shiftDefault', 'sebelumnya'));
    }

    public function store(Request $request)
    {
        $this->masuk();
        $v = $request->validate($this->rules());
        $butir = SaranaLog::daftarButir();
        if ($err = $this->cekItem($request, $butir)) return back()->withErrors($err)->withInput();

        $log = DB::transaction(function () use ($v, $request, $butir) {
            $log = SaranaLog::create([
                'user_id' => auth()->id(), 'tanggal' => $v['tanggal'], 'shift' => (int) $v['shift'],
                'jam_mulai' => $v['jam_mulai'], 'jam_selesai' => $v['jam_selesai'] ?? null,
                'catatan_petugas' => $v['catatan_petugas'] ?? null,
            ]);
            $this->simpanItem($log, $request, $butir);
            return $log;
        });

        $gagal = $this->simpanFoto($log, $request, 'foto_keterangan', 0);
        $r = redirect()->route('sarana.logbook.show', $log)->with('success', 'Logbook sarana berhasil disimpan.');
        return $gagal ? $r->with('warning', "$gagal foto gagal diunggah. Buka Edit untuk menambahkannya lagi.") : $r;
    }

    public function show(SaranaLog $saranaLog)
    {
        $this->masuk();
        $saranaLog->load(['user', 'items', 'fotos']);
        return view('sarana.logbook.show', ['log' => $saranaLog, 'bisaUbah' => $this->bisaUbah($saranaLog)]);
    }

    public function edit(SaranaLog $saranaLog)
    {
        $this->masuk();
        if (!$this->bisaUbah($saranaLog)) abort(403, 'Anda hanya dapat mengubah logbook milik sendiri.');
        $saranaLog->load(['items', 'fotos']);
        $butir = SaranaLog::daftarButir($saranaLog);
        return view('sarana.logbook.edit', ['log' => $saranaLog, 'butir' => $butir]);
    }

    public function update(Request $request, SaranaLog $saranaLog)
    {
        $this->masuk();
        if (!$this->bisaUbah($saranaLog)) abort(403, 'Anda hanya dapat mengubah logbook milik sendiri.');
        $saranaLog->load('items');
        $v = $request->validate($this->rules());
        $butir = SaranaLog::daftarButir($saranaLog);
        if ($err = $this->cekItem($request, $butir)) return back()->withErrors($err)->withInput();

        DB::transaction(function () use ($saranaLog, $v, $request, $butir) {
            $saranaLog->update([
                'tanggal' => $v['tanggal'], 'shift' => (int) $v['shift'], 'jam_mulai' => $v['jam_mulai'],
                'jam_selesai' => $v['jam_selesai'] ?? null, 'catatan_petugas' => $v['catatan_petugas'] ?? null,
            ]);
            $this->simpanItem($saranaLog, $request, $butir);
        });

        foreach ((array) $request->input('foto_keterangan_lama', []) as $id => $ket) {
            SaranaLogFoto::where('sarana_log_id', $saranaLog->id)->where('id', (int) $id)->update(['keterangan' => $this->bersih($ket)]);
        }
        foreach ((array) $request->input('hapus_foto', []) as $id) {
            $f = SaranaLogFoto::where('sarana_log_id', $saranaLog->id)->find((int) $id);
            if ($f) { ImageHelper::deleteWithThumbnail($f->path); $f->delete(); }
        }
        $gagal = $this->simpanFoto($saranaLog, $request, 'foto_keterangan_baru', (int) ($saranaLog->fotos()->max('urutan') ?? -1) + 1);

        $r = redirect()->route('sarana.logbook.show', $saranaLog)->with('success', 'Logbook sarana diperbarui.');
        return $gagal ? $r->with('warning', "$gagal foto gagal diunggah. Coba tambahkan lagi.") : $r;
    }

    public function destroy(SaranaLog $saranaLog)
    {
        $this->masuk();
        if (!$this->bisaUbah($saranaLog)) abort(403, 'Anda hanya dapat menghapus logbook milik sendiri.');
        foreach ($saranaLog->fotos as $f) ImageHelper::deleteWithThumbnail($f->path);
        $saranaLog->delete();
        return redirect()->route('sarana.logbook.index')->with('success', 'Logbook sarana dihapus.');
    }

    // ───────────── Cetak ─────────────
    public function cetak(SaranaLog $saranaLog)
    {
        $this->masuk();
        return $this->pdf(collect([$saranaLog->load(['user', 'items', 'fotos'])]), 'logbook-sarana-'.$saranaLog->tanggal->format('Ymd').'-shift'.$saranaLog->shift);
    }

    public function cetakHarian(Request $request)
    {
        $this->masuk();
        $tgl = $request->get('tanggal', now()->toDateString());
        $q = SaranaLog::with(['user', 'items', 'fotos'])->whereDate('tanggal', $tgl)->orderBy('shift')->orderBy('id');
        if ($request->query('tampil') !== 'semua') $q->where('user_id', auth()->id());
        return $this->pdf($q->get(), 'logbook-sarana-harian-'.$tgl);
    }

    public function cetakBulanan(Request $request)
    {
        $this->masuk();
        $bulan = preg_match('/^\d{4}-\d{2}$/', (string) $request->get('bulan')) ? $request->get('bulan') : now()->format('Y-m');
        [$th, $bl] = explode('-', $bulan);
        $q = SaranaLog::with(['user', 'items', 'fotos'])->whereYear('tanggal', $th)->whereMonth('tanggal', $bl)
            ->orderBy('tanggal')->orderBy('shift')->orderBy('id');
        if ($request->query('tampil') !== 'semua') $q->where('user_id', auth()->id());
        return $this->pdf($q->limit(120)->get(), 'logbook-sarana-bulanan-'.$bulan);
    }

    private function pdf($logs, string $nama)
    {
        if ($logs->isEmpty()) return back()->withErrors(['pdf' => 'Tidak ada logbook untuk dicetak.']);
        @ini_set('memory_limit', '512M');
        @set_time_limit(180);
        $settings = AppSetting::allKeyed();
        $pengelola = [];
        foreach ($logs as $l) {
            $acuan = AppSetting::tanggalAcuan($l->tanggal);
            $pengelola[$l->id] = AppSetting::getKoordinator('sarana', $acuan);
        }
        $html = view('sarana.logbook.pdf', compact('logs', 'settings', 'pengelola'))->render();

        $dirs = [storage_path('app/dompdf/fonts'), storage_path('app/dompdf/font-cache'), storage_path('app/dompdf/tmp')];
        foreach ($dirs as $d) { if (!is_dir($d)) @mkdir($d, 0775, true); }
        $opt = new \Dompdf\Options();
        $opt->setIsRemoteEnabled(false);
        $opt->setIsHtml5ParserEnabled(true);
        $opt->setDefaultFont('dejavu sans');
        $opt->setFontDir($dirs[0]); $opt->setFontCache($dirs[1]); $opt->setTempDir($dirs[2]);
        $opt->setChroot([base_path(), public_path(), storage_path()]);
        try {
            $d = new \Dompdf\Dompdf($opt);
            $d->setPaper('A4', 'portrait');
            $d->loadHtml($html, 'UTF-8');
            $d->render();
            return response($d->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$nama.'.pdf"',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Gagal cetak logbook sarana: '.$e->getMessage());
            return back()->withErrors(['pdf' => 'Gagal membuat PDF: '.$e->getMessage()]);
        }
    }
}
