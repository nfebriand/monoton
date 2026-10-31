<!DOCTYPE html>
<html><head><meta charset="UTF-8">@include('grounding._pdf-css')</head><body><div class="page">
@php
    $L = \App\Models\GroundingUkur::STATUS_LABEL;
    $fmt = fn($v) => $v === null ? '-' : rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    $spek = [
        'Wilayah' => $t->wilayah->nama, 'Peruntukan' => $t->peruntukan, 'Tipe grounding' => $t->tipe, 'Jenis bahan' => $t->bahan,
        'Kedalaman' => $t->kedalaman_m !== null ? $fmt($t->kedalaman_m).' m' : null,
        'Diameter rod' => $t->diameter_mm !== null ? $fmt($t->diameter_mm).' mm' : null,
        'Panjang kabel' => $t->panjang_kabel_m !== null ? $fmt($t->panjang_kabel_m).' m' : null,
        'Ukuran kabel' => $t->ukuran_kabel_mm2 !== null ? $fmt($t->ukuran_kabel_mm2)." mm\u{00B2}" : null,
        'Jumlah elektroda' => $t->jumlah_elektroda, 'Tahun pasang' => $t->tahun_pasang,
        'Batas tahanan' => $fmt($t->batas_ohm)." \u{03A9}",
        'Koordinat GPS' => $t->latitude !== null ? $t->latitude.', '.$t->longitude : null,
        'Status titik' => \App\Models\GroundingTitik::STATUS[$t->status] ?? $t->status,
    ];
@endphp
@include('grounding._pdf-kop', ['judul' => 'Kartu Data Titik Grounding', 'sub' => $t->kode.' - '.$t->nama])

<h4>Spesifikasi</h4>
<table class="t">
    @foreach($spek as $k => $v)@if($v !== null && $v !== '')<tr><td style="width:30%;background:#f4f4f4"><strong>{{ $k }}</strong></td><td>{{ $v }}</td></tr>@endif @endforeach
    @if($t->catatan)<tr><td style="background:#f4f4f4"><strong>Catatan</strong></td><td style="white-space:pre-wrap">{{ $t->catatan }}</td></tr>@endif
</table>

@if($t->fotos->isNotEmpty())
<h4>Foto</h4>
@foreach($t->fotos->chunk(3) as $row)
<table class="fg"><tr>
    @foreach($row as $f)
    @php
        $rel = $f->thumb_path ?: $f->path; $b64 = '';
        if ($rel && \Illuminate\Support\Facades\Storage::disk('public')->exists($rel)) {
            $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
            $b64 = 'data:'.($ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg')).';base64,'.base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($rel));
        }
    @endphp
    <td><div class="box"><div class="in">@if($b64)<img src="{{ $b64 }}">@else<span style="color:#aaa;font-size:7pt">Foto tidak tersedia</span>@endif</div></div><div class="cap">{{ $f->keterangan ?: '-' }}</div></td>
    @endforeach
    @for($i = 0; $i < 3 - count($row); $i++)<td></td>@endfor
</tr></table>
@endforeach
@endif

<h4>Riwayat Pengukuran</h4>
<table class="t">
    <thead><tr><th style="width:13%">Tanggal</th><th style="width:11%">Nilai (&Omega;)</th><th style="width:13%">Status</th><th style="width:11%">Tanah</th><th style="width:20%">Alat / metode</th><th style="width:14%">Petugas</th><th>Keterangan</th></tr></thead>
    <tbody>
    @forelse($t->ukurs as $u)
    <tr>
        <td class="c">{{ $u->tanggal->format('d/m/Y') }}</td><td class="r"><strong>{{ $fmt($u->nilai_ohm) }}</strong></td>
        <td class="c"><span class="b" style="background:{{ \App\Models\GroundingTitik::warna($u->status) }}">{{ $L[$u->status] }}</span></td>
        <td>{{ \App\Models\GroundingUkur::KONDISI[$u->kondisi_tanah] ?? '-' }}</td>
        <td>{{ trim($u->alat.' '.($u->metode ? '('.$u->metode.')' : '')) ?: '-' }}</td><td>{{ $u->user->name ?? '-' }}</td><td>{{ $u->keterangan }}</td>
    </tr>
    @empty
    <tr><td colspan="7" class="c">Belum ada pengukuran.</td></tr>
    @endforelse
    </tbody>
</table>

@include('grounding._pdf-ttd')
</div></body></html>
