<?php
namespace App\Http\Controllers;

use App\Models\JadwalShift;
use App\Models\JadwalCuti;
use App\Models\SkemaShift;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\AppSetting;
use App\Support\JadwalExcel;

class JadwalController extends Controller
{
    /**
     * Cek akses: Super Admin ATAU Admin Divisi.
     * Operator tidak boleh masuk sama sekali.
     */
    private function checkAccess(): void
    {
        if (!auth()->user()->hasAdminAccess()) {
            abort(403, 'Hanya Administrator atau Admin Divisi.');
        }
    }

    /**
     * Ambil query personil yang boleh dijadwalkan oleh user yang login.
     * Personil yang bisa dijadwalkan: Operator DAN Admin Divisi
     * (Admin Divisi juga bertugas langsung di lapangan/dinas).
     * - Super Admin   → semua personil semua divisi
     * - Admin Divisi  → hanya personil divisinya sendiri (termasuk dirinya/admin divisi lain di divisi yang sama)
     */
    private function operatorQuery()
    {
        $user  = auth()->user();
        $query = User::whereIn('role', [User::ROLE_OPERATOR, User::ROLE_ADMIN_DIVISI])
            ->where('is_active', true)
            ->orderBy('divisi')
            ->orderBy('name');

        if ($user->isAdminDivisi()) {
            $query->where('divisi', $user->divisi);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $this->checkAccess();

        $user  = auth()->user();
        $bulan = (int)$request->get('bulan', now()->month);
        $tahun = (int)$request->get('tahun', now()->year);

        $jadwalQuery = JadwalShift::with('user')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->orderBy('tanggal')
            ->orderBy('shift');

        // Admin Divisi hanya melihat jadwal operator di divisinya
        if ($user->isAdminDivisi()) {
            $jadwalQuery->whereHas('user', fn($q) =>
                $q->where('divisi', $user->divisi)
            );
        }

        $jadwals = $jadwalQuery->get()
            ->groupBy(fn($j) => Carbon::parse($j->tanggal)->toDateString());

        $operators  = $this->operatorQuery()->get();
        $awalBulan  = Carbon::create($tahun, $bulan, 1);
        $akhirBulan = $awalBulan->copy()->endOfMonth();

        $hariKalender = collect();
        for ($d = $awalBulan->copy(); $d <= $akhirBulan; $d->addDay()) {
            $hariKalender->push($d->copy());
        }

        // Baca skema dari DB (fallback ke konstanta jika DB kosong)
        $skemaShift = SkemaShift::getAllForJs();

        // Cuti / izin / sakit bulan ini (admin divisi hanya divisinya)
        $adaTabelCuti = \Illuminate\Support\Facades\Schema::hasTable('jadwal_cuti'); // aman sebelum migrate
        $cutiQuery = JadwalCuti::with('user')
            ->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)
            ->orderBy('tanggal');
        if ($user->isAdminDivisi()) {
            $cutiQuery->whereHas('user', fn($q) => $q->where('divisi', $user->divisi));
        }
        $cutiList  = $adaTabelCuti ? $cutiQuery->get() : collect();
        $cutiByTgl = $cutiList->groupBy(fn($c) => Carbon::parse($c->tanggal)->toDateString());

        return view('jadwal.index', compact(
            'jadwals', 'operators', 'bulan', 'tahun', 'hariKalender', 'skemaShift', 'cutiList', 'cutiByTgl'
        ));
    }

    /**
     * Export jadwal shift bulanan ke Excel (.xlsx), satu sheet per lokasi dinas.
     * Admin Divisi: otomatis divisinya sendiri. Super Admin: pilih ?divisi=
     */
    public function exportExcel(Request $request)
    {
        $this->checkAccess();

        $user  = auth()->user();
        $bulan = max(1, min(12, (int)$request->get('bulan', now()->month)));
        $tahun = (int)$request->get('tahun', now()->year);

        $divisi = $user->isAdminDivisi() ? $user->divisi : $request->get('divisi', 'transmisi');
        if (!in_array($divisi, ['transmisi','studio','sarana'], true)) $divisi = 'transmisi';

        $jadwals = JadwalShift::with('user')
            ->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)
            ->whereHas('user', fn($q) => $q->where('divisi', $divisi))
            ->orderBy('tanggal')->get();

