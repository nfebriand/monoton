<?php
namespace App\Http\Controllers;

use App\Models\SkemaShift;
use App\Models\SkemaShiftItem;
use Illuminate\Http\Request;

class SkemaShiftController extends Controller
{
    private function checkAccess(): void
    {
        if (!auth()->user()->hasAdminAccess()) {
            abort(403, 'Hanya Administrator atau Admin Divisi yang dapat mengelola skema shift.');
        }
    }

    public function index()
    {
        $this->checkAccess();
        $user = auth()->user();

        $query = SkemaShift::with(['items','creator'])->orderBy('divisi')->orderBy('nama');

        // Admin Divisi hanya lihat skema divisinya
        if ($user->isAdminDivisi()) {
            $query->where('divisi', $user->divisi);
        }

        $skemas = $query->get()->groupBy('divisi');
        return view('jadwal.skema.index', compact('skemas'));
    }

    public function create()
    {
        $this->checkAccess();
        $user = auth()->user();
        // Admin divisi hanya bisa buat skema untuk divisinya
        $divisiOptions = $user->isAdmin()
            ? \App\Models\User::DIVISI_LABEL
            : [$user->divisi => \App\Models\User::DIVISI_LABEL[$user->divisi]];

        return view('jadwal.skema.create', compact('divisiOptions'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();
        $user = auth()->user();

        $v = $request->validate([
            'nama'              => 'required|string|max:100',
            'divisi'            => 'required|in:transmisi,studio,sarana',
            'kode'              => 'required|string|max:50|alpha_dash|unique:skema_shifts,kode',
            'is_active'         => 'nullable|boolean',
            'shift_label'       => 'required|array|min:1',
            'shift_label.*'     => 'required|string|max:50',
            'shift_mulai'       => 'required|array',
            'shift_mulai.*'     => 'required|date_format:H:i',
            'shift_selesai'     => 'required|array',
            'shift_selesai.*'   => 'required|date_format:H:i',
        ]);

        // Admin divisi tidak bisa buat skema untuk divisi lain
        if ($user->isAdminDivisi() && $v['divisi'] !== $user->divisi) {
            abort(403, 'Anda hanya dapat membuat skema untuk divisi Anda sendiri.');
        }

        $skema = SkemaShift::create([
            'nama'       => $v['nama'],
            'divisi'     => $v['divisi'],
            'kode'       => $v['kode'],
            'is_default' => false,
            'is_active'  => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        foreach ($v['shift_label'] as $i => $label) {
            if (!trim($label)) continue;
            SkemaShiftItem::create([
                'skema_shift_id' => $skema->id,
                'nomor'          => $i + 1,
                'label'          => $label,
                'jam_mulai'      => $v['shift_mulai'][$i],
                'jam_selesai'    => $v['shift_selesai'][$i],
            ]);
        }

        return redirect()->route('skema-shift.index')->with('success', "Skema \"{$skema->nama}\" berhasil dibuat.");
    }

    public function edit(SkemaShift $skemaShift)
    {
        $this->checkAccess();
        $user = auth()->user();

        if ($user->isAdminDivisi() && $skemaShift->divisi !== $user->divisi) {
            abort(403, 'Anda hanya dapat mengedit skema divisi Anda sendiri.');
        }

        $skemaShift->load('items');
        $divisiOptions = $user->isAdmin()
            ? \App\Models\User::DIVISI_LABEL
            : [$user->divisi => \App\Models\User::DIVISI_LABEL[$user->divisi]];

        return view('jadwal.skema.edit', compact('skemaShift', 'divisiOptions'));
    }

    public function update(Request $request, SkemaShift $skemaShift)
    {
        $this->checkAccess();
        $user = auth()->user();

        if ($user->isAdminDivisi() && $skemaShift->divisi !== $user->divisi) {
            abort(403);
        }

        $v = $request->validate([
            'nama'            => 'required|string|max:100',
            'divisi'          => 'required|in:transmisi,studio,sarana',
            'kode'            => 'required|string|max:50|alpha_dash|unique:skema_shifts,kode,'.$skemaShift->id,
            'is_active'       => 'nullable|boolean',
            'shift_label'     => 'required|array|min:1',
            'shift_label.*'   => 'required|string|max:50',
            'shift_mulai'     => 'required|array',
            'shift_mulai.*'   => 'required|date_format:H:i',
            'shift_selesai'   => 'required|array',
            'shift_selesai.*' => 'required|date_format:H:i',
        ]);

        if ($user->isAdminDivisi() && $v['divisi'] !== $user->divisi) {
            abort(403);
        }

        $skemaShift->update([
            'nama'      => $v['nama'],
            'divisi'    => $v['divisi'],
            'kode'      => $v['kode'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Hapus semua item lama, ganti dengan yang baru
        $skemaShift->items()->delete();
        foreach ($v['shift_label'] as $i => $label) {
            if (!trim($label)) continue;
            SkemaShiftItem::create([
                'skema_shift_id' => $skemaShift->id,
                'nomor'          => $i + 1,
                'label'          => $label,
                'jam_mulai'      => $v['shift_mulai'][$i],
                'jam_selesai'    => $v['shift_selesai'][$i],
            ]);
        }

        return redirect()->route('skema-shift.index')->with('success', "Skema \"{$skemaShift->nama}\" berhasil diperbarui.");
    }

    public function destroy(SkemaShift $skemaShift)
    {
        $this->checkAccess();
        $user = auth()->user();

        if ($user->isAdminDivisi() && $skemaShift->divisi !== $user->divisi) {
            abort(403);
        }
        if ($skemaShift->is_default) {
            return back()->withErrors(['skema' => 'Skema bawaan sistem tidak dapat dihapus, hanya bisa dinonaktifkan.']);
        }

        $skemaShift->delete();
        return redirect()->route('skema-shift.index')->with('success', 'Skema berhasil dihapus.');
    }

    public function toggleAktif(SkemaShift $skemaShift)
    {
        $this->checkAccess();
        $user = auth()->user();

        if ($user->isAdminDivisi() && $skemaShift->divisi !== $user->divisi) {
            abort(403);
        }

        $skemaShift->update(['is_active' => !$skemaShift->is_active]);
        $status = $skemaShift->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Skema \"{$skemaShift->nama}\" berhasil {$status}.");
    }
}
