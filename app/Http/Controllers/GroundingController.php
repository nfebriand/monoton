<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\GroundingTitik;
use App\Models\GroundingWilayah;
use App\Models\PerawatanRutin;
use App\Support\Akses;
use Illuminate\Http\Request;

/** Wilayah (peta) pemantauan grounding. */
class GroundingController extends Controller
{
    private function masuk(): void
    {
        if (!Akses::groundingMasuk(auth()->user())) abort(403, 'Menu ini hanya untuk Divisi Sarana & Prasarana.');
    }

    private function kelola(): void
    {
        $this->masuk();
        if (!Akses::groundingKelola(auth()->user())) abort(403, 'Hanya admin Sarana & Prasarana yang dapat mengelola wilayah dan titik grounding.');
    }

    private function rules(): array
    {
        return [
            'nama'       => 'required|string|max:255',
            'lokasi'     => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:2000',
            'peta'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'hapus_peta' => 'nullable|boolean',
            'is_active'  => 'nullable|boolean',
        ];
    }

    public function index()
    {
        $this->masuk();
        $wilayah = GroundingWilayah::with(['titiks.ukurTerakhir'])->orderByDesc('is_active')->orderBy('nama')->get()
            ->map(function ($w) {
                $h = ['baik' => 0, 'waspada' => 0, 'melebihi' => 0, 'belum' => 0];
                foreach ($w->titiks->where('status', 'aktif') as $t) $h[$t->status_ukur]++;
                $w->ringkas = $h;
                return $w;
            });
        return view('grounding.index', ['wilayah' => $wilayah, 'bisaKelola' => Akses::groundingKelola(auth()->user())]);
    }

    public function create()
    {
        $this->kelola();
        return view('grounding.wilayah-form', ['w' => new GroundingWilayah(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $this->kelola();
        $v = $request->validate($this->rules());
        $data = ['nama' => $v['nama'], 'lokasi' => $v['lokasi'] ?? null, 'keterangan' => $v['keterangan'] ?? null, 'is_active' => true];
        if ($request->hasFile('peta')) {
            $data['peta_path'] = ImageHelper::saveWithThumbnail($request->file('peta'), 'grounding/peta')['original'];
        }
        $w = GroundingWilayah::create($data);
        return redirect()->route('grounding.wilayah.show', $w)->with('success', 'Wilayah dibuat. Sekarang tambahkan titik grounding.');
    }

    public function show(GroundingWilayah $groundingWilayah)
    {
        $this->masuk();
        $groundingWilayah->load(['titiks.ukurTerakhir']);
        return view('grounding.wilayah-show', ['w' => $groundingWilayah, 'bisaKelola' => Akses::groundingKelola(auth()->user())]);
    }

    public function edit(GroundingWilayah $groundingWilayah)
    {
        $this->kelola();
        return view('grounding.wilayah-form', ['w' => $groundingWilayah]);
    }

    public function update(Request $request, GroundingWilayah $groundingWilayah)
    {
        $this->kelola();
        $v = $request->validate($this->rules());
        $data = ['nama' => $v['nama'], 'lokasi' => $v['lokasi'] ?? null, 'keterangan' => $v['keterangan'] ?? null,
                 'is_active' => $request->boolean('is_active')];
        if ($request->hasFile('peta')) {
            if ($groundingWilayah->peta_path) ImageHelper::deleteWithThumbnail($groundingWilayah->peta_path);
            $data['peta_path'] = ImageHelper::saveWithThumbnail($request->file('peta'), 'grounding/peta')['original'];
        } elseif ($request->boolean('hapus_peta') && $groundingWilayah->peta_path) {
            ImageHelper::deleteWithThumbnail($groundingWilayah->peta_path);
            $data['peta_path'] = null;
        }
        $groundingWilayah->update($data);
        return redirect()->route('grounding.wilayah.show', $groundingWilayah)->with('success', 'Wilayah diperbarui.');
    }

    /** Buat jadwal pengukuran berkala (Perawatan Rutin) untuk semua titik aktif wilayah yang belum punya jadwal. */
    public function buatJadwal(Request $request, GroundingWilayah $groundingWilayah)
    {
        $this->kelola();
        $v = $request->validate([
            'interval_hari' => 'required|integer|min:1|max:3650',
            'nama_tugas'    => 'nullable|string|max:150',
        ]);
        if (!\Illuminate\Support\Facades\Schema::hasColumn('perawatan_rutin', 'grounding_titik_id')) {
            return back()->withErrors(['jadwal' => 'Jalankan php artisan migrate terlebih dahulu.']);
        }
        $nama = trim((string) ($v['nama_tugas'] ?? '')) ?: 'Pengukuran tahanan grounding';
        $ada = PerawatanRutin::where('objek_tipe', 'grounding')->whereIn('grounding_titik_id', $groundingWilayah->titiks()->pluck('id'))
            ->pluck('grounding_titik_id')->all();
        $dibuat = 0;
        foreach ($groundingWilayah->titiks()->where('status', 'aktif')->with('ukurTerakhir')->get() as $t) {
            if (in_array($t->id, $ada, true)) continue;
            PerawatanRutin::create([
                'objek_tipe' => 'grounding', 'grounding_titik_id' => $t->id, 'nama_tugas' => $nama, 'basis' => 'tanggal',
                'interval_hari' => (int) $v['interval_hari'],
                'terakhir_tanggal' => $t->ukurTerakhir ? $t->ukurTerakhir->tanggal->format('Y-m-d') : null,
                'aktif' => true, 'dibuat_oleh' => auth()->id(),
            ]);
            $dibuat++;
        }
        return back()->with('success', $dibuat ? "$dibuat jadwal pengukuran dibuat (tiap {$v['interval_hari']} hari). Pengingat tampil di Perawatan Rutin dan dashboard." : 'Semua titik aktif sudah memiliki jadwal.');
    }

    public function destroy(GroundingWilayah $groundingWilayah)
    {
        $this->kelola();
        if ($groundingWilayah->titiks()->exists()) {
            return back()->withErrors(['hapus' => 'Wilayah masih memiliki titik grounding. Hapus titiknya dulu, atau nonaktifkan wilayah ini.']);
        }
        if ($groundingWilayah->peta_path) ImageHelper::deleteWithThumbnail($groundingWilayah->peta_path);
        $groundingWilayah->delete();
        return redirect()->route('grounding.index')->with('success', 'Wilayah dihapus.');
    }
}
