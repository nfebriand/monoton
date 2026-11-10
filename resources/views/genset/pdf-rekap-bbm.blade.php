<!DOCTYPE html>
<html><head><meta charset="UTF-8">@include('grounding._pdf-css')</head><body><div class="page">
@php
    $fmt = fn($v) => rtrim(rtrim(number_format((float) $v, 1, ',', '.'), '0'), ',');
    $sub = \Carbon\Carbon::parse($dari)->translatedFormat('d F Y').' s.d. '.\Carbon\Carbon::parse($sampai)->translatedFormat('d F Y');
@endphp
@include('grounding._pdf-kop', ['judul' => 'Rekap Pengisian BBM Genset', 'sub' => 'Periode '.$sub])

<p style="font-size:8.5pt;margin-bottom:4px">Total pengisian: <strong>{{ $fmt($total) }} L</strong> dalam <strong>{{ $logs->count() }}</strong> kali pengisian.</p>

<h4>Rekap per Unit</h4>
<table class="t">
    <thead><tr><th style="width:6%">No</th><th>Unit</th><th style="width:14%">Lokasi</th><th style="width:10%">Jml isi</th><th style="width:14%">Total (L)</th><th style="width:14%">Rata-rata (L)</th><th style="width:14%">Terakhir isi</th></tr></thead>
    <tbody>
    @forelse($perUnit as $r)
    <tr><td class="c">{{ $loop->iteration }}</td><td>{{ $r['unit']->nama_unit ?? '-' }}</td><td>{{ $r['unit']->lokasi ?? '-' }}</td><td class="c">{{ $r['jumlah'] }}</td>
        <td class="r"><strong>{{ $fmt($r['total']) }}</strong></td><td class="r">{{ $fmt($r['rata']) }}</td><td class="c">{{ $r['terakhir'] ? $r['terakhir']->format('d/m/Y') : '-' }}</td></tr>
    @empty
    <tr><td colspan="7" class="c">Tidak ada pengisian pada periode ini.</td></tr>
    @endforelse
    </tbody>
</table>

@if($perBulan->isNotEmpty())
<h4>Total per Bulan</h4>
<table class="t" style="width:50%">
    <thead><tr><th>Bulan</th><th>Total (L)</th></tr></thead>
    <tbody>@foreach($perBulan as $bln => $lt)<tr><td class="c">{{ \Carbon\Carbon::parse($bln.'-01')->translatedFormat('F Y') }}</td><td class="r">{{ $fmt($lt) }}</td></tr>@endforeach</tbody>
</table>
@endif

@if($logs->isNotEmpty())
<h4>Rincian Pengisian</h4>
<table class="t">
    <thead><tr><th style="width:12%">Tanggal</th><th style="width:22%">Unit</th><th style="width:10%">Liter</th><th style="width:15%">Alasan</th><th style="width:16%">Petugas</th><th>Keterangan</th></tr></thead>
    <tbody>
    @foreach($logs as $l)
    <tr><td class="c">{{ $l->tanggal->format('d/m/Y') }}</td><td>{{ $l->gensetUnit->nama_unit ?? '-' }}</td><td class="r"><strong>{{ $fmt($l->bbm_isi) }}</strong></td>
        <td>{{ $l->alasan_label }}</td><td>{{ $l->user->name ?? '-' }}</td><td>{{ $l->keterangan }}</td></tr>
    @endforeach
    </tbody>
</table>
@endif

@include('grounding._pdf-ttd')
</div></body></html>
