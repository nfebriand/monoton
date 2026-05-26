<?php

namespace App\Http\Controllers;

use App\Models\OperasionalLog;
use App\Models\Pemancar;
use App\Models\JadwalShift;
use App\Services\VswrCalculator;
use Illuminate\Http\Request;

class OperasionalController extends Controller
{
    private function getPemancarsForUser()
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return Pemancar::where('is_active', true)->orderBy('nama_stasiun')->get();
        }
        if ($user->lokasi_dinas) {
            return Pemancar::where('is_active', true)
                ->where('lokasi', $user->lokasi_dinas)
                ->orderBy('nama_stasiun')->get();
        }
        return collect();
    }

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = OperasionalLog::with(['pemancar','user'])->orderByDesc('dicatat_pada');

        // Operator: batasi ke lokasi dinasnya
        if ($user->isOperator() && $user->lokasi_dinas) {
            $ids = Pemancar::where('lokasi', $user->lokasi_dinas)->pluck('id');
            $query->whereIn('pemancar_id', $ids);
        }

        // Filter lokasi (admin)
        if ($request->filled('lokasi')) {
            $ids = Pemancar::where('lokasi', $request->lokasi)->pluck('id');
            $query->whereIn('pemancar_id', $ids);
        }
        if ($request->filled('pemancar_id'))    $query->where('pemancar_id', $request->pemancar_id);
        if ($request->filled('tanggal_dari'))   $query->whereDate('dicatat_pada', '>=', $request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $query->whereDate('dicatat_pada', '<=', $request->tanggal_sampai);

        $logs        = $query->paginate(20)->withQueryString();
        $pemancars   = $this->getPemancarsForUser();
        $semuaLokasi = Pemancar::whereNotNull('lokasi')->distinct()->orderBy('lokasi')->pluck('lokasi');

        return view('operasional.index', compact('logs','pemancars','semuaLokasi'));
    }

    public function create()
    {
        $user             = auth()->user();
        $shiftAktifId     = session('shift_aktif_id');
        $shiftAktif       = $shiftAktifId ? JadwalShift::find($shiftAktifId) : null;
        $pemancars        = $this->getPemancarsForUser();
        $lokasiTidakDiset = $user->isOperator() && !$user->lokasi_dinas;

        $suhuTerakhir = $kelembabanTerakhir = null;
        if ($shiftAktifId) {
            $prev = OperasionalLog::where('jadwal_shift_id', $shiftAktifId)
                ->whereNotNull('suhu_ruangan')->latest('dicatat_pada')->first();
            if ($prev) {
                $suhuTerakhir       = $prev->suhu_ruangan;
                $kelembabanTerakhir = $prev->kelembaban;
            }
        }

        $sudahDicatat = $shiftAktifId
            ? OperasionalLog::where('jadwal_shift_id', $shiftAktifId)
                ->where('dicatat_pada', '>=', now()->subHours(3))
                ->pluck('pemancar_id')->toArray()
            : [];

        return view('operasional.create', compact(
            'pemancars','shiftAktif','sudahDicatat',
            'suhuTerakhir','kelembabanTerakhir','lokasiTidakDiset'
        ));
    }

    public function store(Request $request)
    {
        $user      = auth()->user();
        $pemancars = $this->getPemancarsForUser();

        if ($user->isOperator() && !$user->lokasi_dinas) {
            return back()->withErrors(['lokasi' => 'Lokasi dinas Anda belum diset.']);
        }

        $validated = $request->validate([
            'dicatat_pada'               => 'required|date',
            'suhu_ruangan'               => 'required|numeric|between:-50,100',
            'kelembaban'                 => 'nullable|numeric|between:0,100',
            'pemancar'                   => 'required|array|min:1',
            'pemancar.*.id'              => 'required|exists:pemancars,id',
            'pemancar.*.output_final_pa' => 'nullable|numeric|min:0',
            'pemancar.*.output_driver'   => 'nullable|numeric|min:0',
            'pemancar.*.output_exciter'  => 'nullable|numeric|min:0',
            'pemancar.*.reflect_final'   => 'nullable|numeric|min:0',
            'pemancar.*.reject_final'    => 'nullable|numeric|min:0',
            'pemancar.*.suhu_pemancar'   => 'nullable|numeric|between:-50,200',
            'pemancar.*.keterangan'      => 'nullable|string|max:500',
        ]);

        $allowedIds = $pemancars->pluck('id')->toArray();
        foreach ($validated['pemancar'] as $data) {
            if ($user->isOperator() && !in_array($data['id'], $allowedIds)) {
                return back()->withErrors(['pemancar' => 'Pemancar di luar lokasi dinas Anda.']);
            }
            if (!empty($data['output_final_pa'])) {
                $p = $pemancars->firstWhere('id', $data['id']);
                if ($p && $data['output_final_pa'] > $p->kapasitas_output_final) {
                    return back()->withErrors([
                        'output' => "Output Final PA untuk {$p->nama_stasiun} tidak boleh melebihi kapasitas ({$p->kapasitas_output_final} W)."
                    ])->withInput();
                }
            }
        }

        $shiftAktifId = session('shift_aktif_id');
        foreach ($validated['pemancar'] as $data) {
            $vswr = VswrCalculator::calculateAll($data);
            OperasionalLog::create([
                'pemancar_id'       => $data['id'],
                'user_id'           => $user->id,
                'jadwal_shift_id'   => $shiftAktifId,
                'dicatat_pada'      => $validated['dicatat_pada'],
                'output_final_pa'   => $data['output_final_pa']  ?? null,
                'output_driver'     => $data['output_driver']     ?? null,
                'output_exciter'    => $data['output_exciter']    ?? null,
                'reflect_final'     => $data['reflect_final']     ?? null,
                'reject_final'      => $data['reject_final']      ?? null,
                'vswr_final'        => $vswr['vswr_final'],
                'return_loss_final' => $vswr['return_loss_final'],
                'suhu_pemancar'     => $data['suhu_pemancar']     ?? null,
                'suhu_ruangan'      => $validated['suhu_ruangan'],
                'kelembaban'        => $validated['kelembaban']   ?? null,
                'keterangan'        => $data['keterangan']        ?? null,
            ]);
        }

        return redirect()->route('operasional.index')
            ->with('success', count($validated['pemancar']).' log operasional berhasil dicatat.');
    }

    public function show(OperasionalLog $operasional)
    {
        $this->checkAkses($operasional);
        $operasional->load(['pemancar','user','jadwalShift']);
        $vswrStatus = $operasional->vswr_final
            ? VswrCalculator::getStatus((float)$operasional->vswr_final) : null;
        return view('operasional.show', compact('operasional','vswrStatus'));
    }

    public function edit(OperasionalLog $operasional)
    {
        $this->checkAkses($operasional);
        if (!auth()->user()->isAdmin() && auth()->id() !== $operasional->user_id) abort(403);
        $operasional->load(['pemancar','user']);
        $pemancars = $this->getPemancarsForUser();
        return view('operasional.edit', compact('operasional','pemancars'));
    }

    public function update(Request $request, OperasionalLog $operasional)
    {
        $this->checkAkses($operasional);
        if (!auth()->user()->isAdmin() && auth()->id() !== $operasional->user_id) abort(403);

        $validated = $request->validate([
            'dicatat_pada'    => 'required|date',
            'output_final_pa' => 'nullable|numeric|min:0',
            'output_driver'   => 'nullable|numeric|min:0',
            'output_exciter'  => 'nullable|numeric|min:0',
            'reflect_final'   => 'nullable|numeric|min:0',
            'reject_final'    => 'nullable|numeric|min:0',
            'suhu_pemancar'   => 'nullable|numeric|between:-50,200',
            'suhu_ruangan'    => 'nullable|numeric|between:-50,100',
            'kelembaban'      => 'nullable|numeric|between:0,100',
            'keterangan'      => 'nullable|string|max:1000',
        ]);

        if (!empty($validated['output_final_pa'])) {
            $p = $operasional->pemancar;
            if ($validated['output_final_pa'] > $p->kapasitas_output_final) {
                return back()->withErrors([
                    'output_final_pa' => "Output Final PA tidak boleh melebihi kapasitas ({$p->kapasitas_output_final} W)."
                ])->withInput();
            }
        }

        $vswr = VswrCalculator::calculateAll($validated);
        $operasional->update(array_merge($validated, [
            'vswr_final'        => $vswr['vswr_final'],
            'return_loss_final' => $vswr['return_loss_final'],
        ]));

        return redirect()->route('operasional.show', $operasional)
            ->with('success', 'Log operasional berhasil diperbarui.');
    }

    public function hitungVswr(Request $request)
    {
        $request->validate(['forward'=>'required|numeric|min:0','reflected'=>'required|numeric|min:0']);
        $result = VswrCalculator::calculate((float)$request->forward,(float)$request->reflected);
        $status = $result['vswr'] ? VswrCalculator::getStatus($result['vswr']) : null;
        return response()->json(array_merge($result,['status'=>$status]));
    }

    public function destroy(OperasionalLog $operasional)
    {
        if (!auth()->user()->isAdmin()) abort(403);
        $operasional->delete();
        return back()->with('success','Log berhasil dihapus.');
    }

    private function checkAkses(OperasionalLog $operasional): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if ($user->lokasi_dinas) {
            $p = Pemancar::find($operasional->pemancar_id);
            if ($p && $p->lokasi !== $user->lokasi_dinas) abort(403,'Akses ditolak.');
        }
    }
}
