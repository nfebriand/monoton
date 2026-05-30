<?php
namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Pemancar;
use App\Models\OperasionalLog;
use App\Models\Eviden;
use App\Models\JadwalShift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        if (!auth()->user()->isAdmin()) abort(403);
        $settings = AppSetting::allKeyed();
        return view('setting.index', compact('settings'));
    }

    public function update(Request $request)
    {
        if (!auth()->user()->isAdmin()) abort(403);

        $request->validate([
            'satuan_kerja'   => 'nullable|string|max:255',
            'kepala_stasiun' => 'nullable|string|max:255',
            'kepala_bidang'  => 'nullable|string|max:255',
            'koordinator'    => 'nullable|string|max:255',
            'tema_warna'     => 'nullable|string|max:7',
            'logo'           => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        foreach (['satuan_kerja','kepala_stasiun','kepala_bidang','koordinator','tema_warna'] as $key) {
            if ($request->has($key)) {
                AppSetting::set($key, $request->input($key));
            }
        }

        if ($request->hasFile('logo')) {
            $old = AppSetting::get('logo_path');
            if ($old) Storage::disk('public')->delete($old);
            $path = $request->file('logo')->store('settings','public');
            AppSetting::set('logo_path', $path);
        }

        return back()->with('success','Pengaturan berhasil disimpan.');
    }

    /**
     * Simpan catatan update baru dengan auto-increment versi
     */
    public function addUpdateLog(Request $request)
    {
        if (!auth()->user()->isAdmin()) abort(403);

        $request->validate([
            'catatan_update'  => 'required|string|max:2000',
            'tipe_increment'  => 'required|in:patch,minor,major',
        ]);

        $newVersion = AppSetting::addUpdateLog(
            $request->catatan_update,
            $request->tipe_increment
        );

        return back()->with('success',"Update log berhasil ditambahkan. Versi baru: v{$newVersion}");
    }

    public function updateKredit(Request $request)
    {
        if (!auth()->user()->isAdmin()) abort(403);
        $request->validate([
            'kredit_pembuat'  => 'required|string|max:100',
            'kredit_wa'       => 'required|string|max:20',
            'kredit_telegram' => 'required|string|max:50',
        ]);
        AppSetting::set('kredit_pembuat',  $request->kredit_pembuat);
        AppSetting::set('kredit_wa',       $request->kredit_wa);
        AppSetting::set('kredit_telegram', $request->kredit_telegram);
        return back()->with('success','Kredit berhasil diperbarui.');
    }
}
