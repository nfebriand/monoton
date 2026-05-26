<?php
namespace App\Http\Controllers;

use App\Models\Eviden;
use App\Models\EvidenFoto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenController extends Controller
{
    public function index(Request $request)
    {
        $query = Eviden::with(['user','operators','fotos'])
            ->orderByDesc('tanggal')->orderByDesc('jam_mulai');

        if ($request->filled('tanggal_dari'))   $query->where('tanggal','>=',$request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $query->where('tanggal','<=',$request->tanggal_sampai);
        if ($request->filled('user_id'))        $query->where('user_id',$request->user_id);

        // Operator hanya lihat evidennya sendiri
        if (auth()->user()->isOperator()) {
            $query->where(function($q){
                $q->where('user_id',auth()->id())
                  ->orWhereHas('operators',fn($q2)=>$q2->where('users.id',auth()->id()));
            });
        }

        $evidens   = $query->paginate(12)->withQueryString();
        $operators = User::where('role','operator')->where('is_active',true)->orderBy('name')->get();
        return view('eviden.index', compact('evidens','operators'));
    }

    public function create()
    {
        $operators = User::where('role','operator')->where('is_active',true)->orderBy('name')->get();
        return view('eviden.create', compact('operators'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul'        => 'required|string|max:255',
            'deskripsi'    => 'nullable|string',
            'tanggal'      => 'required|date',
            'jam_mulai'    => 'required|date_format:H:i',
            'jam_selesai'  => 'required|date_format:H:i',
            'lokasi'       => 'nullable|string|max:255',
            'supervisi'    => 'nullable|string|max:255',
            'operator_ids' => 'nullable|array',
            'operator_ids.*'=>'exists:users,id',
            'fotos'        => 'nullable|array',
            'fotos.*'      => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'foto_keterangan'=>'nullable|array',
        ]);

        $eviden = Eviden::create(array_merge(
            $validated,
            ['user_id'=>auth()->id()]
        ));

        // Attach operators
        if (!empty($validated['operator_ids'])) {
            $eviden->operators()->sync($validated['operator_ids']);
        }

        // Upload foto
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $idx => $foto) {
                $path = $foto->store('eviden','public');
                EvidenFoto::create([
                    'eviden_id'  =>$eviden->id,
                    'path'       =>$path,
                    'keterangan' =>$request->input("foto_keterangan.{$idx}"),
                    'urutan'     =>$idx,
                ]);
            }
        }

        return redirect()->route('eviden.show',$eviden)
            ->with('success','Catatan eviden berhasil disimpan.');
    }

    public function show(Eviden $eviden)
    {
        $eviden->load(['user','operators','fotos']);
        return view('eviden.show', compact('eviden'));
    }

    public function edit(Eviden $eviden)
    {
        if (!auth()->user()->isAdmin() && auth()->id() !== $eviden->user_id) abort(403);
        $eviden->load(['operators','fotos']);
        $operators = User::where('role','operator')->where('is_active',true)->orderBy('name')->get();
        return view('eviden.edit', compact('eviden','operators'));
    }

    public function update(Request $request, Eviden $eviden)
    {
        if (!auth()->user()->isAdmin() && auth()->id() !== $eviden->user_id) abort(403);

        $validated = $request->validate([
            'judul'        => 'required|string|max:255',
            'deskripsi'    => 'nullable|string',
            'tanggal'      => 'required|date',
            'jam_mulai'    => 'required|date_format:H:i',
            'jam_selesai'  => 'required|date_format:H:i',
            'lokasi'       => 'nullable|string|max:255',
            'supervisi'    => 'nullable|string|max:255',
            'operator_ids' => 'nullable|array',
            'operator_ids.*'=>'exists:users,id',
        ]);

        $eviden->update($validated);
        $eviden->operators()->sync($validated['operator_ids'] ?? []);

        // Upload foto baru
        if ($request->hasFile('fotos')) {
            $last = $eviden->fotos()->max('urutan') ?? -1;
            foreach ($request->file('fotos') as $idx => $foto) {
                $path = $foto->store('eviden','public');
                EvidenFoto::create([
                    'eviden_id'  =>$eviden->id,
                    'path'       =>$path,
                    'keterangan' =>$request->input("foto_keterangan_baru.{$idx}"),
                    'urutan'     =>$last+$idx+1,
                ]);
            }
        }

        // Hapus foto
        if ($request->has('hapus_foto')) {
            foreach ($request->hapus_foto as $fotoId) {
                $foto = EvidenFoto::where('eviden_id',$eviden->id)->find($fotoId);
                if ($foto) { Storage::disk('public')->delete($foto->path); $foto->delete(); }
            }
        }

        return redirect()->route('eviden.show',$eviden)->with('success','Eviden berhasil diperbarui.');
    }

    public function destroy(Eviden $eviden)
    {
        if (!auth()->user()->isAdmin() && auth()->id() !== $eviden->user_id) abort(403);
        foreach ($eviden->fotos as $f) Storage::disk('public')->delete($f->path);
        $eviden->delete();
        return redirect()->route('eviden.index')->with('success','Eviden berhasil dihapus.');
    }
}
