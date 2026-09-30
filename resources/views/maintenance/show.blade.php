@extends('layouts.app')
@section('title','Detail Maintenance')
@section('page-title','Detail Maintenance')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="{{ route('maintenance.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    <span class="fw-bold">{{ $maintenance->aset->nama }}</span>
    <span class="badge" style="background:{{ ['preventif'=>'#10ac84','korektif'=>'#ee5a24','inspeksi'=>'#0a3d62'][$maintenance->jenis] }}">{{ $maintenance->jenis_label }}</span>
    @if(auth()->user()->isAdmin())
    <form action="{{ route('maintenance.destroy',$maintenance) }}" method="POST" class="ms-auto" onsubmit="return confirm('Hapus log ini?')">
        @csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
    </form>
    @endif
</div>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2 text-primary"></i>Informasi Maintenance</div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Tanggal</div>
                        <div class="fw-bold mono">{{ $maintenance->tanggal->format('d/m/Y') }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Hasil</div>
                        <span class="badge {{ $maintenance->hasil=='selesai'?'bg-success':($maintenance->hasil=='sebagian'?'bg-warning text-dark':'bg-secondary') }}">{{ $maintenance->hasil_label }}</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Petugas</div>
                        <div class="fw-bold">{{ $maintenance->user->name }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted" style="font-size:.7rem">Biaya</div>
                        <div class="fw-bold mono">{{ $maintenance->biaya ? 'Rp '.number_format($maintenance->biaya,0,',','.') : '–' }}</div>
                    </div>
                </div>

                <div class="text-muted mb-1" style="font-size:.72rem;font-weight:700">URAIAN PEKERJAAN</div>
                <div class="p-3 rounded mb-3" style="background:var(--bg);border:1px solid var(--border);font-size:.85rem;line-height:1.6;white-space:pre-line">
                    {{ $maintenance->uraian_pekerjaan }}
                </div>

                @if($maintenance->sparepart_terpakai)
                <div class="text-muted mb-2" style="font-size:.72rem;font-weight:700">SPAREPART TERPAKAI</div>
                <table class="table table-sm mb-3">
                    <thead><tr><th>Nama</th><th>Jumlah</th><th>Satuan</th></tr></thead>
                    <tbody>
                    @foreach($maintenance->sparepart_terpakai as $sp)
                    <tr><td>{{ $sp['nama'] }}</td><td class="mono">{{ $sp['jumlah'] }}</td><td>{{ $sp['satuan'] }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
                @endif

                @if($maintenance->rencana_maintenance_berikutnya)
                <div class="alert alert-info py-2 mb-0">
                    <i class="bi bi-calendar-event me-2"></i>Rencana maintenance berikutnya: <strong>{{ $maintenance->rencana_maintenance_berikutnya->format('d F Y') }}</strong>
                </div>
                @endif

                @if($maintenance->keterangan)
                <div class="mt-3 text-muted" style="font-size:.8rem">{{ $maintenance->keterangan }}</div>
                @endif
            </div>
        </div>

        @if($maintenance->fotos->isNotEmpty())
        @php $sebelumF = $maintenance->fotos->where('tipe','sebelum'); $sesudahF = $maintenance->fotos->where('tipe','sesudah'); @endphp
        <div class="card">
            <div class="card-header"><i class="bi bi-images me-2"></i>Dokumentasi Foto</div>
            <div class="card-body">
                @if($sebelumF->isNotEmpty())
                <div class="text-muted mb-2" style="font-size:.72rem;font-weight:700">SEBELUM</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:.5rem" class="mb-3">
                    @foreach($sebelumF as $f)
                    <div style="aspect-ratio:1;border-radius:6px;overflow:hidden;border:2px solid var(--border)">
                        <img src="{{ $f->thumb_url }}" onclick="bukaLightbox('{{ $f->url }}')" style="width:100%;height:100%;object-fit:cover;cursor:zoom-in">
                    </div>
                    @endforeach
                </div>
                @endif
                @if($sesudahF->isNotEmpty())
                <div class="text-muted mb-2" style="font-size:.72rem;font-weight:700">SESUDAH</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:.5rem">
                    @foreach($sesudahF as $f)
                    <div style="aspect-ratio:1;border-radius:6px;overflow:hidden;border:2px solid var(--border)">
                        <img src="{{ $f->thumb_url }}" onclick="bukaLightbox('{{ $f->url }}')" style="width:100%;height:100%;object-fit:cover;cursor:zoom-in">
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-box-seam me-2 text-primary"></i>Aset Terkait</div>
            <div class="card-body">
                <div class="fw-bold mb-1">{{ $maintenance->aset->nama }}</div>
                <div class="text-muted mono mb-2" style="font-size:.75rem">{{ $maintenance->aset->kode_aset }}</div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted" style="font-size:.75rem">Lokasi</span>
                    <span style="font-size:.78rem">{{ $maintenance->aset->lokasi ?? '–' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted" style="font-size:.75rem">Kondisi Saat Ini</span>
                    <span class="badge" style="background:{{ \App\Models\Aset::KONDISI_COLOR[$maintenance->aset->kondisi] }}">{{ $maintenance->aset->kondisi_label }}</span>
                </div>
                <a href="{{ route('aset.show',$maintenance->aset) }}" class="btn btn-outline-primary w-100">
                    <i class="bi bi-eye me-1"></i>Lihat Detail Aset
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
