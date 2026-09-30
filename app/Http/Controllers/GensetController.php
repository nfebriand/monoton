<?php
namespace App\Http\Controllers;

use App\Models\GensetLog;
use App\Models\GensetUnit;
use App\Models\Lokasi;
use Illuminate\Http\Request;

class GensetController extends Controller
{
    /** Pastikan hanya divisi Sarana / Admin yang akses */
    private function checkAccess(): void
    {
        $user = auth()->user();
        // Super admin, divisi Sarana, ATAU divisi Transmisi boleh akses genset
        $boleh = $user->isAdmin()
            || $user->isDivisi('sarana')
            || $user->isDivisi('transmisi');

        if (!$boleh) {
            abort(403, 'Menu ini hanya untuk Divisi Sarana & Prasarana atau Transmisi.');
        }
    }


    /**
     * Cek akses manajemen unit genset.
     * Hanya Super Admin dan Admin Divisi Sarana yang diperbolehkan
     * (bukan Admin Divisi divisi lain — genset adalah aset Sarana & Prasarana).
     */
    private function checkUnitAccess(): void
    {
        $user = auth()->user();

        $boleh = $user->isAdmin()
            || ($user->isAdminDivisi() && $user->isDivisi('sarana'));

        if (!$boleh) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola unit genset. Menu ini khusus Super Admin dan Admin Divisi Sarana & Prasarana.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAccess();

        $query = GensetLog::with(['gensetUnit','user'])
            ->orderByDesc('tanggal')->orderByDesc('jam_mulai');

        if ($request->filled('genset_unit_id')) $query->where('genset_unit_id',$request->genset_unit_id);
        if ($request->filled('tanggal_dari'))   $query->where('tanggal','>=',$request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $query->where('tanggal','<=',$request->tanggal_sampai);
        if ($request->filled('kondisi'))        $query->where('kondisi',$request->kondisi);

        $logs  = $query->paginate(15)->withQueryString();
        $units = GensetUnit::where('is_active',true)->orderBy('nama_unit')->get();

        // Ringkasan bulan ini
        $bulanIni = GensetLog::whereMonth('tanggal',now()->month)->whereYear('tanggal',now()->year);
        $ringkasan = [
            'total_operasi'   => (clone $bulanIni)->count(),
            'total_bbm'       => (clone $bulanIni)->get()->sum('pemakaian_bbm'),
            'total_jam'       => (clone $bulanIni)->get()->sum(fn($l)=>$l->jam_operasi_hm ?? $l->durasi_jam ?? 0),
            'gangguan'        => (clone $bulanIni)->where('kondisi','gangguan')->count(),
        ];

        return view('genset.index', compact('logs','units','ringkasan'));
    }

    public function create()
    {
        $this->checkAccess();
        $units = GensetUnit::where('is_active',true)->orderBy('nama_unit')->get();

        if ($units->isEmpty()) {
            return redirect()->route('genset.index')
                ->with('error','Belum ada unit genset terdaftar. Hubungi admin untuk menambahkan unit genset terlebih dahulu.');
        }

        // Ambil HM & BBM akhir terakhir per unit, untuk auto-fill "awal" log baru
        $lastLogs = GensetLog::whereIn('genset_unit_id',$units->pluck('id'))
            ->orderByDesc('tanggal')->orderByDesc('jam_mulai')
            ->get()->groupBy('genset_unit_id')->map(fn($g)=>$g->first());

        return view('genset.create', compact('units','lastLogs'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();

        $v = $request->validate([
            'genset_unit_id' => 'required|exists:genset_units,id',
            'tanggal'        => 'required|date',
            'jam_mulai'      => 'required|date_format:H:i',
            'jam_selesai'    => 'nullable|date_format:H:i',
            'alasan'         => 'required|in:pln_mati,maintenance,test_rutin,lainnya',
            'hm_awal'        => 'nullable|numeric|min:0',
            'hm_akhir'       => 'nullable|numeric|min:0|gte:hm_awal',
            'bbm_awal'       => 'nullable|numeric|min:0',
            'bbm_isi'        => 'nullable|numeric|min:0',
            'bbm_akhir'      => 'nullable|numeric|min:0',
            'kondisi'        => 'required|in:normal,gangguan',
            'keterangan'     => 'nullable|string',
        ]);

        $v['user_id'] = auth()->id();
        GensetLog::create($v);

        return redirect()->route('genset.index')->with('success','Log operasional genset berhasil disimpan.');
    }

    public function show(GensetLog $genset)
    {
        $this->checkAccess();
        $genset->load(['gensetUnit','user']);
        return view('genset.show', compact('genset'));
    }

    public function edit(GensetLog $genset)
    {
        $this->checkAccess();
        if (!auth()->user()->isAdmin() && auth()->id() !== $genset->user_id) abort(403);
        $units = GensetUnit::where('is_active',true)->orderBy('nama_unit')->get();
        return view('genset.edit', compact('genset','units'));
    }

    public function update(Request $request, GensetLog $genset)
    {
        $this->checkAccess();
        if (!auth()->user()->isAdmin() && auth()->id() !== $genset->user_id) abort(403);

        $v = $request->validate([
            'genset_unit_id' => 'required|exists:genset_units,id',
            'tanggal'        => 'required|date',
            'jam_mulai'      => 'required|date_format:H:i',
            'jam_selesai'    => 'nullable|date_format:H:i',
            'alasan'         => 'required|in:pln_mati,maintenance,test_rutin,lainnya',
            'hm_awal'        => 'nullable|numeric|min:0',
            'hm_akhir'       => 'nullable|numeric|min:0|gte:hm_awal',
            'bbm_awal'       => 'nullable|numeric|min:0',
            'bbm_isi'        => 'nullable|numeric|min:0',
            'bbm_akhir'      => 'nullable|numeric|min:0',
            'kondisi'        => 'required|in:normal,gangguan',
            'keterangan'     => 'nullable|string',
        ]);

        $genset->update($v);
        return redirect()->route('genset.show',$genset)->with('success','Log genset berhasil diperbarui.');
    }

    public function destroy(GensetLog $genset)
    {
        $this->checkAccess();
        $this->checkUnitAccess();
        $genset->delete();
        return redirect()->route('genset.index')->with('success','Log genset berhasil dihapus.');
    }

    // ════════════════════════════════════════════
    //  MANAJEMEN UNIT GENSET (Admin Only)
    // ════════════════════════════════════════════

    public function units()
    {
        $this->checkUnitAccess();
        $units   = GensetUnit::orderBy('lokasi')->orderBy('nama_unit')->get();
        $lokasis = Lokasi::orderBy('nama')->get();
        return view('genset.units', compact('units', 'lokasis'));
    }

    public function storeUnit(Request $request)
    {
        $this->checkUnitAccess();
        $v = $request->validate([
            'nama_unit'             => 'required|string|max:100',
            'lokasi'                => 'nullable|string|max:100',
            'merk'                  => 'nullable|string|max:100',
            'tipe'                  => 'nullable|string|max:100',
            'kapasitas_kva'         => 'nullable|integer|min:0',
            'kapasitas_tangki_liter'=> 'nullable|numeric|min:0',
            'tahun_pembuatan'       => 'nullable|integer',
            'is_active'             => 'nullable|boolean',
        ]);
        $v['is_active'] = $request->boolean('is_active', true);
        GensetUnit::create($v);
        return redirect()->route('genset.units')->with('success','Unit genset berhasil ditambahkan.');
    }

    public function updateUnit(Request $request, GensetUnit $unit)
    {
        $this->checkUnitAccess();
        $v = $request->validate([
            'nama_unit'             => 'required|string|max:100',
            'lokasi'                => 'nullable|string|max:100',
            'merk'                  => 'nullable|string|max:100',
            'tipe'                  => 'nullable|string|max:100',
            'kapasitas_kva'         => 'nullable|integer|min:0',
            'kapasitas_tangki_liter'=> 'nullable|numeric|min:0',
            'tahun_pembuatan'       => 'nullable|integer',
            'is_active'             => 'nullable|boolean',
        ]);
        $v['is_active'] = $request->boolean('is_active', true);
        $unit->update($v);
        return redirect()->route('genset.units')->with('success','Unit genset berhasil diperbarui.');
    }

    public function destroyUnit(GensetUnit $unit)
    {
        $this->checkUnitAccess();
        if ($unit->logs()->exists()) {
            return back()->withErrors(['unit'=>'Unit ini memiliki riwayat log dan tidak bisa dihapus. Nonaktifkan saja.']);
        }
        $unit->delete();
        return redirect()->route('genset.units')->with('success','Unit genset berhasil dihapus.');
    }
}
