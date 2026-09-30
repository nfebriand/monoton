<?php
namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceFoto;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaintenanceController extends Controller
{
    /** Hanya Admin (super) + Divisi Sarana yang boleh akses maintenance. */
    private function checkAccess(): void
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isDivisi('sarana')) {
            abort(403, 'Menu ini hanya untuk Divisi Sarana & Prasarana.');
        }
    }

    /** Hapus log: Admin super + Admin Divisi Sarana. */
    private function canDelete(): bool
    {
        $user = auth()->user();
        return $user->isAdmin()
            || ($user->isAdminDivisi() && $user->isDivisi('sarana'));
    }

    public function index(Request $request)
    {
        $this->checkAccess();
        $query = MaintenanceLog::with(['aset','user'])->orderByDesc('tanggal');
        if ($request->filled('aset_id'))        $query->where('aset_id',$request->aset_id);
        if ($request->filled('jenis'))          $query->where('jenis',$request->jenis);
        if ($request->filled('tanggal_dari'))   $query->where('tanggal','>=',$request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $query->where('tanggal','<=',$request->tanggal_sampai);
        $logs           = $query->paginate(15)->withQueryString();
        $asets          = Aset::where('status','aktif')->orderBy('nama')->get();
        $perluPerhatian = Aset::where('status','aktif')->get()
            ->filter(fn($a)=>in_array($a->status_maintenance,['jatuh_tempo','terlambat']))
            ->sortBy('jatuh_tempo_maintenance');
        return view('maintenance.index', compact('logs','asets','perluPerhatian'));
    }

    public function create(Request $request)
    {
        $this->checkAccess();
        $asets        = Aset::where('status','aktif')->orderBy('nama')->get();
        $asetTerpilih = $request->filled('aset_id') ? Aset::find($request->aset_id) : null;
        return view('maintenance.create', compact('asets','asetTerpilih'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();
        $v = $request->validate([
            'aset_id'                        => 'required|exists:asets,id',
            'tanggal'                        => 'required|date',
            'jenis'                          => 'required|in:preventif,korektif,inspeksi',
            'uraian_pekerjaan'               => 'required|string',
            'hasil'                          => 'required|in:selesai,sebagian,tertunda',
            'biaya'                          => 'nullable|numeric|min:0',
            'sparepart_nama'                 => 'nullable|array',
            'sparepart_jumlah'               => 'nullable|array',
            'sparepart_satuan'               => 'nullable|array',
            'rencana_maintenance_berikutnya' => 'nullable|date',
            'keterangan'                     => 'nullable|string',
            'fotos_sebelum.*'                => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'fotos_sesudah.*'                => 'image|mimes:jpeg,png,jpg,webp|max:8192',
        ]);

        $sparepartTerpakai = [];
        if ($request->filled('sparepart_nama')) {
            foreach ($request->sparepart_nama as $idx => $nama) {
                if (!trim($nama)) continue;
                $sparepartTerpakai[] = [
                    'nama'   => $nama,
                    'jumlah' => $request->sparepart_jumlah[$idx] ?? 1,
                    'satuan' => $request->sparepart_satuan[$idx] ?? 'pcs',
                ];
            }
        }

        $log = MaintenanceLog::create([
            'aset_id'                        => $v['aset_id'],
            'user_id'                        => auth()->id(),
            'tanggal'                        => $v['tanggal'],
            'jenis'                          => $v['jenis'],
            'uraian_pekerjaan'               => $v['uraian_pekerjaan'],
            'hasil'                          => $v['hasil'],
            'biaya'                          => $v['biaya'] ?? null,
            'sparepart_terpakai'             => $sparepartTerpakai ?: null,
            'rencana_maintenance_berikutnya' => $v['rencana_maintenance_berikutnya'] ?? null,
            'keterangan'                     => $v['keterangan'] ?? null,
        ]);

        if ($v['hasil'] === 'selesai') {
            Aset::where('id',$v['aset_id'])->update(['maintenance_terakhir' => $v['tanggal']]);
        }

        if ($request->hasFile('fotos_sebelum')) {
            foreach ($request->file('fotos_sebelum') as $foto) {
                $saved = ImageHelper::saveWithThumbnail($foto, 'maintenance');
                MaintenanceFoto::create(['maintenance_log_id'=>$log->id,'path'=>$saved['original'],'thumb_path'=>$saved['thumbnail'],'tipe'=>'sebelum']);
            }
        }
        if ($request->hasFile('fotos_sesudah')) {
            foreach ($request->file('fotos_sesudah') as $foto) {
                $saved = ImageHelper::saveWithThumbnail($foto, 'maintenance');
                MaintenanceFoto::create(['maintenance_log_id'=>$log->id,'path'=>$saved['original'],'thumb_path'=>$saved['thumbnail'],'tipe'=>'sesudah']);
            }
        }

        return redirect()->route('maintenance.show',$log)->with('success','Log maintenance berhasil disimpan.');
    }

    public function show(MaintenanceLog $maintenance)
    {
        $this->checkAccess();
        $maintenance->load(['aset','user','fotos']);
        return view('maintenance.show', compact('maintenance'));
    }

    public function destroy(MaintenanceLog $maintenance)
    {
        if (!$this->canDelete()) abort(403, 'Hanya Admin Divisi Sarana yang dapat menghapus log maintenance.');
        foreach ($maintenance->fotos as $f) ImageHelper::deleteWithThumbnail($f->path);
        $maintenance->delete();
        return redirect()->route('maintenance.index')->with('success','Log maintenance berhasil dihapus.');
    }
}
