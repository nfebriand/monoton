<?php
namespace App\Http\Controllers;

use App\Models\JadwalShift;
use App\Models\SkemaShift;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

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

        return view('jadwal.index', compact(
            'jadwals', 'operators', 'bulan', 'tahun', 'hariKalender', 'skemaShift'
        ));
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
