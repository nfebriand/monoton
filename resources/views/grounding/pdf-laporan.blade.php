<!DOCTYPE html>
<html><head><meta charset="UTF-8">@include('grounding._pdf-css')</head><body><div class="page">
@php
    $L = \App\Models\GroundingUkur::STATUS_LABEL;
    $fmt = fn($v) => $v === null ? '-' : rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
@endphp
@include('grounding._pdf-kop', ['judul' => 'Laporan Pengukuran Tahanan Grounding', 'sub' => $w->nama.($w->lokasi ? ' - '.$w->lokasi : '').' | Periode '.\Carbon\Carbon::parse($dari)->translatedFormat('d F Y').' s.d. '.\Carbon\Carbon::parse($sampai)->translatedFormat('d F Y')])

<p style="font-size:8.5pt;margin-bottom:4px">Ringkasan hasil terakhir dalam periode:
    <strong>{{ $stat['baik'] }}</strong> baik, <strong>{{ $stat['waspada'] }}</strong> waspada, <strong>{{ $stat['melebihi'] }}</strong> melebihi batas, <strong>{{ $stat['belum'] }}</strong> belum diukur.</p>

<h4>Ringkasan per Titik</h4>
<table class="t">
    <thead><tr><th style="width:5%">No</th><th style="width:12%">Kode</th><th>Titik / Letak</th><th style="width:10%">Batas (&Omega;)</th><th style="width:7%">Jml ukur</th><th style="width:11%">Terakhir (&Omega;)</th><th style="width:11%">Tanggal</th><th style="width:12%">Status</th></tr></thead>
    <tbody>
    @forelse($ringkas as $r)
    <tr>
        <td class="c">{{ $loop->iteration }}</td><td>{{ $r['t']->kode }}</td><td>{{ $r['t']->nama }}@if($r['t']->peruntukan)<br><span style="color:#666">{{ $r['t']->peruntukan }}</span>@endif</td>
        <td class="r">{{ $fmt($r['t']->batas_ohm) }}</td><td class="c">{{ $r['n'] }}</td>
        <td class="r"><strong>{{ $r['akhir'] ? $fmt($r['akhir']->nilai_ohm) : '-' }}</strong></td>
        <td class="c">{{ $r['akhir'] ? $r['akhir']->tanggal->format('d/m/Y') : '-' }}</td>
        <td class="c"><span class="b" style="background:{{ \App\Models\GroundingTitik::warna($r['status']) }}">{{ $L[$r['status']] }}</span></td>
    </tr>
    @empty
    <tr><td colspan="8" class="c">Tidak ada titik.</td></tr>
    @endforelse
    </tbody>
</table>

@if($detail->isNotEmpty())
<h4>Rincian Pengukuran</h4>
<table class="t">
    <thead><tr><th style="width:11%">Tanggal</th><th style="width:12%">Titik</th><th style="width:9%">Nilai (&Omega;)</th><th style="width:11%">Status</th><th style="width:10%">Tanah</th><th style="width:17%">Alat / metode</th><th style="width:12%">Petugas</th><th>Keterangan</th></tr></thead>
    <tbody>
    @foreach($detail as $d)
    @php $u = $d['u']; @endphp
    <tr>
        <td class="c">{{ $u->tanggal->format('d/m/Y') }}</td><td>{{ $d['t']->kode }}</td><td class="r"><strong>{{ $fmt($u->nilai_ohm) }}</strong></td>
        <td class="c"><span class="b" style="background:{{ \App\Models\GroundingTitik::warna($u->status) }}">{{ $L[$u->status] }}</span></td>
        <td>{{ \App\Models\GroundingUkur::KONDISI[$u->kondisi_tanah] ?? '-' }}</td>
        <td>{{ trim($u->alat.' '.($u->metode ? '('.$u->metode.')' : '')) ?: '-' }}</td>
        <td>{{ $u->user->name ?? '-' }}</td><td>{{ $u->keterangan }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

@include('grounding._pdf-ttd')
</div></body></html>
