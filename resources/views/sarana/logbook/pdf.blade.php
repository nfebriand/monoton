<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DejaVu Sans',sans-serif;font-size:9pt;color:#222;}
.page{padding:1cm;}
.hd{width:100%;border:1px solid #ccc;border-collapse:collapse;margin-bottom:8px;}
.hd td{padding:8px 10px;vertical-align:middle;}
.hd .logo{width:70px;text-align:center;border-right:1px solid #ccc;font-weight:bold;color:#0a3d62;font-size:8pt;}
.hd h3{font-size:11pt;}
.hd p{font-size:8.5pt;margin-top:2px;}
table.cl{width:100%;border-collapse:collapse;}
table.cl th{background:#4a90d9;color:#fff;padding:5px 8px;font-size:8pt;text-align:center;border:1px solid #ccc;}
table.cl td{padding:5px 8px;font-size:8pt;border:1px solid #ccc;vertical-align:top;}
.sub td{background:#f39c12;color:#fff;font-weight:bold;text-align:center;}
.ok{color:#27ae60;font-weight:bold;}.bad{color:#e74c3c;font-weight:bold;}
.cat{border:1px solid #ccc;background:#fffde7;padding:6px 8px;margin-top:8px;font-size:8.5pt;white-space:pre-wrap;}
.cat b{display:block;margin-bottom:3px;}
.ttd{width:100%;border-collapse:collapse;margin-top:16px;page-break-inside:avoid;}
.ttd td{text-align:center;vertical-align:top;font-size:8.5pt;padding:0 10px;width:50%;}
.sp{height:50px;}.sg{height:50px;}.sg img{max-height:48px;max-width:140px;}
.nm{font-weight:bold;border-top:1px solid #333;display:inline-block;min-width:160px;padding-top:2px;margin-top:2px;}
.fg{width:100%;border-collapse:collapse;margin-top:8px;page-break-inside:avoid;}
.fg td{width:33.33%;padding:3px;text-align:center;vertical-align:top;}
.fg .box{border:1px solid #ccc;height:120px;display:table;width:100%;}
.fg .in{display:table-cell;vertical-align:middle;text-align:center;}
.fg img{max-width:100%;max-height:112px;}
.cap{font-size:7pt;color:#444;border:1px solid #ccc;border-top:0;padding:2px;background:#f4f4f4;}
.ft{margin-top:8px;font-size:7pt;color:#888;text-align:right;}
</style></head><body>
@php
    $logoB64 = '';
    $logoRel = \App\Models\AppSetting::logoInstansiPath();
    if ($logoRel && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoRel)) {
        $ext = strtolower(pathinfo($logoRel, PATHINFO_EXTENSION));
        $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
        $logoB64 = 'data:'.$mime.';base64,'.base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($logoRel));
    }
@endphp
@foreach($logs as $log)
@php $p = $pengelola[$log->id] ?? []; @endphp
<div class="page" style="{{ !$loop->first ? 'page-break-before:always;' : '' }}">
<table class="hd"><tr>
    <td class="logo">@if($logoB64)<img src="{{ $logoB64 }}" style="max-width:60px;max-height:60px">@endif</td>
    <td>
        <h3>Logbook Laporan Harian Teknik Sarana dan Prasarana</h3>
        <p>Periode Laporan ({{ $log->tanggal->translatedFormat('F Y') }})</p>
        <p>{{ $settings['satuan_kerja'] ?? 'LPP RRI Bandarlampung' }}</p>
    </td>
</tr></table>

<table class="cl">
    <thead><tr><th style="width:42%">TANGGAL</th><th style="width:28%">SHIFT</th><th>NAMA PETUGAS</th></tr></thead>
    <tbody>
    <tr>
        <td style="text-align:center;font-weight:bold">{{ $log->tanggal->translatedFormat('l, j F Y') }}</td>
        <td style="text-align:center;font-weight:bold">{{ $log->shift_label }} ({{ substr($log->jam_mulai,0,5) }}{{ $log->jam_selesai ? ' - '.substr($log->jam_selesai,0,5) : '' }})</td>
        <td style="text-align:center;font-weight:bold">{{ $log->user->name }}</td>
    </tr>
    <tr class="sub"><td>Log Harian</td><td>Aksi</td><td>Keterangan</td></tr>
    @foreach($log->items as $it)
    <tr style="{{ $it->kondisi === 'kendala' ? 'background:#fff5f5' : '' }}">
        <td><strong>{{ $it->label }}</strong>@if($it->deskripsi)<br><span style="font-size:7.5pt;color:#666">*{{ $it->deskripsi }}</span>@endif</td>
        <td style="text-align:center" class="{{ $it->kondisi === 'kendala' ? 'bad' : 'ok' }}">{{ $it->teks_kondisi }}</td>
        <td>{{ $it->keterangan }}</td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="cat"><b>Catatan Petugas Dinas <span style="font-weight:normal;font-size:7.5pt">(untuk petugas berikutnya)</span></b>{{ $log->catatan_petugas ?: '-' }}</div>

@if($log->fotos->isNotEmpty())
@foreach($log->fotos->chunk(3) as $row)
<table class="fg"><tr>
    @foreach($row as $f)
    @php
        $rel = $f->thumb_path ?: $f->path; $b64 = '';
        if ($rel && \Illuminate\Support\Facades\Storage::disk('public')->exists($rel)) {
            $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg');
            $b64 = 'data:'.$mime.';base64,'.base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($rel));
        }
    @endphp
    <td><div class="box"><div class="in">@if($b64)<img src="{{ $b64 }}">@else<span style="color:#aaa;font-size:7pt">Foto tidak tersedia</span>@endif</div></div><div class="cap">{{ $f->keterangan ?: '-' }}</div></td>
    @endforeach
    @for($i = 0; $i < 3 - count($row); $i++)<td></td>@endfor
</tr></table>
@endforeach
@endif

<table class="ttd"><tr>
    <td>
        <div>Pengelola Teknik Sarana &amp; Prasarana</div>
        @if(!empty($p['ttd_base64']))<div class="sg"><img src="{{ $p['ttd_base64'] }}"></div>@else<div class="sp"></div>@endif
        <div class="nm">{{ $p['nama'] ?? '' ?: '( ____________________ )' }}</div>
        @if(!empty($p['nip']))<div style="font-size:7pt;color:#555">NIP. {{ $p['nip'] }}</div>@endif
    </td>
    <td>
        <div>Petugas Yang Berdinas</div>
        @php $ttdU = $log->user->ttd_base64 ?? null; @endphp
        @if($ttdU)<div class="sg"><img src="{{ $ttdU }}"></div>@else<div class="sp"></div>@endif
        <div class="nm">{{ $log->user->name }}</div>
        @if($log->user->nip)<div style="font-size:7pt;color:#555">NIP. {{ $log->user->nip }}</div>@endif
    </td>
</tr></table>
<div class="ft">MonOTOn | {{ $settings['satuan_kerja'] ?? 'LPP RRI Bandarlampung' }}</div>
</div>
@endforeach
</body></html>
