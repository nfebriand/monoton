<?php
namespace App\Http\Controllers;

use App\Models\JadwalShift;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JadwalController extends Controller
{
    private function checkAdmin()
    {
        if (!auth()->user()->isAdmin()) abort(403, 'Hanya Administrator.');
    }

    public function index(Request $request)
    {
        $this->checkAdmin();
        $bulan = (int)$request->get('bulan', now()->month);
        $tahun = (int)$request->get('tahun', now()->year);

        $jadwals = JadwalShift::with('user')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->orderBy('tanggal')->orderBy('shift')
            ->get()
            ->groupBy(fn($j) => Carbon::parse($j->tanggal)->toDateString());

        $operators    = User::where('role','operator')->where('is_active',true)->orderBy('name')->get();
        $awalBulan    = Carbon::create($tahun, $bulan, 1);
        $akhirBulan   = $awalBulan->copy()->endOfMonth();
        $hariKalender = collect();
        for ($d = $awalBulan->copy(); $d <= $akhirBulan; $d->addDay()) {
            $hariKalender->push($d->copy());
        }

        return view('jadwal.index', compact(
            'jadwals','operators','bulan','tahun','hariKalender'
        ));
    }

    public function store(Request $request)
    {
        $this->checkAdmin();
        $v = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'shift'   => 'required|integer|min:1|max:3',
            'catatan' => 'nullable|string|max:500',
        ]);

        $op    = User::findOrFail($v['user_id']);
        $skema = JadwalShift::getSkemaForLokasi($op->lokasi_dinas ?? '');
        $sd    = JadwalShift::getShiftData($skema, (int)$v['shift']);
        if (!$sd) return back()->withErrors(['shift' => 'Shift tidak valid untuk lokasi ini.']);

        if (JadwalShift::where('user_id',$v['user_id'])->where('tanggal',$v['tanggal'])->exists()) {
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
        $this->checkAdmin();
        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'bulan_target'  => 'required|integer|between:1,12',
            'tahun_target'  => 'required|integer|min:2024',
            'jadwal_harian' => 'required|array',
        ]);

        $userId = $request->user_id;
        $bulan  = $request->bulan_target;
        $tahun  = $request->tahun_target;
        $op     = User::findOrFail($userId);
        $skema  = JadwalShift::getSkemaForLokasi($op->lokasi_dinas ?? '');

        $dibuat = $lewat = $hapus = 0;

        foreach ($request->jadwal_harian as $tgl => $shift) {
            $date = Carbon::createFromFormat('Y-m-d', $tgl);
            if ($date->month != $bulan || $date->year != $tahun) continue;

            $existing = JadwalShift::where('user_id',$userId)->where('tanggal',$tgl)->first();

            if (!$shift || $shift === 'skip') {
                if ($existing) { $existing->delete(); $hapus++; }
                continue;
            }

            $sd = JadwalShift::getShiftData($skema, (int)$shift);
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
        $this->checkAdmin();
        $jadwal->delete();
        return back()->with('success', 'Jadwal dihapus.');
    }
}
