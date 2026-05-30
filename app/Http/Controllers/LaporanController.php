<?php
namespace App\Http\Controllers;

use App\Models\OperasionalLog;
use App\Models\Pemancar;
use App\Models\User;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function index()
    {
        $pemancars   = Pemancar::where('is_active',true)->orderBy('nama_stasiun')->get();
        $operators   = User::where('role','operator')->where('is_active',true)->orderBy('name')->get();
        $semuaLokasi = Pemancar::whereNotNull('lokasi')->distinct()->orderBy('lokasi')->pluck('lokasi');
        return view('laporan.index', compact('pemancars','operators','semuaLokasi'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'tanggal_dari'        => 'required|date',
            'tanggal_sampai'      => 'required|date|after_or_equal:tanggal_dari',
            'pemancar_id'         => 'nullable|exists:pemancars,id',
            'lokasi'              => 'nullable|string',
            'user_id'             => 'nullable|exists:users,id',
            'format'              => 'required|in:pdf,view',
            'generated_by_custom' => 'nullable|string|max:100',
            'pengelola'           => 'nullable|string|max:100',
        ]);

        $query = OperasionalLog::with(['pemancar','user','jadwalShift'])
            ->whereBetween('dicatat_pada',[
                Carbon::parse($validated['tanggal_dari'])->startOfDay(),
                Carbon::parse($validated['tanggal_sampai'])->endOfDay(),
            ])->orderBy('dicatat_pada');

        if (!empty($validated['pemancar_id'])) $query->where('pemancar_id',$validated['pemancar_id']);
        if (!empty($validated['user_id']))     $query->where('user_id',$validated['user_id']);
        if (!empty($validated['lokasi'])) {
            $ids = Pemancar::where('lokasi',$validated['lokasi'])->pluck('id');
            $query->whereIn('pemancar_id',$ids);
        }

        $logs    = $query->get();
        $settings = AppSetting::allKeyed();

        $summary = [
            'total_pencatatan'  => $logs->count(),
            'rata_output_final' => $logs->whereNotNull('output_final_pa')->avg('output_final_pa'),
            'rata_vswr_final'   => $logs->whereNotNull('vswr_final')->avg('vswr_final'),
            'max_vswr_final'    => $logs->max('vswr_final'),
            'rata_suhu_ruangan' => $logs->whereNotNull('suhu_ruangan')->avg('suhu_ruangan'),
            'rata_kelembaban'   => $logs->whereNotNull('kelembaban')->avg('kelembaban'),
            'pemancar_filter'   => !empty($validated['pemancar_id']) ? Pemancar::find($validated['pemancar_id']) : null,
            'operator_filter'   => !empty($validated['user_id'])     ? User::find($validated['user_id'])     : null,
            'lokasi_filter'     => $validated['lokasi'] ?? null,
        ];

        $data = [
            'logs'           => $logs,
            'summary'        => $summary,
            'tanggal_dari'   => $validated['tanggal_dari'],
            'tanggal_sampai' => $validated['tanggal_sampai'],
            'generated_at'   => now()->format('d/m/Y H:i'),
            'generated_by'   => $validated['generated_by_custom'] ?? auth()->user()->name,
            'pengelola'      => $validated['pengelola'] ?? ($settings['koordinator'] ?? ''),
            'satkerName'     => $settings['satuan_kerja'] ?? '',
        ];

        if ($validated['format'] === 'pdf') {
            return $this->generatePdf('laporan.pdf', $data,
                'laporan-operasional-'.$validated['tanggal_dari'].'-sd-'.$validated['tanggal_sampai'].'.pdf',
                'landscape');
        }
        return view('laporan.view', $data);
    }

    public function suhuBulanan(Request $request)
    {
        $bulan      = (int)$request->get('bulan', now()->month);
        $tahun      = (int)$request->get('tahun', now()->year);
        $pemancarId = $request->get('pemancar_id');
        $lokasi     = $request->get('lokasi');

        $query = OperasionalLog::with(['pemancar','user'])
            ->whereYear('dicatat_pada',$tahun)
            ->whereMonth('dicatat_pada',$bulan)
            ->whereNotNull('suhu_ruangan')
            ->orderBy('dicatat_pada');

        if ($pemancarId) $query->where('pemancar_id',$pemancarId);
        if ($lokasi) {
            $ids = Pemancar::where('lokasi',$lokasi)->pluck('id');
            $query->whereIn('pemancar_id',$ids);
        }

        $logs        = $query->get();
        $pemancars   = Pemancar::where('is_active',true)->orderBy('nama_stasiun')->get();
        $semuaLokasi = Pemancar::whereNotNull('lokasi')->distinct()->orderBy('lokasi')->pluck('lokasi');

        $dataGrafik = $logs
            ->groupBy(fn($l) => Carbon::parse($l->dicatat_pada)->format('d'))
            ->map(fn($g) => [
                'hari'               => $g->first()->dicatat_pada->format('d'),
                'tanggal'            => $g->first()->dicatat_pada->format('d/m'),
                'rata_suhu_ruangan'  => round($g->avg('suhu_ruangan'),1),
                'max_suhu_ruangan'   => $g->max('suhu_ruangan'),
                'min_suhu_ruangan'   => $g->min('suhu_ruangan'),
                'rata_suhu_pemancar' => round($g->whereNotNull('suhu_pemancar')->avg('suhu_pemancar'),1),
                'rata_kelembaban'    => round($g->whereNotNull('kelembaban')->avg('kelembaban'),1),
            ])->values();

        if ($request->wantsJson()) return response()->json($dataGrafik);

        return view('laporan.suhu', compact(
            'dataGrafik','pemancars','semuaLokasi',
            'bulan','tahun','pemancarId','lokasi','logs'
        ));
    }

    public function suhuPdf(Request $request)
    {
        $bulan      = (int)$request->get('bulan', now()->month);
        $tahun      = (int)$request->get('tahun', now()->year);
        $pemancarId = $request->get('pemancar_id');
        $lokasi     = $request->get('lokasi');

        $query = OperasionalLog::with(['pemancar','user','jadwalShift'])
            ->whereYear('dicatat_pada',$tahun)
            ->whereMonth('dicatat_pada',$bulan)
            ->orderBy('dicatat_pada');

        if ($pemancarId) $query->where('pemancar_id',$pemancarId);
        if ($lokasi) {
            $ids = Pemancar::where('lokasi',$lokasi)->pluck('id');
            $query->whereIn('pemancar_id',$ids);
        }

        $logs       = $query->get();
        $pemancar   = $pemancarId ? Pemancar::find($pemancarId) : null;
        $bulanLabel = Carbon::create($tahun,$bulan,1)->translatedFormat('F Y');
        $settings   = AppSetting::allKeyed();

        // Operator aktif yang sedang login
        $currentUser    = auth()->user();
        $operatorNama   = $currentUser->name;
        $operatorNip    = $currentUser->nip ?? '';
        $koordinatorName = $settings['koordinator'] ?? '';
        $satkerName      = $settings['satuan_kerja'] ?? '';

        return $this->generatePdf('laporan.suhu-pdf',
            compact('logs','pemancar','bulanLabel','bulan','tahun',
                    'lokasi','operatorNama','operatorNip',
                    'koordinatorName','satkerName'),
            "laporan-suhu-{$bulan}-{$tahun}.pdf",'portrait');
    }

    private function generatePdf(string $view, array $data, string $filename, string $orientation='portrait')
    {
        $html = view($view, $data)->render();
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html,'UTF-8');
        $dompdf->setPaper('A4',$orientation);
        $dompdf->set_option('isRemoteEnabled',false);
        $dompdf->set_option('isHtml5ParserEnabled',true);
        $dompdf->set_option('defaultFont','dejavu sans');
        $dompdf->set_option('isFontSubsettingEnabled',true);
        $dompdf->render();
        return response($dompdf->output(),200,[
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
