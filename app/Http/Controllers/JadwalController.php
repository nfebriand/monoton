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
        if (!auth()->user()->isAdmin()) abort(403,'Hanya Administrator.');
    }

    public function index(Request $request)
    {
        $this->checkAdmin();
        $bulan = $request->get('bulan', now()->month);
        $tahun = $request->get('tahun', now()->year);

        $jadwals = JadwalShift::with('user')
            ->whereYear('tanggal',$tahun)->whereMonth('tanggal',$bulan)
            ->orderBy('tanggal')->orderBy('shift')->get()
            ->groupBy(fn($j)=>Carbon::parse($j->tanggal)->toDateString());

        $operators    = User::where('role','operator')->where('is_active',true)->orderBy('name')->get();
        $awalBulan    = Carbon::create($tahun,$bulan,1);
        $akhirBulan   = $awalBulan->copy()->endOfMonth();
        $hariKalender = collect();
        for ($d=$awalBulan->copy(); $d<=$akhirBulan; $d->addDay()) {
            $hariKalender->push($d->copy());
        }
        $shiftDefs = JadwalShift::SHIFTS;

        return view('jadwal.index', compact('jadwals','operators','bulan','tahun','hariKalender','shiftDefs'));
    }

    // Simpan 1 jadwal
    public function store(Request $request)
    {
        $this->checkAdmin();
        $v = $request->validate([
            'user_id'=>'required|exists:users,id',
            'tanggal'=>'required|date',
            'shift'  =>'required|in:1,2,3',
            'catatan'=>'nullable|string|max:500',
        ]);
        // 1 operator 1 shift per hari
        if (JadwalShift::where('user_id',$v['user_id'])->where('tanggal',$v['tanggal'])->exists()) {
            return back()->withErrors(['shift'=>'Operator sudah memiliki jadwal pada tanggal tersebut.']);
        }
        $s = JadwalShift::SHIFTS[$v['shift']];
        JadwalShift::create(array_merge($v,['jam_mulai'=>$s['mulai'],'jam_selesai'=>$s['selesai']]));
        return back()->with('success','Jadwal berhasil dibuat.');
    }

    // Simpan jadwal bulanan (1 operator, 1 bulan penuh, shift per tanggal)
    public function storeBulanan(Request $request)
    {
        $this->checkAdmin();
        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'bulan_target'  => 'required|integer|between:1,12',
            'tahun_target'  => 'required|integer|min:2024',
            'jadwal_harian' => 'required|array',
            'jadwal_harian.*' => 'nullable|in:1,2,3,skip',
        ]);

        $userId     = $request->user_id;
        $bulan      = $request->bulan_target;
        $tahun      = $request->tahun_target;
        $jadwalHarian = $request->jadwal_harian;

        $dibuat = 0; $lewat = 0; $hapus = 0;

        foreach ($jadwalHarian as $tgl => $shift) {
            // Validasi tanggal milik bulan yang dipilih
            $date = Carbon::createFromFormat('Y-m-d', $tgl);
            if ($date->month != $bulan || $date->year != $tahun) continue;

            $existing = JadwalShift::where('user_id',$userId)->where('tanggal',$tgl)->first();

            if ($shift === 'skip' || $shift === null || $shift === '') {
                // Jika skip, hapus jika ada
                if ($existing) { $existing->delete(); $hapus++; }
                continue;
            }

            $s = JadwalShift::SHIFTS[$shift];
            if ($existing) {
                // Update shift
                $existing->update(['shift'=>$shift,'jam_mulai'=>$s['mulai'],'jam_selesai'=>$s['selesai']]);
                $lewat++;
            } else {
                JadwalShift::create([
                    'user_id'    =>$userId,
                    'tanggal'    =>$tgl,
                    'shift'      =>$shift,
                    'jam_mulai'  =>$s['mulai'],
                    'jam_selesai'=>$s['selesai'],
                ]);
                $dibuat++;
            }
        }

        return back()->with('success',"Jadwal disimpan: {$dibuat} baru, {$lewat} diperbarui, {$hapus} dihapus.");
    }

    public function destroy(JadwalShift $jadwal)
    {
        $this->checkAdmin();
        $jadwal->delete();
        return back()->with('success','Jadwal dihapus.');
    }
}
