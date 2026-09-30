<?php
namespace App\Http\Controllers;

use App\Models\Lokasi;
use Illuminate\Http\Request;

class LokasiController extends Controller
{
    public function index()
    {
        if (!auth()->user()->isAdmin()) abort(403);
        $lokasis = Lokasi::orderBy('divisi')->orderBy('nama')->get();
        return view('lokasi.index', compact('lokasis'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isAdmin()) abort(403);
        $v = $request->validate([
            'nama'        => 'required|string|max:100|unique:lokasis,nama',
            'divisi'      => 'required|in:transmisi,studio,sarana,umum',
            'alamat'      => 'nullable|string|max:200',
            'keterangan'  => 'nullable|string|max:200',
            'is_active'   => 'nullable|boolean',
        ]);
        $v['is_active'] = $request->boolean('is_active', true);
        Lokasi::create($v);
        return redirect()->route('lokasi.index')->with('success','Lokasi berhasil ditambahkan.');
    }

    public function update(Request $request, Lokasi $lokasi)
    {
        if (!auth()->user()->isAdmin()) abort(403);
        $v = $request->validate([
            'nama'       => 'required|string|max:100|unique:lokasis,nama,'.$lokasi->id,
            'divisi'     => 'required|in:transmisi,studio,sarana,umum',
            'alamat'     => 'nullable|string|max:200',
            'keterangan' => 'nullable|string|max:200',
            'is_active'  => 'nullable|boolean',
        ]);
        $v['is_active'] = $request->boolean('is_active', true);
        $lokasi->update($v);
        return redirect()->route('lokasi.index')->with('success','Lokasi berhasil diperbarui.');
    }

    public function destroy(Lokasi $lokasi)
    {
        if (!auth()->user()->isAdmin()) abort(403);
        $lokasi->delete();
        return redirect()->route('lokasi.index')->with('success','Lokasi berhasil dihapus.');
    }
}
