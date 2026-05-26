<?php

namespace App\Http\Controllers;

use App\Models\Pemancar;
use App\Models\PemancarFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PemancarController extends Controller
{
    private function checkAdmin()
    {
        if (!auth()->user()->isAdmin()) abort(403, 'Hanya Administrator yang dapat melakukan aksi ini.');
    }

    private function rules(): array
    {
        return [
            'nama_stasiun'           => 'required|string|max:255',
            'merk'                   => 'required|string|max:255',
            'tipe_unit'              => 'required|string|max:255',
            'tipe_komponen'          => 'required|in:tabung,solid_state',
            'modulasi'               => 'required|in:AM,FM',
            'lokasi'                 => 'nullable|string|max:255',
            'latitude'               => 'nullable|numeric|between:-90,90',
            'longitude'              => 'nullable|numeric|between:-180,180',
            'alamat_lokasi'          => 'nullable|string|max:500',
            'kapasitas_output_final' => 'required|numeric|min:0',
            'tipe_exciter'           => 'nullable|string|max:255',
            'tipe_driver'            => 'nullable|string|max:255',
            'frekuensi'              => 'nullable|numeric|min:0',
            'nomor_izin'             => 'nullable|string|max:100',
            'tanggal_instalasi'      => 'nullable|date',
            'keterangan'             => 'nullable|string',
        ];
    }

    public function index()
    {
        $user  = auth()->user();
        $query = Pemancar::with('fotos')->orderBy('nama_stasiun');

        // Operator hanya lihat pemancar di lokasi dinasnya
        if ($user->isOperator() && $user->lokasi_dinas) {
            $query->where('lokasi', $user->lokasi_dinas);
        }

        $pemancars    = $query->paginate(12);
        $lokasiDinas  = $user->lokasi_dinas;
        $isFiltered   = $user->isOperator();

        return view('pemancar.index', compact('pemancars', 'lokasiDinas', 'isFiltered'));
    }

    public function create()
    {
        $this->checkAdmin();
        return view('pemancar.create');
    }

    public function store(Request $request)
    {
        $this->checkAdmin();
        $validated = $request->validate(array_merge($this->rules(), [
            'fotos'           => 'nullable|array',
            'fotos.*'         => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'foto_keterangan' => 'nullable|array',
        ]));

        $pemancar = Pemancar::create($validated);

        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $idx => $foto) {
                $path = $foto->store('pemancar', 'public');
                PemancarFoto::create([
                    'pemancar_id' => $pemancar->id,
                    'path'        => $path,
                    'keterangan'  => $request->input("foto_keterangan.{$idx}"),
                    'urutan'      => $idx,
                ]);
            }
        }

        return redirect()->route('pemancar.show', $pemancar)
            ->with('success', 'Data pemancar berhasil disimpan.');
    }

    public function show(Pemancar $pemancar)
    {
        // Operator hanya bisa lihat pemancar di lokasi dinasnya
        $this->checkLokasiAkses($pemancar);

        $pemancar->load([
            'fotos',
            'operasionalLogs' => fn($q) => $q->with('user')->latest('dicatat_pada')->limit(10)
        ]);
        return view('pemancar.show', compact('pemancar'));
    }

    public function edit(Pemancar $pemancar)
    {
        $this->checkAdmin();
        $pemancar->load('fotos');
        return view('pemancar.edit', compact('pemancar'));
    }

    public function update(Request $request, Pemancar $pemancar)
    {
        $this->checkAdmin();
        $validated = $request->validate($this->rules());
        $validated['is_active'] = $request->boolean('is_active', true);
        $pemancar->update($validated);

        if ($request->hasFile('fotos')) {
            $lastUrutan = $pemancar->fotos()->max('urutan') ?? -1;
            foreach ($request->file('fotos') as $idx => $foto) {
                $path = $foto->store('pemancar', 'public');
                PemancarFoto::create([
                    'pemancar_id' => $pemancar->id,
                    'path'        => $path,
                    'keterangan'  => $request->input("foto_keterangan_baru.{$idx}"),
                    'urutan'      => $lastUrutan + $idx + 1,
                ]);
            }
        }

        if ($request->has('hapus_foto')) {
            foreach ($request->hapus_foto as $fotoId) {
                $foto = PemancarFoto::where('pemancar_id', $pemancar->id)->find($fotoId);
                if ($foto) { Storage::disk('public')->delete($foto->path); $foto->delete(); }
            }
        }

        return redirect()->route('pemancar.show', $pemancar)
            ->with('success', 'Data pemancar berhasil diperbarui.');
    }

    public function destroy(Pemancar $pemancar)
    {
        $this->checkAdmin();
        foreach ($pemancar->fotos as $foto) Storage::disk('public')->delete($foto->path);
        $pemancar->delete();
        return redirect()->route('pemancar.index')
            ->with('success', 'Data pemancar berhasil dihapus.');
    }

    private function checkLokasiAkses(Pemancar $pemancar): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if ($user->lokasi_dinas && $pemancar->lokasi !== $user->lokasi_dinas) {
            abort(403, 'Anda tidak dapat mengakses pemancar di luar lokasi dinas Anda.');
        }
    }
}
