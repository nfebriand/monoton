<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Eviden — MonOTOn</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
@page { margin: 1cm; }
body{font-family:'DejaVu Sans',Arial,sans-serif;font-size:8.5pt;color:#1a1a1a;}
.kop{width:100%;border-bottom:3px solid #0a3d62;padding-bottom:8px;margin-bottom:10px;}
.kop-tbl{width:100%;border-collapse:collapse;}
.kop-logo{width:46px;height:46px;background:#0a3d62;border-radius:7px;text-align:center;line-height:46px;font-size:16pt;color:#00d2d3;}
.kop-brand{padding-left:8px;vertical-align:middle;}
.kop-brand h1{font-size:11pt;font-weight:bold;color:#0a3d62;}
.kop-brand .sub{font-size:7pt;color:#555;margin-top:2px;}
.kop-brand .satker{font-size:8pt;color:#0a3d62;font-weight:bold;margin-top:1px;}
.kop-meta{text-align:right;vertical-align:middle;font-size:7.5pt;color:#555;}
.kop-meta strong{color:#0a3d62;}
.divisi-badge{display:inline-block;padding:3px 10px;border-radius:4px;font-size:8pt;font-weight:bold;color:#fff;margin-bottom:8px;}
table.tbl{width:100%;border-collapse:collapse;margin-bottom:12px;}
table.tbl thead tr{background:#0a3d62;color:#fff;}
table.tbl th{padding:5px 6px;font-size:7.5pt;text-align:left;border:1px solid #0a3d62;}
table.tbl td{padding:4px 6px;font-size:8pt;border:1px solid #e0e0e0;vertical-align:top;}
table.tbl tr:nth-child(even) td{background:#f7fafc;}
.ttd-tbl{width:100%;border-collapse:collapse;margin-top:18px;}
.ttd-cell{width:33%;text-align:center;font-size:8pt;padding:0 10px;vertical-align:top;}
.ttd-space{height:52px;}
.ttd-sign{height:52px;display:flex;align-items:center;justify-content:center;}
.ttd-sign img{max-height:48px;max-width:140px;object-fit:contain;}
.ttd-line{border-top:1px solid #333;padding-top:3px;font-weight:bold;}
.ttd-sub{font-size:7pt;color:#555;margin-top:2px;}
.pg{text-align:right;font-size:6pt;color:#aaa;margin-top:10px;border-top:1px solid #eee;padding-top:4px;}
</style>
</head>
<body>
<div class="kop">
<table class="kop-tbl">
<tr>
    <td style="width:54px"><div class="kop-logo">📡</div></td>
    <td class="kop-brand">
        <h1>MonOTOn — Monitoring Operasional Terintegrasi Online</h1>
        <div class="sub">REKAP LAPORAN CATATAN EVIDEN / DOKUMENTASI KEGIATAN</div>
        @if($satkerName)<div class="satker">{{ $satkerName }}</div>@endif
    </td>
    <td class="kop-meta">
        @if($tanggal_dari && $tanggal_sampai)
        <div>Periode: <strong>{{ \Carbon\Carbon::parse($tanggal_dari)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($tanggal_sampai)->format('d/m/Y') }}</strong></div>
        @else
        <div>Periode: <strong>Semua Waktu</strong></div>
        @endif
        <div>Divisi: <strong>{{ $divisiLabel }}</strong></div>
        <div>Total Eviden: <strong>{{ $evidens->count() }}</strong></div>
        <div>Dicetak oleh: <strong>{{ $generated_by }}</strong></div>
        <div>Dicetak: <strong>{{ $generated_at }}</strong></div>
    </td>
</tr>
</table>
</div>

@php
    $divisiColor = ['transmisi'=>'#0a3d62','studio'=>'#7b1fa2','sarana'=>'#10ac84'][$divisi] ?? '#555';
@endphp
<div class="divisi-badge" style="background:{{ $divisiColor }}">
    {{ $divisiLabel }}
</div>

<table class="tbl">
<thead>
<tr>
    <th style="width:22px">No</th>
    <th style="width:75px">Tanggal</th>
    <th style="width:55px">Waktu</th>
    <th>Judul Kegiatan</th>
    <th style="width:90px">Lokasi</th>
    <th style="width:90px">Operator</th>
    <th style="width:40px">Foto</th>
</tr>
</thead>
<tbody>
@forelse($evidens as $i => $ev)
<tr>
    <td style="text-align:center">{{ $i+1 }}</td>
    <td style="font-family:monospace;font-size:7.5pt">{{ \Carbon\Carbon::parse($ev->tanggal)->format('d/m/Y') }}</td>
    <td style="font-family:monospace;font-size:7.5pt">{{ substr($ev->jam_mulai,0,5) }} – {{ substr($ev->jam_selesai,0,5) }}</td>
    <td>
        {{ $ev->judul }}
        @if($ev->supervisi)<div style="font-size:7pt;color:#555">Sup: {{ $ev->supervisi }}</div>@endif
    </td>
    <td style="font-size:7.5pt">{{ $ev->lokasi ?? '–' }}</td>
    <td style="font-size:7.5pt">{{ $ev->user->name }}</td>
    <td style="text-align:center;font-family:monospace">{{ $ev->fotos_count ?? 0 }}</td>
</tr>
@empty
<tr><td colspan="7" style="text-align:center;padding:12px;color:#aaa">Tidak ada data</td></tr>
@endforelse
</tbody>
</table>

@php
    $ttdFile = !empty($koordinator['ttd_path']) ? public_path('uploads/'.$koordinator['ttd_path']) : null;
    $kabidFile = !empty($kabid['ttd_path']) ? public_path('uploads/'.$kabid['ttd_path']) : null;
@endphp
<table class="ttd-tbl">
<tr>
    <td class="ttd-cell">
        <div>Mengetahui,</div>
        <div>{{ $koordinator['jabatan'] ?: 'Koordinator '.$divisiLabel }}</div>
        @if($ttdFile && file_exists($ttdFile))
        <div class="ttd-sign"><img src="{{ $koordinator['ttd_url'] }}" alt="TTD"></div>
        @else
        <div class="ttd-space"></div>
        @endif
        <div class="ttd-line">{{ $koordinator['nama'] ?: '( _________________________ )' }}</div>
        @if(!empty($koordinator['nip']))<div class="ttd-sub">NIP. {{ $koordinator['nip'] }}</div>@endif
    </td>
    <td class="ttd-cell">
        {{-- Kepala Bidang Teknik (opsional, muncul jika ada datanya) --}}
        @if(!empty($kabid['nama']))
        <div>Menyetujui,</div>
        <div>{{ $kabid['jabatan'] }}</div>
        @if($kabidFile && file_exists($kabidFile))
        <div class="ttd-sign"><img src="{{ $kabid['ttd_url'] }}" alt="TTD Kabid"></div>
        @else
        <div class="ttd-space"></div>
        @endif
        <div class="ttd-line">{{ $kabid['nama'] }}</div>
        @if(!empty($kabid['nip']))<div class="ttd-sub">NIP. {{ $kabid['nip'] }}</div>@endif
        @endif
    </td>
    <td class="ttd-cell">
        <div>{{ now()->translatedFormat('d F Y') }}</div>
        <div>Dibuat Oleh,</div>
        <div class="ttd-space"></div>
        <div class="ttd-line">{{ $generated_by }}</div>
    </td>
</tr>
</table>
<div class="pg">MonOTOn — Rekap Laporan Eviden | {{ $divisiLabel }}</div>
</body>
</html>
