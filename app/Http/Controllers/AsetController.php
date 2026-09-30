<?php
namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\AsetKategori;
use App\Models\AsetFoto;
use App\Models\Lokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AsetController extends Controller
{
    private function checkAccess(): void
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isDivisi('sarana') && !$user->isDivisi('transmisi')) {
            abort(403, 'Menu ini hanya untuk Divisi Sarana & Prasarana atau Transmisi.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAccess();
        $query = Aset::with(['kategori','fotos'])->where('status','!=','dihapus');
        if ($request->filled('kategori_id')) $query->where('aset_kategori_id',$request->kategori_id);
        if ($request->filled('lokasi'))      $query->where('lokasi',$request->lokasi);
        if ($request->filled('kondisi'))     $query->where('kondisi',$request->kondisi);
        if ($request->filled('search'))      $query->where(fn($q)=>$q->where('nama','like','%'.$request->search.'%')->orWhere('kode_aset','like','%'.$request->search.'%')->orWhere('merk','like','%'.$request->search.'%'));

        $asets      = $query->orderBy('nama')->paginate(15)->withQueryString();
        $kategoris  = AsetKategori::orderBy('nama')->get();
        $lokasiList = Lokasi::listAktif();
        $semuaAset  = Aset::where('status','aktif')->get();
        $ringkasan  = [
            'total'       => $semuaAset->count(),
            'jatuh_tempo' => $semuaAset->filter(fn($a)=>in_array($a->status_maintenance,['jatuh_tempo','terlambat']))->count(),
            'rusak'       => $semuaAset->whereIn('kondisi',['rusak_ringan','rusak_berat'])->count(),
        ];
        return view('aset.index', compact('asets','kategoris','lokasiList','ringkasan'));
    }

    public function create()
    {
        $this->checkAccess();
        $kategoris  = AsetKategori::orderBy('nama')->get();
        $lokasiList = Lokasi::listAktif();
        $kodeBaru   = Aset::generateKode();
        return view('aset.create', compact('kategoris','lokasiList','kodeBaru'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();
        $v = $request->validate([
            'nama'                      => 'required|string|max:150',
            'aset_kategori_id'          => 'nullable|exists:aset_kategoris,id',
            'lokasi'                    => 'nullable|string|max:100',
            'merk'                      => 'nullable|string|max:100',
            'tipe_model'                => 'nullable|string|max:100',
            'no_seri'                   => 'nullable|string|max:100',
            'tanggal_perolehan'         => 'nullable|date',
            'harga_perolehan'           => 'nullable|numeric|min:0',
            'kondisi'                   => 'required|in:baik,rusak_ringan,rusak_berat,hilang',
            'keterangan'                => 'nullable|string',
            'interval_maintenance_hari' => 'nullable|integer|min:1',
            'fotos.*'                   => 'image|mimes:jpeg,png,jpg,webp|max:8192',
        ]);
        $v['kode_aset'] = Aset::generateKode();
        $v['status']    = 'aktif';
        $aset = Aset::create($v);
        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $idx => $foto) {
                $path = $foto->store('aset','public');
                AsetFoto::create(['aset_id'=>$aset->id,'path'=>$path,'urutan'=>$idx]);
            }
        }
        return redirect()->route('aset.show',$aset)->with('success','Aset berhasil ditambahkan dengan kode '.$aset->kode_aset.'.');
    }

    public function show(Aset $aset)
    {
        $this->checkAccess();
        $aset->load(['kategori','fotos','maintenanceLogs.user','maintenanceLogs.fotos']);
        return view('aset.show', compact('aset'));
    }

    public function edit(Aset $aset)
    {
        $this->checkAccess();
        $aset->load('fotos');
        $kategoris  = AsetKategori::orderBy('nama')->get();
        $lokasiList = Lokasi::listAktif();
        return view('aset.edit', compact('aset','kategoris','lokasiList'));
    }

    public function update(Request $request, Aset $aset)
    {
        $this->checkAccess();
        $v = $request->validate([
            'nama'                      => 'required|string|max:150',
            'aset_kategori_id'          => 'nullable|exists:aset_kategoris,id',
            'lokasi'                    => 'nullable|string|max:100',
            'merk'                      => 'nullable|string|max:100',
            'tipe_model'                => 'nullable|string|max:100',
            'no_seri'                   => 'nullable|string|max:100',
            'tanggal_perolehan'         => 'nullable|date',
            'harga_perolehan'           => 'nullable|numeric|min:0',
            'kondisi'                   => 'required|in:baik,rusak_ringan,rusak_berat,hilang',
            'status'                    => 'required|in:aktif,nonaktif',
            'keterangan'                => 'nullable|string',
            'interval_maintenance_hari' => 'nullable|integer|min:1',
            'fotos.*'                   => 'image|mimes:jpeg,png,jpg,webp|max:8192',
        ]);
        $aset->update($v);
        if ($request->hasFile('fotos')) {
            $last = $aset->fotos()->max('urutan') ?? -1;
            foreach ($request->file('fotos') as $idx => $foto) {
                $path = $foto->store('aset','public');
                AsetFoto::create(['aset_id'=>$aset->id,'path'=>$path,'urutan'=>$last+$idx+1]);
            }
        }
        if ($request->has('hapus_foto')) {
            foreach ($request->hapus_foto as $fotoId) {
                $f = AsetFoto::where('aset_id',$aset->id)->find($fotoId);
                if ($f) { Storage::disk('public')->delete($f->path); $f->delete(); }
            }
        }
        return redirect()->route('aset.show',$aset)->with('success','Aset berhasil diperbarui.');
    }

    public function destroy(Aset $aset)
    {
        if (!auth()->user()->isAdmin()) abort(403);
        $aset->update(['status'=>'dihapus']);
        return redirect()->route('aset.index')->with('success','Aset berhasil diarsipkan.');
    }

    public function kategoriIndex()
    {
        if (!auth()->user()->isAdmin()) abort(403);
        $kategoris = AsetKategori::withCount('asets')->orderBy('nama')->get();
        return view('aset.kategori', compact('kategoris'));
    }

    public function kategoriStore(Request $request)
    {
        if (!auth()->user()->isAdmin()) abort(403);
        $v = $request->validate(['nama'=>'required|string|max:100|unique:aset_kategoris,nama','interval_maintenance_hari'=>'nullable|integer|min:1']);
        AsetKategori::create($v);
        return redirect()->route('aset.kategori.index')->with('success','Kategori berhasil ditambahkan.');
    }

    public function kategoriUpdate(Request $request, AsetKategori $kategori)
    {
        if (!auth()->user()->isAdmin()) abort(403);
        $v = $request->validate(['nama'=>'required|string|max:100|unique:aset_kategoris,nama,'.$kategori->id,'interval_maintenance_hari'=>'nullable|integer|min:1']);
        $kategori->update($v);
        return redirect()->route('aset.kategori.index')->with('success','Kategori berhasil diperbarui.');
    }

    public function kategoriDestroy(AsetKategori $kategori)
    {
        if (!auth()->user()->isAdmin()) abort(403);
        if ($kategori->asets()->exists()) {
            return back()->withErrors(['kategori'=>'Kategori masih dipakai oleh aset.']);
        }
        $kategori->delete();
        return redirect()->route('aset.kategori.index')->with('success','Kategori berhasil dihapus.');
    }
}