        if ($request->filled('lokasi')) {
            $jadwals = $jadwals->filter(fn($j) => ($j->user->lokasi_dinas ?: 'Umum') === $request->lokasi);
        }

        if ($jadwals->isEmpty()) {
            return back()->withErrors(['export' => 'Belum ada jadwal untuk diexport pada bulan/divisi ini.']);
        }

        // Susun per lokasi: [lokasi => [Y-m-d => [shift => [nama,...]]]]
        $perLokasi = [];
        $aliasLokasi = []; // [lokasi => [nama lengkap => alias]]
        foreach ($jadwals as $j) {
            $lok = $j->user->lokasi_dinas ?: 'Umum';
            if (trim((string) $j->user->alias) !== '') $aliasLokasi[$lok][$j->user->name] = trim($j->user->alias);
            $perLokasi[$lok][Carbon::parse($j->tanggal)->toDateString()][(int)$j->shift][] = $j->user->name;
        }
        // Cuti/izin/sakit bulan ini per lokasi: [lokasi => [Y-m-d => [[nama, jenis_label, keterangan], ...]]]
        $cutiLokasi = [];
        $cutis = !\Illuminate\Support\Facades\Schema::hasTable('jadwal_cuti') ? collect() : JadwalCuti::with('user')
            ->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)
            ->whereHas('user', fn($q) => $q->where('divisi', $divisi))
            ->orderBy('tanggal')->get();
        foreach ($cutis as $c) {
            $lok = $c->user->lokasi_dinas ?: 'Umum';
            if ($request->filled('lokasi') && $request->lokasi !== $lok) continue;
            if (trim((string) $c->user->alias) !== '') $aliasLokasi[$lok][$c->user->name] = trim($c->user->alias);
            $cutiLokasi[$lok][Carbon::parse($c->tanggal)->toDateString()][] = [$c->user->name, $c->jenis_label, (string) $c->keterangan];
        }
        ksort($perLokasi);
        $sheets = [];
        foreach ($perLokasi as $lok => $data) {
            foreach ($data as $tgl => $perShift) foreach ($perShift as $no => $list) {
                sort($list, SORT_NATURAL | SORT_FLAG_CASE);
                $data[$tgl][$no] = $list;
            }
            $sheets[] = ['lokasi' => $lok, 'jadwal' => $data, 'alias' => $aliasLokasi[$lok] ?? [], 'cuti' => $cutiLokasi[$lok] ?? []];
        }

        $skema  = JadwalShift::getSkemaForDivisi($divisi);
        $shifts = SkemaShift::getShiftsForKode($skema);

        $kor  = AppSetting::getKoordinator($divisi);
        $kabid = AppSetting::getKabid();
        $kepala = AppSetting::getKepalaStasiun();

        $x = JadwalExcel::build([
            'divisi_label' => \App\Models\User::DIVISI_LABEL[$divisi] ?? ucfirst($divisi),
            'bulan'  => $bulan,
            'tahun'  => $tahun,
            'shifts' => $shifts,
            'sheets' => $sheets,
            'logo'        => $this->ttdGambar(AppSetting::logoInstansiPath() ?: null, false),
            'koordinator' => ['jabatan'=>$kor['jabatan'],'nama'=>$kor['nama'],'nip'=>$kor['nip'],'ttd'=>$this->ttdGambar($kor['ttd_path'] ?? null)],
            'kabid'       => ['jabatan'=>$kabid['jabatan'],'nama'=>$kabid['nama'],'nip'=>$kabid['nip'],'ttd'=>$this->ttdGambar($kabid['ttd_path'] ?? null)],
            'kepala'      => ['jabatan'=>$kepala['jabatan'],'nama'=>$kepala['nama'],'nip'=>$kepala['nip'],'ttd'=>$this->ttdGambar($kepala['ttd_path'] ?? null)],
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'jadwal_').'.xlsx';
        $x->save($tmp);

        $namaFile = 'Jadwal-'.ucfirst($divisi).'-'.sprintf('%04d-%02d', $tahun, $bulan).'.xlsx';
        $resp = response()->download($tmp, $namaFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
        // Jangan pernah di-cache (browser/proxy/CDN): hasil export harus selalu dibuat ulang
        $resp->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $resp->headers->set('Pragma', 'no-cache');
        $resp->headers->set('Expires', '0');
        $resp->headers->remove('Last-Modified');
        $resp->headers->remove('ETag');
        return $resp;
    }

    /** Baca file TTD dari storage -> ['bytes'=>..., 'ext'=>'png|jpeg'] atau null */
    private function ttdGambar(?string $path, bool $trim = true): ?array
    {
        if (!$path) return null;
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        if (!$disk->exists($path)) return null;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $bytes = $disk->get($path);
        // TTD: buang margin kosong/putih supaya proporsi & posisi di Excel pas
        if ($trim && ($t = \App\Support\ImageTrim::trim($bytes))) return $t;
        return ['bytes' => $bytes, 'ext' => $ext === 'png' ? 'png' : 'jpeg'];
    }

    public function store(Request $request)
    {
        $this->checkAccess();

        $v = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'shift'   => 'required|integer|min:1|max:5',
            'catatan' => 'nullable|string|max:500',
        ]);

        // Pastikan Admin Divisi tidak bisa buat jadwal untuk operator lain divisi
        $op = User::findOrFail($v['user_id']);
        if (!auth()->user()->canManageDivisi($op->divisi)) {
            abort(403, 'Anda hanya dapat mengatur jadwal untuk operator divisi Anda sendiri.');
        }

        $skema     = JadwalShift::getSkemaForDivisi($op->divisi ?? 'transmisi');
        $skemaData = SkemaShift::getShiftsForKode($skema);
        $sd = $skemaData[(int)$v['shift']] ?? JadwalShift::getShiftData($skema, (int)$v['shift']);

        if (!$sd) {
            return back()->withErrors(['shift' => 'Shift tidak valid untuk lokasi ini.']);
        }

        if (JadwalShift::where('user_id', $v['user_id'])->where('tanggal', $v['tanggal'])->exists()) {
            return back()->withErrors(['shift' => 'Operator sudah memiliki jadwal pada tanggal tersebut.']);
        }

        JadwalShift::create([
            'user_id'    => $v['user_id'],
            'tanggal'    => $v['tanggal'],
            'shift'      => $v['shift'],
            'skema'      => $skema,
            'jam_mulai'  => $sd['mulai'],
            'jam_selesai'=> $sd['selesai'],
            'catatan'    => $v['catatan'] ?? null,
        ]);

        return back()->with('success', 'Jadwal berhasil dibuat.');
    }

    public function storeBulanan(Request $request)
    {
        $this->checkAccess();

        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'bulan_target'  => 'required|integer|between:1,12',
            'tahun_target'  => 'required|integer|min:2024',
            'jadwal_harian' => 'required|array',
        ]);

        // Admin Divisi tidak bisa buat jadwal untuk operator lain divisi
        $op = User::findOrFail($request->user_id);
        if (!auth()->user()->canManageDivisi($op->divisi)) {
            abort(403, 'Anda hanya dapat mengatur jadwal untuk operator divisi Anda sendiri.');
        }

        $userId = $request->user_id;
        $bulan  = $request->bulan_target;
        $tahun  = $request->tahun_target;
        $skema     = JadwalShift::getSkemaForDivisi($op->divisi ?? 'transmisi');
        $skemaData = SkemaShift::getShiftsForKode($skema);

        $dibuat = $lewat = $hapus = 0;

        foreach ($request->jadwal_harian as $tgl => $shift) {
            $date = Carbon::createFromFormat('Y-m-d', $tgl);
            if ($date->month != $bulan || $date->year != $tahun) continue;

            $existing = JadwalShift::where('user_id', $userId)->where('tanggal', $tgl)->first();

            if (!$shift || $shift === 'skip') {
                if ($existing) { $existing->delete(); $hapus++; }
                continue;
            }

            $sd = $skemaData[(int)$shift] ?? JadwalShift::getShiftData($skema, (int)$shift);
            if (!$sd) continue;

            if ($existing) {
                $existing->update([
                    'shift'      => $shift,
                    'skema'      => $skema,
                    'jam_mulai'  => $sd['mulai'],
                    'jam_selesai'=> $sd['selesai'],
                ]);
                $lewat++;
            } else {
                JadwalShift::create([
                    'user_id'    => $userId,
                    'tanggal'    => $tgl,
                    'shift'      => $shift,
                    'skema'      => $skema,
                    'jam_mulai'  => $sd['mulai'],
                    'jam_selesai'=> $sd['selesai'],
                ]);
                $dibuat++;
            }
        }

        return back()->with('success',
            "Jadwal disimpan: {$dibuat} baru, {$lewat} diperbarui, {$hapus} dihapus.");
    }

    /**
     * Catat cuti/izin/sakit untuk satu operator pada rentang tanggal (dari s.d sampai).
     * Menimpa catatan cuti lama di tanggal yang sama. Jadwal shift pada tanggal itu dihapus
     * supaya operator tidak tercatat dinas dan cuti sekaligus.
     */
    public function storeCuti(Request $request)
    {
        $this->checkAccess();

        $v = $request->validate([
            'user_id'        => 'required|exists:users,id',
            'tanggal_mulai'  => 'required|date',
            'tanggal_selesai'=> 'nullable|date|after_or_equal:tanggal_mulai',
            'jenis'          => 'required|in:'.implode(',', array_keys(JadwalCuti::JENIS)),
            'keterangan'     => 'nullable|string|max:200',
        ]);

        $op = User::findOrFail($v['user_id']);
        if (!auth()->user()->canManageDivisi($op->divisi)) {
            abort(403, 'Anda hanya dapat mengatur cuti operator divisi Anda sendiri.');
        }

        $mulai   = Carbon::parse($v['tanggal_mulai'])->startOfDay();
        $selesai = Carbon::parse($v['tanggal_selesai'] ?? $v['tanggal_mulai'])->startOfDay();
        if ($mulai->diffInDays($selesai) > 62) {
            return back()->withErrors(['tanggal_selesai' => 'Rentang cuti maksimal 63 hari per input.']);
        }

        $n = 0; $shiftDihapus = 0;
        for ($d = $mulai->copy(); $d <= $selesai; $d->addDay()) {
            JadwalCuti::updateOrCreate(
                ['user_id' => $op->id, 'tanggal' => $d->toDateString()],
                ['jenis' => $v['jenis'], 'keterangan' => $v['keterangan'] ?? null]
            );
            $shiftDihapus += JadwalShift::where('user_id', $op->id)->whereDate('tanggal', $d->toDateString())->delete();
            $n++;
        }

        $msg = "{$n} hari ".strtolower(JadwalCuti::JENIS[$v['jenis']])." dicatat untuk {$op->name}.";
        if ($shiftDihapus) $msg .= " {$shiftDihapus} jadwal shift pada tanggal tersebut dihapus.";
        return back()->with('success', $msg);
    }

    public function destroyCuti(JadwalCuti $cuti)
    {
        $this->checkAccess();
        if (!auth()->user()->canManageDivisi($cuti->user->divisi)) {
            abort(403, 'Anda hanya dapat menghapus cuti operator divisi Anda sendiri.');
        }
        $cuti->delete();
        return back()->with('success', 'Catatan cuti dihapus.');
    }

    public function destroy(JadwalShift $jadwal)
    {
        $this->checkAccess();

        // Admin Divisi tidak bisa hapus jadwal operator lain divisi
        if (!auth()->user()->canManageDivisi($jadwal->user->divisi)) {
            abort(403, 'Anda hanya dapat menghapus jadwal untuk operator divisi Anda sendiri.');
        }

        $jadwal->delete();
        return back()->with('success', 'Jadwal dihapus.');
    }
}