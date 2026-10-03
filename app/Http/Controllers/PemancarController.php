<?php
namespace App\Http\Controllers;

use App\Models\Pemancar;
use App\Models\PemancarFoto;
use App\Models\User;
use App\Models\OperasionalLog;
use Illuminate\Http\Request;
use App\Helpers\ImageHelper;

class PemancarController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Hanya admin + transmisi yang boleh lihat pemancar
        if ($user->isOperator() && !$user->isDivisi('transmisi')) {
            abort(403, 'Menu Data Pemancar hanya untuk Divisi Transmisi.');
        }

        $query = Pemancar::with('fotos')->orderBy('lokasi')->orderBy('nama_stasiun');

        $lokasiDinas = $user->lokasi_dinas;
        $isFiltered  = false;

        if ($user->isOperator() && $lokasiDinas) {
            $query->where('lokasi', $lokasiDinas);
            $isFiltered = true;
        }

        if ($request->filled('lokasi')) {
            $query->where('lokasi', $request->lokasi);
            $isFiltered  = true;
            $lokasiDinas = $request->lokasi;
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request){
                $q->where('nama_stasiun','like','%'.$request->search.'%')
                  ->orWhere('merk','like','%'.$request->search.'%');
            });
        }

        $pemancars  = $query->paginate(12)->withQueryString();
        $lokasiList = Pemancar::whereNotNull('lokasi')->distinct()->orderBy('lokasi')->pluck('lokasi');

        return view('pemancar.index', compact('pemancars','lokasiList','isFiltered','lokasiDinas'));
    }

    public function create()
    {
        return view('pemancar.create');
    }

	public function store(Request $request)
	{
		$v = $request->validate([
			'nama_stasiun'            => 'required|string|max:150',
			'lokasi'                  => 'nullable|string|max:100',
			'modulasi'                => 'required|in:FM,AM',
			'frekuensi'               => 'nullable|string|max:30',
			'merk'                    => 'nullable|string|max:100',
			'tipe_unit'               => 'nullable|string|max:100',
			'tahun_pembuatan'         => 'nullable|integer',
			'kapasitas_output_final'  => 'required|numeric|min:0',
			'kapasitas_output_driver' => 'nullable|numeric|min:0',
			'kapasitas_output_exciter'=> 'nullable|numeric|min:0',
			'keterangan'              => 'nullable|string',
			'fotos.*'                 => 'image|mimes:jpeg,png,jpg,webp|max:8192',
			'is_active'               => 'nullable|boolean',
		]);

		$v['is_active'] = $request->boolean('is_active', true);

		$pemancar = Pemancar::create($v);

		if ($request->hasFile('fotos')) {

			foreach ($request->file('fotos') as $idx => $foto) {

				$img = ImageHelper::saveWithThumbnail($foto, 'pemancar');

				PemancarFoto::create([
					'pemancar_id' => $pemancar->id,
					'path'        => $img['original'],
					'keterangan'  => $request->input("foto_keterangan.{$idx}"),
					'urutan'      => $idx,
				]);
			}
		}

		return redirect()->route('pemancar.show',$pemancar)
			->with('success','Pemancar berhasil ditambahkan.');
	}

    public function show(Pemancar $pemancar)
    {
        $pemancar->load('fotos');

        if (auth()->user()->isOperator() && auth()->user()->lokasi_dinas
            && $pemancar->lokasi !== auth()->user()->lokasi_dinas) {
            abort(403,'Anda tidak memiliki akses ke pemancar ini.');
        }

        // Operator bertugas di lokasi yang sama
        $operatorLokasi = collect();
        if ($pemancar->lokasi) {
            $operatorLokasi = User::where('role','operator')
                ->where('lokasi_dinas', $pemancar->lokasi)
                ->orderBy('name')
                ->get();
        }

        // Log terakhir
        $logTerakhir = OperasionalLog::where('pemancar_id',$pemancar->id)
            ->with(['user','jadwalShift'])
            ->orderByDesc('dicatat_pada')
            ->first();

        // ── Statistik 30 hari terakhir ──
        $logs30 = OperasionalLog::where('pemancar_id',$pemancar->id)
            ->where('dicatat_pada','>=', now()->subDays(30))
            ->get();
        // Baris berstatus OFF (pemancar sedang tidak mengudara, bergantian
        // dengan unit lain) tidak ikut dihitung dalam statistik/rata-rata.
        $logs30On = $logs30->where('status', '!=', 'off');

        $statistik = [
            'total_log'        => $logs30->count(),
            'total_off'        => $logs30->where('status', 'off')->count(),
            'rata_output'      => $logs30On->whereNotNull('output_final_pa')->avg('output_final_pa'),
            'max_output'       => $logs30On->whereNotNull('output_final_pa')->max('output_final_pa'),
            'min_output'       => $logs30On->whereNotNull('output_final_pa')->min('output_final_pa'),
            'rata_vswr'        => $logs30On->whereNotNull('vswr_final')->avg('vswr_final'),
            'max_vswr'         => $logs30On->whereNotNull('vswr_final')->max('vswr_final'),
            'rata_suhu_pmcr'   => $logs30On->whereNotNull('suhu_pemancar')->avg('suhu_pemancar'),
            'max_suhu_pmcr'    => $logs30On->whereNotNull('suhu_pemancar')->max('suhu_pemancar'),
            'rata_suhu_ruang'  => $logs30On->whereNotNull('suhu_ruangan')->avg('suhu_ruangan'),
            'jumlah_vswr_buruk'=> $logs30On->where('vswr_final','>=',2)->count(),
        ];

        // ── Total sepanjang waktu ──
        $totalLogSemua = OperasionalLog::where('pemancar_id',$pemancar->id)->count();
        $logPertama    = OperasionalLog::where('pemancar_id',$pemancar->id)
            ->orderBy('dicatat_pada')->first();

        // ── Riwayat 10 log terakhir untuk tabel mini ──
        $riwayatLog = OperasionalLog::where('pemancar_id',$pemancar->id)
            ->with('user')
            ->orderByDesc('dicatat_pada')
            ->limit(10)
            ->get();

        // ── Info teknis tambahan ──
        $umurUnit = $pemancar->tahun_pembuatan ? (now()->year - $pemancar->tahun_pembuatan) : null;
        $efisiensi = null;
        if ($pemancar->kapasitas_output_driver && $pemancar->kapasitas_output_final) {
            $efisiensi = round(($pemancar->kapasitas_output_driver / $pemancar->kapasitas_output_final) * 100, 1);
        }

        return view('pemancar.show', compact(
            'pemancar','operatorLokasi','logTerakhir','statistik',
            'totalLogSemua','logPertama','riwayatLog','umurUnit','efisiensi'
        ));
    }

    public function edit(Pemancar $pemancar)
    {
        $pemancar->load('fotos');
        $lokasiList = \App\Models\Lokasi::orderBy('nama')->get();
        return view('pemancar.edit', compact('pemancar', 'lokasiList'));
    }

    public function update(Request $request, Pemancar $pemancar)
    {
        $v = $request->validate([
            'nama_stasiun'            => 'required|string|max:150',
            'lokasi'                  => 'nullable|string|max:100',
            'modulasi'                => 'required|in:FM,AM',
            'frekuensi'               => 'nullable|string|max:30',
            'merk'                    => 'nullable|string|max:100',
            'tipe_unit'               => 'nullable|string|max:100',
            'tahun_pembuatan'         => 'nullable|integer',
            'kapasitas_output_final'  => 'required|numeric|min:0',
            'kapasitas_output_driver' => 'nullable|numeric|min:0',
            'kapasitas_output_exciter'=> 'nullable|numeric|min:0',
            'keterangan'              => 'nullable|string',
            'fotos.*'                 => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'is_active'               => 'nullable|boolean',
        ]);
        $v['is_active'] = $request->boolean('is_active', true);
        $pemancar->update($v);

        if ($request->hasFile('fotos')) {
		$last = $pemancar->fotos()->max('urutan') ?? -1;
			foreach ($request->file('fotos') as $idx => $foto) {
				$img = ImageHelper::saveWithThumbnail($foto, 'pemancar');
				PemancarFoto::create([
					'pemancar_id' => $pemancar->id,
					'path'        => $img['original'],
					'keterangan'  => $request->input("foto_keterangan_baru.{$idx}"),
					'urutan'      => $last + $idx + 1,
				]);
			}
		}
        if ($request->has('hapus_foto')) {
            foreach ($request->hapus_foto as $fotoId) {
                $foto = PemancarFoto::where('pemancar_id',$pemancar->id)->find($fotoId);
				if ($foto){ImageHelper::deleteWithThumbnail($foto->path); $foto->delete();}
            }
        }

        return redirect()->route('pemancar.show',$pemancar)->with('success','Data pemancar berhasil diperbarui.');
    }

    public function destroy(Pemancar $pemancar)
	{
		foreach ($pemancar->fotos as $f) {
			ImageHelper::deleteWithThumbnail($f->path);
		}
		$pemancar->delete();
		return redirect()
			->route('pemancar.index')
			->with('success','Pemancar berhasil dihapus.');
	}
}
