<?php
namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\PejabatRiwayat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Pergantian pejabat penandatangan (koordinator/pengelola, ketua tim, kepala).
 * Pejabat lama diarsipkan lengkap dengan masa jabatannya, sehingga laporan periode
 * lama tetap tercetak dengan pejabat yang menjabat saat itu.
 */
class PejabatController extends Controller
{
    public const LABEL = [
        'transmisi' => 'Pengelola Transmisi dan Distribusi',
        'studio'    => 'Pengelola Teknik Studio & Media Baru (Pahoman)',
        'studio_wk' => 'Koordinator Studio Produksi Way Kanan',
        'sarana'    => 'Pengelola Teknik Sarana & Prasarana',
        'kabid'     => 'Ketua Tim Kerja Teknologi & Media Baru',
        'kepala'    => 'Kepala LPP RRI Bandar Lampung',
    ];

    public function index()
    {
        $s = AppSetting::allKeyed();
        $pejabat = [];
        foreach (AppSetting::KODE_PEJABAT as $kode) {
            $k = AppSetting::kunciPejabat($kode);
            $pejabat[$kode] = [
                'label'   => self::LABEL[$kode],
                'saat_ini'=> match ($kode) {
                    'kabid'  => AppSetting::getKabid(),
                    'kepala' => AppSetting::getKepalaStasiun(),
                    default  => AppSetting::getKoordinator($kode),
                },
                'mulai'   => $s[$k['mulai']] ?? null,
                'riwayat' => PejabatRiwayat::where('kode', $kode)->orderByDesc('berlaku_sampai')->get(),
            ];
        }
        return view('setting.pejabat', compact('pejabat'));
    }

    /** Ganti pejabat: arsipkan yang sekarang, pasang yang baru berlaku sejak tanggal tertentu. */
    public function ganti(Request $request)
    {
        $v = $request->validate([
            'kode'     => 'required|in:'.implode(',', AppSetting::KODE_PEJABAT),
            'berlaku'  => 'required|date',
            'nama'     => 'required|string|max:100',
            'nip'      => 'nullable|string|max:30',
            'jabatan'  => 'nullable|string|max:100',
            'ttd'      => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
            'keterangan' => 'nullable|string|max:200',
        ]);

        $kode = $v['kode'];
        $k    = AppSetting::kunciPejabat($kode);
        $baru = Carbon::parse($v['berlaku'])->startOfDay();
        $mulaiLama = AppSetting::get($k['mulai']);

        if ($mulaiLama && $baru->lte(Carbon::parse($mulaiLama))) {
            throw ValidationException::withMessages([
                'berlaku' => 'Tanggal berlaku harus setelah tanggal mulai pejabat saat ini ('.Carbon::parse($mulaiLama)->format('d/m/Y').').',
            ]);
        }
        $terakhirArsip = PejabatRiwayat::where('kode', $kode)->max('berlaku_sampai');
        if ($terakhirArsip && $baru->lte(Carbon::parse($terakhirArsip))) {
            throw ValidationException::withMessages([
                'berlaku' => 'Tanggal berlaku harus setelah akhir masa jabatan arsip terakhir ('.Carbon::parse($terakhirArsip)->format('d/m/Y').').',
            ]);
        }

        $ttdBaru = $request->hasFile('ttd') ? $request->file('ttd')->store('settings/ttd', 'public') : '';

        DB::transaction(function () use ($kode, $k, $baru, $mulaiLama, $v, $ttdBaru) {
            $lama = [
                'nama' => (string) AppSetting::get($k['nama'], ''),
                'nip'  => (string) AppSetting::get($k['nip'], ''),
                'jab'  => (string) AppSetting::get($k['jabatan'], ''),
                'ttd'  => (string) AppSetting::get($k['ttd'], ''),
            ];
            if ($kode === 'transmisi' && $lama['nama'] === '') $lama['nama'] = (string) AppSetting::get('koordinator', '');

            // Arsipkan pejabat yang menjabat sekarang (hanya bila ada namanya)
            if ($lama['nama'] !== '') {
                PejabatRiwayat::create([
                    'kode'           => $kode,
                    'nama'           => $lama['nama'],
                    'nip'            => $lama['nip'] ?: null,
                    'jabatan'        => $lama['jab'] ?: null,
                    'ttd_path'       => $lama['ttd'] ?: null,
                    'berlaku_mulai'  => $mulaiLama ?: null,
                    'berlaku_sampai' => $baru->copy()->subDay()->toDateString(),
                    'keterangan'     => $v['keterangan'] ?? null,
                ]);
            }

            // Pasang pejabat baru. TTD lama TIDAK dihapus (dipakai arsip).
            AppSetting::set($k['nama'], $v['nama']);
            AppSetting::set($k['nip'], $v['nip'] ?? '');
            if (!empty($v['jabatan'])) AppSetting::set($k['jabatan'], $v['jabatan']);
            AppSetting::set($k['ttd'], $ttdBaru);
            AppSetting::set($k['mulai'], $baru->toDateString());
        });

        return redirect()->route('setting.pejabat')->with('success', 'Pejabat baru berlaku sejak '.$baru->format('d/m/Y').'. Pejabat sebelumnya diarsipkan.');
    }

    /** Koreksi data arsip (typo nama/NIP/jabatan, atau masa jabatan). */
    public function update(Request $request, PejabatRiwayat $riwayat)
    {
        $v = $request->validate([
            'nama'    => 'required|string|max:100',
            'nip'     => 'nullable|string|max:30',
            'jabatan' => 'nullable|string|max:100',
            'berlaku_mulai'  => 'nullable|date',
            'berlaku_sampai' => 'required|date',
            'ttd'     => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
        ]);

        if (!empty($v['berlaku_mulai']) && Carbon::parse($v['berlaku_sampai'])->lt(Carbon::parse($v['berlaku_mulai']))) {
            throw ValidationException::withMessages(['berlaku_sampai' => 'Tanggal akhir tidak boleh sebelum tanggal mulai.']);
        }

        $bentrok = PejabatRiwayat::where('kode', $riwayat->kode)->where('id', '!=', $riwayat->id)
            ->whereDate('berlaku_sampai', '>=', $v['berlaku_mulai'] ?? '1900-01-01')
            ->where(function ($q) use ($v) {
                $q->whereNull('berlaku_mulai')->orWhereDate('berlaku_mulai', '<=', $v['berlaku_sampai']);
            })->exists();
        if ($bentrok) {
            throw ValidationException::withMessages(['berlaku_sampai' => 'Masa jabatan bertumpuk dengan arsip pejabat lain pada jabatan yang sama.']);
        }

        if ($request->hasFile('ttd')) {
            if ($riwayat->ttd_path) Storage::disk('public')->delete($riwayat->ttd_path);
            $v['ttd_path'] = $request->file('ttd')->store('settings/ttd', 'public');
        }
        unset($v['ttd']);
        $riwayat->update($v);

        return redirect()->route('setting.pejabat')->with('success', 'Arsip pejabat diperbarui.');
    }

    public function destroy(PejabatRiwayat $riwayat)
    {
        if ($riwayat->ttd_path) Storage::disk('public')->delete($riwayat->ttd_path);
        $riwayat->delete();
        return redirect()->route('setting.pejabat')->with('success', 'Arsip pejabat dihapus.');
    }
}
