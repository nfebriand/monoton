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
            'pemancar.*.status'          => 'nullable|in:on,off',
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
            $statusOff = ($data['status'] ?? 'on') === 'off';
            if (!$statusOff && !empty($data['output_final_pa'])) {
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
            $statusOff = ($data['status'] ?? 'on') === 'off';
            // Saat OFF, nilai teknis tidak relevan (pemancar tidak mengudara)
            // -- tetap boleh diisi kalau operator mau catat, tapi tidak wajib.
            $vswr = $statusOff ? ['vswr_final' => null, 'return_loss_final' => null] : VswrCalculator::calculateAll($data);
            OperasionalLog::create([
                'pemancar_id'       => $data['id'],
                'status'            => $data['status'] ?? 'on',
                'user_id'           => $user->id,
                'jadwal_shift_id'   => $shiftAktifId,
                'dicatat_pada'      => $validated['dicatat_pada'],
                'output_final_pa'   => $statusOff ? null : ($data['output_final_pa']  ?? null),
                'output_driver'     => $statusOff ? null : ($data['output_driver']     ?? null),
                'output_exciter'    => $statusOff ? null : ($data['output_exciter']    ?? null),
                'reflect_final'     => $statusOff ? null : ($data['reflect_final']     ?? null),
                'reject_final'      => $statusOff ? null : ($data['reject_final']      ?? null),
                'vswr_final'        => $vswr['vswr_final'],
                'return_loss_final' => $vswr['return_loss_final'],
                'suhu_pemancar'     => $statusOff ? null : ($data['suhu_pemancar']     ?? null),
                'suhu_ruangan'      => $validated['suhu_ruangan'],
                'kelembaban'        => $validated['kelembaban']   ?? null,
                'keterangan'        => $data['keterangan']        ?? ($statusOff ? 'Pemancar OFF (bergantian/cadangan)' : null),
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
            'status'          => 'nullable|in:on,off',
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

        $statusOff = ($validated['status'] ?? 'on') === 'off';

        if (!$statusOff && !empty($validated['output_final_pa'])) {
            $p = $operasional->pemancar;
            if ($validated['output_final_pa'] > $p->kapasitas_output_final) {
                return back()->withErrors([
                    'output_final_pa' => "Output Final PA tidak boleh melebihi kapasitas ({$p->kapasitas_output_final} W)."
                ])->withInput();
            }
        }

        $vswr = $statusOff ? ['vswr_final' => null, 'return_loss_final' => null] : VswrCalculator::calculateAll($validated);
        if ($statusOff) {
            $validated['output_final_pa'] = null;
            $validated['output_driver']   = null;
            $validated['output_exciter']  = null;
            $validated['reflect_final']   = null;
            $validated['reject_final']    = null;
            $validated['suhu_pemancar']   = null;
        }
        $operasional->update(array_merge($validated, [
            'status'             => $validated['status'] ?? 'on',
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

    /**
     * Default jam checkpoint per shift (3x per shift), dipakai sebagai
     * ISIAN AWAL yang MASIH BISA DIEDIT operator — bukan nilai final.
     * Operator wajib menyesuaikan ke waktu sebenarnya sesuai catatan asli
     * mereka sebelum menyimpan.
     */
    private function defaultCheckpoints(string $shift): array
    {
        $map = [
            '1' => ['00:20', '04:00', '07:30'], // malam/dini hari 00:15-07:45
            '2' => ['08:00', '12:00', '15:20'], // pagi 07:45-15:45
            '3' => ['16:00', '20:00', '23:20'], // sore/malam 15:45-23:45
        ];
        $jams = $map[$shift] ?? $map['2'];

        return array_map(function ($jam) {
            [$h, $m] = explode(':', $jam);
            $menit = ((int) $m + random_int(1, 20)) % 60;
            $jamFinal = (int) $h + (int) (((int) $m + random_int(1, 20)) >= 60 ? 1 : 0);
            return sprintf('%02d:%02d', $jamFinal, $menit);
        }, $jams);
    }

    /**
     * GET /operasional/isi-susulan
     * Form bantu untuk OPERATOR mengisi log operasional hari/shift yang
     * terlewat, berdasarkan catatan manual (kertas/WA) yang mereka miliki.
     * Operator hanya bisa mengisi data ATAS NAMA DIRINYA SENDIRI.
     */
    public function backfillCreate(Request $request)
    {
        $user      = auth()->user();
        $pemancars = $this->getPemancarsForUser();

        $tanggal = $request->get('tanggal');
        $shift   = $request->get('shift');

        $checkpoints = null;
        if ($tanggal && $shift) {
            $checkpoints = $this->defaultCheckpoints($shift);
        }

        return view('operasional.backfill', compact(
            'pemancars', 'tanggal', 'shift', 'checkpoints'
        ));
    }

    /**
     * POST /operasional/isi-susulan
     * Menyimpan beberapa baris log operasional sekaligus (hingga 3
     * checkpoint waktu) ATAS NAMA operator yang sedang login. Nilai
     * parameter teknis tetap wajib diisi manual oleh operator dari
     * catatan asli mereka — tidak ada nilai yang dibuat otomatis oleh
     * sistem.
     */
    public function backfillStore(Request $request)
    {
        $user      = auth()->user();
        $pemancars = $this->getPemancarsForUser();

        if ($user->isOperator() && !$user->lokasi_dinas) {
            return back()->withErrors(['lokasi' => 'Lokasi dinas Anda belum diset.']);
        }

        $validated = $request->validate([
            'tanggal'                                  => 'required|date|before_or_equal:today',
            'shift'                                     => 'required|in:1,2,3',
            'checkpoint'                                => 'required|array|min:1',
            'checkpoint.*.jam'                           => 'required|date_format:H:i',
            'checkpoint.*.suhu_ruangan'                  => 'required|numeric|between:-50,100',
            'checkpoint.*.kelembaban'                    => 'nullable|numeric|between:0,100',
            'checkpoint.*.pemancar'                      => 'required|array|min:1',
            'checkpoint.*.pemancar.*.id'                 => 'required|exists:pemancars,id',
            'checkpoint.*.pemancar.*.status'             => 'nullable|in:on,off',
            'checkpoint.*.pemancar.*.output_final_pa'    => 'nullable|numeric|min:0',
            'checkpoint.*.pemancar.*.output_driver'      => 'nullable|numeric|min:0',
            'checkpoint.*.pemancar.*.output_exciter'     => 'nullable|numeric|min:0',
            'checkpoint.*.pemancar.*.reflect_final'      => 'nullable|numeric|min:0',
            'checkpoint.*.pemancar.*.reject_final'       => 'nullable|numeric|min:0',
            'checkpoint.*.pemancar.*.suhu_pemancar'      => 'nullable|numeric|between:-50,200',
            'checkpoint.*.pemancar.*.keterangan'         => 'nullable|string|max:500',
        ]);

        $allowedIds = $pemancars->pluck('id')->toArray();
        $dibuat = 0;

        foreach ($validated['checkpoint'] as $cp) {
            $dicatatPada = $validated['tanggal'] . ' ' . $cp['jam'] . ':00';

            foreach ($cp['pemancar'] as $data) {
                $statusOff = ($data['status'] ?? 'on') === 'off';

                // Lewati baris yang tidak diisi sama sekali (semua field kosong)
                // -- kecuali status-nya memang sengaja di-set OFF.
                $adaIsi = collect($data)->except(['id','status'])->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
                if (!$adaIsi && !$statusOff) continue;

                if ($user->isOperator() && !in_array($data['id'], $allowedIds)) {
                    return back()->withErrors(['pemancar' => 'Pemancar di luar lokasi dinas Anda.'])->withInput();
                }

                if (!$statusOff && !empty($data['output_final_pa'])) {
                    $p = $pemancars->firstWhere('id', $data['id']);
                    if ($p && $data['output_final_pa'] > $p->kapasitas_output_final) {
                        return back()->withErrors([
                            'output' => "Output Final PA untuk {$p->nama_stasiun} tidak boleh melebihi kapasitas ({$p->kapasitas_output_final} W).",
                        ])->withInput();
                    }
                }

                $vswr = $statusOff ? ['vswr_final' => null, 'return_loss_final' => null] : VswrCalculator::calculateAll($data);

                OperasionalLog::create([
                    'pemancar_id'       => $data['id'],
                    'status'            => $data['status'] ?? 'on',
                    'user_id'           => $user->id,
                    'jadwal_shift_id'   => null,
                    'dicatat_pada'      => $dicatatPada,
                    'output_final_pa'   => $statusOff ? null : ($data['output_final_pa']  ?? null),
                    'output_driver'     => $statusOff ? null : ($data['output_driver']    ?? null),
                    'output_exciter'    => $statusOff ? null : ($data['output_exciter']   ?? null),
                    'reflect_final'     => $statusOff ? null : ($data['reflect_final']    ?? null),
                    'reject_final'      => $statusOff ? null : ($data['reject_final']     ?? null),
                    'vswr_final'        => $vswr['vswr_final'],
                    'return_loss_final' => $vswr['return_loss_final'],
                    'suhu_pemancar'     => $statusOff ? null : ($data['suhu_pemancar']    ?? null),
                    'suhu_ruangan'      => $cp['suhu_ruangan'],
                    'kelembaban'        => $cp['kelembaban']         ?? null,
                    'keterangan'        => $data['keterangan']       ?? ($statusOff ? 'Pemancar OFF (bergantian/cadangan)' : null),
                    'is_backfill'       => true,
                ]);
                $dibuat++;
            }
        }

        if ($dibuat === 0) {
            return back()->withErrors(['umum' => 'Tidak ada data yang diisi.'])->withInput();
        }

        return redirect()->route('operasional.index')
            ->with('success', "{$dibuat} log operasional susulan berhasil disimpan atas nama Anda.");
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
