<?php
namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\GroundingTitik;
use App\Models\GroundingUkur;
use App\Models\GroundingWilayah;
use App\Support\Akses;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** PDF: laporan pengukuran per wilayah/periode dan kartu data per titik. */
class GroundingLaporanController extends Controller
{
    private function masuk(): void
    {
        if (!Akses::groundingMasuk(auth()->user())) abort(403, 'Menu ini hanya untuk Divisi Sarana & Prasarana.');
    }

    public function laporan(Request $request, GroundingWilayah $groundingWilayah)
    {
        $this->masuk();
        $v = $request->validate(['dari' => 'required|date', 'sampai' => 'required|date|after_or_equal:dari']);
        $dari = $v['dari']; $sampai = $v['sampai'];

        $titiks = $groundingWilayah->titiks()->with(['ukurs' => fn($q) => $q->whereBetween('tanggal', [$dari, $sampai])->reorder()
            ->orderBy('tanggal')->orderBy('id'), 'ukurs.user'])->get();

        $ringkas = [];   // per titik: ukur terakhir dalam periode
        $stat = ['baik' => 0, 'waspada' => 0, 'melebihi' => 0, 'belum' => 0];
        foreach ($titiks as $t) {
            if ($t->status !== 'aktif' && $t->ukurs->isEmpty()) continue;
            $akhir = $t->ukurs->last();
            $s = $akhir ? $akhir->status : 'belum';
            $stat[$s]++;
            $ringkas[] = ['t' => $t, 'akhir' => $akhir, 'status' => $s, 'n' => $t->ukurs->count(),
                'maks' => $t->ukurs->max('nilai_ohm'), 'min' => $t->ukurs->min('nilai_ohm')];
        }
        $detail = $titiks->flatMap(fn($t) => $t->ukurs->map(fn($u) => ['t' => $t, 'u' => $u]))
            ->sortBy(fn($r) => $r['u']->tanggal->format('Y-m-d').$r['t']->kode)->values();

        $settings = AppSetting::allKeyed();
        $acuan = AppSetting::tanggalAcuan($sampai);
        $pengelola = AppSetting::getKoordinator('sarana', $acuan);

        return $this->pdf(view('grounding.pdf-laporan', [
            'w' => $groundingWilayah, 'dari' => $dari, 'sampai' => $sampai, 'ringkas' => $ringkas, 'stat' => $stat,
            'detail' => $detail, 'settings' => $settings, 'pengelola' => $pengelola, 'pembuat' => auth()->user(),
        ])->render(), 'laporan-grounding-'.Str::slug($groundingWilayah->nama).'-'.$dari.'_'.$sampai);
    }

    public function kartu(GroundingTitik $groundingTitik)
    {
        $this->masuk();
        $groundingTitik->load(['wilayah', 'fotos', 'ukurs.user']);
        $settings = AppSetting::allKeyed();
        $pengelola = AppSetting::getKoordinator('sarana', AppSetting::tanggalAcuan(now()->toDateString()));
        return $this->pdf(view('grounding.pdf-kartu', [
            't' => $groundingTitik, 'settings' => $settings, 'pengelola' => $pengelola, 'pembuat' => auth()->user(),
        ])->render(), 'kartu-grounding-'.Str::slug($groundingTitik->kode));
    }

    private function pdf(string $html, string $nama)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(180);
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
            return response($d->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$nama.'.pdf"']);
        } catch (\Throwable $e) {
            \Log::error('Gagal cetak grounding: '.$e->getMessage());
            return back()->withErrors(['pdf' => 'Gagal membuat PDF: '.$e->getMessage()]);
        }
    }
}
