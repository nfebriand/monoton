<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Catatan Dinas - {{ $c->judul }}</title>
<style>
@page { margin: 1cm; }
body{font-family:'DejaVu Sans',Arial,sans-serif;margin:0;padding:0;font-size:9pt;color:#1a1a1a;}
.kop{width:100%;border-bottom:3px solid #0a3d62;padding-bottom:8px;margin-bottom:12px;}
.kop-tbl{width:100%;border-collapse:collapse;}
.kop-brand h1{font-size:11pt;font-weight:bold;color:#0a3d62;margin:0;}
.kop-brand .sub{font-size:7pt;color:#555;margin-top:2px;}
.kop-brand .satker{font-size:8pt;color:#0a3d62;font-weight:bold;margin-top:2px;}
.kop-meta{text-align:right;vertical-align:middle;font-size:7.5pt;color:#555;}
.kop-meta strong{color:#0a3d62;}
.judul-box{background:#0a3d62;color:#fff;padding:10px 14px;border-radius:6px;margin-bottom:12px;}
.judul-box h2{font-size:12pt;font-weight:bold;margin:0 0 3px;}
.judul-box .sub{font-size:7.5pt;opacity:.85;}
table.info{width:100%;border-collapse:collapse;margin-bottom:12px;}
table.info td{padding:4px 8px;font-size:8.5pt;vertical-align:top;border-bottom:1px solid #f0f0f0;}
table.info td:first-child{color:#555;width:30%;font-weight:600;}
.narasi{background:#f8fafc;border:1px solid #e0e0e0;border-left:4px solid #0a3d62;border-radius:4px;padding:10px 12px;margin-bottom:12px;font-size:9pt;line-height:1.6;white-space:pre-wrap;}
.sec{font-size:8pt;font-weight:bold;color:#0a3d62;margin:8px 0 4px;border-left:3px solid #0a3d62;padding-left:6px;}
.foto-grid{width:100%;border-collapse:collapse;margin-bottom:8px;page-break-inside:avoid;}
.foto-grid td{padding:4px;vertical-align:top;text-align:center;width:33.33%;}
.foto-item{border:1px solid #ddd;border-radius:4px;overflow:hidden;background:#f4f6f8;height:165px;display:table;width:100%;}
.foto-item-inner{display:table-cell;vertical-align:middle;text-align:center;height:165px;}
.foto-item-inner img{max-width:100%;max-height:155px;width:auto;height:auto;display:inline-block;}
.foto-caption{font-size:7pt;color:#444;padding:3px 4px;background:#f0f2f5;text-align:center;border-top:1px solid #e2e6ea;}
.ttd-tbl{width:100%;border-collapse:collapse;margin-top:18px;page-break-inside:avoid;}
.ttd-cell{width:33%;text-align:center;font-size:8pt;padding:0 10px;vertical-align:top;}
.ttd-space{height:55px;}
.ttd-sign{height:55px;}
.ttd-sign img{max-height:50px;max-width:140px;}
.ttd-line{border-top:1px solid #333;padding-top:3px;font-weight:bold;}
.ttd-sub{font-size:7pt;color:#555;margin-top:2px;}
.pg{text-align:right;font-size:6.5pt;color:#aaa;margin-top:10px;border-top:1px solid #eee;padding-top:4px;}
</style>
</head>
<body>
<div class="kop">
<table class="kop-tbl"><tr>
    <td class="kop-brand">
        <h1>MonOTOn - Monitoring Operasional Terintegrasi Online</h1>
        <div class="sub">CATATAN DINAS / NARASI KEJADIAN, KONDISI &amp; SITUASI</div>
        @if($satkerName)<div class="satker">{{ $satkerName }}</div>@endif
    </td>
    <td class="kop-meta">
        <div>Tanggal: <strong>{{ $c->tanggal->format('d/m/Y') }}</strong></div>
        @if($c->rentang_jam)<div>Waktu: <strong>{{ $c->rentang_jam }}</strong></div>@endif
        <div>Dicetak: <strong>{{ now()->format('d/m/Y H:i') }}</strong></div>
    </td>
</tr></table>
</div>

<div class="judul-box">
    <h2>{{ $c->judul }}</h2>
    <div class="sub">{{ $c->kategori_label }} &nbsp;|&nbsp; {{ $c->tanggal->translatedFormat('l, d F Y') }}@if($c->lokasi) &nbsp;|&nbsp; {{ $c->lokasi }}@endif</div>
</div>

<table class="info">
<tr><td>Jenis Catatan</td><td>{{ $c->kategori_label }}</td></tr>
<tr><td>Tanggal</td><td>{{ $c->tanggal->translatedFormat('l, d F Y') }}@if($c->rentang_jam), pukul {{ $c->rentang_jam }}@endif</td></tr>
@if($c->lokasi)<tr><td>Lokasi</td><td>{{ $c->lokasi }}</td></tr>@endif
<tr><td>Petugas</td><td>{{ $c->user->name }}{{ $c->user->nip ? ' (NIP. '.$c->user->nip.')' : '' }}</td></tr>
@if($c->fotos->count())<tr><td>Jumlah Foto</td><td>{{ $c->fotos->count() }} foto</td></tr>@endif
</table>

<div class="sec">Narasi</div>
<div class="narasi">{{ $c->narasi }}</div>

@if($c->fotos->isNotEmpty())
<div class="sec">Dokumentasi Foto ({{ $c->fotos->count() }} foto)</div>
@foreach($c->fotos->chunk(3) as $row)
<table class="foto-grid"><tr>
    @foreach($row as $foto)
    @php
        $rel = $foto->thumb_path ?: $foto->path; $b64 = '';
        if ($rel && \Illuminate\Support\Facades\Storage::disk('public')->exists($rel)) {
            $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg');
            $b64 = 'data:'.$mime.';base64,'.base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($rel));
        }
    @endphp
    <td>
        <div class="foto-item"><div class="foto-item-inner">
            @if($b64)<img src="{{ $b64 }}" alt="Foto">@else<span style="color:#aaa;font-size:7pt">Foto tidak tersedia</span>@endif
        </div></div>
        <div class="foto-caption">{{ $foto->keterangan ?: '-' }}</div>
    </td>
    @endforeach
    @for($i = 0; $i < 3 - count($row); $i++)<td></td>@endfor
</tr></table>
@endforeach
@endif

<table class="ttd-tbl"><tr>
    <td class="ttd-cell">
        <div>Mengetahui,</div>
        <div>{{ $koordinator['jabatan'] ?: \App\Models\AppSetting::JABATAN_DEFAULT['transmisi'] }}</div>
        @if(!empty($koordinator['ttd_base64']))<div class="ttd-sign"><img src="{{ $koordinator['ttd_base64'] }}" alt="TTD"></div>@else<div class="ttd-space"></div>@endif
        <div class="ttd-line">{{ $koordinator['nama'] ?: '( _________________________ )' }}</div>
        @if(!empty($koordinator['nip']))<div class="ttd-sub">NIP. {{ $koordinator['nip'] }}</div>@endif
    </td>
    <td class="ttd-cell"></td>
    <td class="ttd-cell">
        <div>{{ $c->tanggal->translatedFormat('d F Y') }}</div>
        <div>Dibuat Oleh,</div>
        @php $ttdU = $c->user->ttd_base64 ?? null; @endphp
        @if($ttdU)<div class="ttd-sign"><img src="{{ $ttdU }}" alt="TTD"></div>@else<div class="ttd-space"></div>@endif
        <div class="ttd-line">{{ $c->user->name }}</div>
        @if($c->user->nip)<div class="ttd-sub">NIP. {{ $c->user->nip }}</div>@endif
    </td>
</tr></table>
<div class="pg">MonOTOn v{{ $appVersion }}{{ $satkerName ? ' | '.$satkerName : '' }}</div>
</body>
</html>
