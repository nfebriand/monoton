<?php
namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /** Daftar divisi yang punya koordinator masing-masing */
    private const DIVISI = ['transmisi','studio','sarana'];

    public function index()
    {
        $settings = AppSetting::allKeyed();
        return view('setting.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $rules = [
            'satuan_kerja'   => 'nullable|string|max:150',
            'kepala_stasiun' => 'nullable|string|max:100',
            'kepala_bidang'  => 'nullable|string|max:100', // legacy
            'koordinator'    => 'nullable|string|max:100', // legacy
            'tema_warna'     => 'nullable|string|max:20',
            'logo'           => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',

            // Kepala Bidang Teknik (lintas divisi)
            'kabid_nama'    => 'nullable|string|max:100',
            'kabid_nip'     => 'nullable|string|max:30',
            'kabid_jabatan' => 'nullable|string|max:100',
            'kabid_ttd'     => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
        ];

        // Rules dinamis per divisi: koordinator_{divisi}_nama/nip/jabatan/ttd
        foreach (self::DIVISI as $d) {
            $rules["koordinator_{$d}_nama"]    = 'nullable|string|max:100';
            $rules["koordinator_{$d}_nip"]     = 'nullable|string|max:30';
            $rules["koordinator_{$d}_jabatan"] = 'nullable|string|max:100';
            $rules["koordinator_{$d}_ttd"]     = 'nullable|image|mimes:png,jpg,jpeg|max:1024';
        }

        $request->validate($rules);

        // Logo aplikasi
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings','public');
            AppSetting::set('logo_path', $path);
        }

        // TTD Kepala Bidang
        if ($request->hasFile('kabid_ttd')) {
            $path = $request->file('kabid_ttd')->store('settings/ttd','public');
            AppSetting::set('kabid_ttd', $path);
        }

        // TTD per divisi
        foreach (self::DIVISI as $d) {
            if ($request->hasFile("koordinator_{$d}_ttd")) {
                $path = $request->file("koordinator_{$d}_ttd")->store('settings/ttd','public');
                AppSetting::set("koordinator_{$d}_ttd", $path);
            }
        }

        // Field teks
        $textKeys = ['satuan_kerja','kepala_stasiun','kepala_bidang','koordinator','tema_warna',
                      'kabid_nama','kabid_nip','kabid_jabatan'];
        foreach (self::DIVISI as $d) {
            $textKeys[] = "koordinator_{$d}_nama";
            $textKeys[] = "koordinator_{$d}_nip";
            $textKeys[] = "koordinator_{$d}_jabatan";
        }
        foreach ($textKeys as $key) {
            if ($request->has($key)) {
                AppSetting::set($key, $request->input($key));
            }
        }

        return redirect()->route('setting.index')->with('success','Pengaturan berhasil disimpan.');
    }

    /**
     * Hapus tanda tangan digital (reset ke kosong)
     * target: kabid_ttd | koordinator_transmisi_ttd | koordinator_studio_ttd | koordinator_sarana_ttd
     */
    public function removeTtd(Request $request)
    {
        $valid = array_merge(['kabid_ttd'], array_map(fn($d)=>"koordinator_{$d}_ttd", self::DIVISI));
        $v = $request->validate(['target' => 'required|in:'.implode(',',$valid)]);

        $key  = $v['target'];
        $path = AppSetting::get($key);
        if ($path) {
            Storage::disk('public')->delete($path);
            AppSetting::set($key, '');
        }
        return redirect()->route('setting.index')->with('success','Tanda tangan berhasil dihapus.');
    }

    public function addUpdateLog(Request $request)
    {
        $v = $request->validate([
            'catatan_update'  => 'required|string',
            'tipe_increment'  => 'required|in:patch,minor,major',
        ]);
        AppSetting::addUpdateLog($v['catatan_update'], $v['tipe_increment']);
        return redirect()->route('setting.index')->with('success','Catatan update berhasil ditambahkan & versi diperbarui.');
    }

    public function updateKredit(Request $request)
    {
        $v = $request->validate([
            'kredit_pembuat'  => 'required|string|max:100',
            'kredit_wa'       => 'required|string|max:30',
            'kredit_telegram' => 'required|string|max:50',
        ]);
        foreach ($v as $key => $val) AppSetting::set($key, $val);
        return redirect()->route('setting.index')->with('success','Kredit berhasil diperbarui.');
    }
}
