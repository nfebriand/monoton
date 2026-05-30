<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Eviden — {{ $eviden->judul }}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DejaVu Sans',Arial,sans-serif;font-size:9pt;color:#1a1a1a;}

/* KOP */
.kop{width:100%;border-bottom:3px solid #0a3d62;padding-bottom:8px;margin-bottom:12px;}
.kop-tbl{width:100%;border-collapse:collapse;}
.kop-logo{width:50px;height:50px;background:#0a3d62;border-radius:8px;
    text-align:center;line-height:50px;font-size:18pt;color:#00d2d3;}
.kop-brand{padding-left:8px;vertical-align:middle;}
.kop-brand h1{font-size:11pt;font-weight:bold;color:#0a3d62;margin:0;}
.kop-brand .sub{font-size:7pt;color:#555;margin-top:2px;}
.kop-brand .satker{font-size:8pt;color:#0a3d62;font-weight:bold;margin-top:2px;}
.kop-meta{text-align:right;vertical-align:middle;font-size:7.5pt;color:#555;}
.kop-meta strong{color:#0a3d62;}

/* JUDUL */
.judul-box{background:#0a3d62;color:#fff;padding:10px 14px;border-radius:6px;margin-bottom:12px;}
.judul-box h2{font-size:12pt;font-weight:bold;margin:0 0 3px;}
.judul-box .sub{font-size:7.5pt;opacity:.85;}

/* INFO TABLE */
table.info{width:100%;border-collapse:collapse;margin-bottom:12px;}
table.info td{padding:4px 8px;font-size:8.5pt;vertical-align:top;}
table.info td:first-child{color:#555;width:35%;font-weight:600;}
table.info tr{border-bottom:1px solid #f0f0f0;}

/* DESKRIPSI */
.deskripsi-box{background:#f8fafc;border:1px solid #e0e0e0;border-left:4px solid #0a3d62;
    border-radius:4px;padding:10px 12px;margin-bottom:12px;font-size:8.5pt;
    line-height:1.6;white-space:pre-wrap;}

/* OPERATOR */
.op-tbl{width:100%;border-collapse:collapse;margin-bottom:12px;}
.op-tbl th{background:#0a3d62;color:#fff;padding:5px 8px;text-align:left;font-size:7.5pt;}
.op-tbl td{padding:4px 8px;font-size:8pt;border:1px solid #e0e0e0;}
.op-tbl tr:nth-child(even) td{background:#f7fafc;}

/* FOTO */
.foto-grid{width:100%;border-collapse:collapse;margin-bottom:12px;}
.foto-grid td{padding:4px;vertical-align:top;text-align:center;}
.foto-item{border:1px solid #ddd;border-radius:4px;overflow:hidden;}
.foto-item img{width:100%;max-height:160px;object-fit:cover;display:block;}
.foto-caption{font-size:6.5pt;color:#555;padding:3px 4px;background:#f8f8f8;
    text-align:center;border-top:1px solid #eee;}

/* TTD */
.ttd-tbl{width:100%;border-collapse:collapse;margin-top:18px;}
.ttd-cell{width:33%;text-align:center;font-size:8pt;padding:0 10px;vertical-align:top;}
.ttd-space{height:55px;}
.ttd-line{border-top:1px solid #333;padding-top:3px;font-weight:bold;}
.ttd-sub{font-size:7pt;color:#555;margin-top:2px;}

.pg{text-align:right;font-size:6.5pt;color:#aaa;margin-top:10px;
    border-top:1px solid #eee;padding-top:4px;}
</style>
</head>
<body>

<!-- KOP -->
<div class="kop">
<table class="kop-tbl">
<tr>
    <td style="width:58px"><div class="kop-logo">📡</div></td>
    <td class="kop-brand">
        <h1>MonOTOn — Monitoring Operasional Transmisi Online</h1>
        <div class="sub">LAPORAN CATATAN EVIDEN / DOKUMENTASI KEGIATAN</div>
        @if($satkerName)<div class="satker">{{ $satkerName }}</div>@endif
    </td>
    <td class="kop-meta">
        <div>Tanggal: <strong>{{ \Carbon\Carbon::parse($eviden->tanggal)->format('d/m/Y') }}</strong></div>
        <div>Waktu: <strong>{{ $eviden->jam_mulai }} – {{ $eviden->jam_selesai }}</strong></div>
        <div>Durasi: <strong>{{ $eviden->durasi }}</strong></div>
        <div>Dicetak: <strong>{{ now()->format('d/m/Y H:i') }}</strong></div>
    </td>
</tr>
</table>
</div>

<!-- JUDUL -->
<div class="judul-box">
    <h2>{{ $eviden->judul }}</h2>
    <div class="sub">
        {{ \Carbon\Carbon::parse($eviden->tanggal)->translatedFormat('l, d F Y') }}
        &nbsp;|&nbsp; {{ $eviden->jam_mulai }} – {{ $eviden->jam_selesai }}
        @if($eviden->lokasi) &nbsp;|&nbsp; 📍 {{ $eviden->lokasi }} @endif
    </div>
</div>

<!-- INFO UMUM -->
<table class="info">
<tr>
    <td>Tanggal Kegiatan</td>
    <td>{{ \Carbon\Carbon::parse($eviden->tanggal)->translatedFormat('l, d F Y') }}</td>
</tr>
<tr>
    <td>Waktu Pelaksanaan</td>
    <td>{{ $eviden->jam_mulai }} – {{ $eviden->jam_selesai }} (Durasi: {{ $eviden->durasi }})</td>
</tr>
@if($eviden->lokasi)
<tr>
    <td>Lokasi Kegiatan</td>
    <td>{{ $eviden->lokasi }}</td>
</tr>
@endif
<tr>
    <td>Dibuat / Dilaporkan Oleh</td>
    <td>{{ $eviden->user->name }}{{ $eviden->user->nip ? ' (NIP. '.$eviden->user->nip.')' : '' }}</td>
</tr>
@if($eviden->supervisi)
<tr>
    <td>Supervisi / Pengelola</td>
    <td><strong>{{ $eviden->supervisi }}</strong></td>
</tr>
@endif
<tr>
    <td>Jumlah Foto Dokumentasi</td>
    <td>{{ $eviden->fotos->count() }} foto</td>
</tr>
</table>

<!-- OPERATOR TERLIBAT -->
@if($eviden->operators->isNotEmpty())
<table class="op-tbl">
<thead>
<tr>
    <th>No</th>
    <th>Nama Operator</th>
    <th>NIP</th>
    <th>Lokasi Dinas</th>
</tr>
</thead>
<tbody>
@foreach($eviden->operators as $i => $op)
<tr>
    <td style="text-align:center">{{ $i+1 }}</td>
    <td>{{ $op->name }}</td>
    <td>{{ $op->nip ?? '–' }}</td>
    <td>{{ $op->lokasi_dinas ?? '–' }}</td>
</tr>
@endforeach
</tbody>
</table>
@endif

<!-- DESKRIPSI -->
@if($eviden->deskripsi)
<div style="font-size:8pt;font-weight:bold;color:#0a3d62;margin-bottom:4px;
     border-left:3px solid #0a3d62;padding-left:6px;">
    Uraian Kegiatan
</div>
<div class="deskripsi-box">{{ $eviden->deskripsi }}</div>
@endif

<!-- FOTO DOKUMENTASI -->
@if($eviden->fotos->isNotEmpty())
<div style="font-size:8pt;font-weight:bold;color:#0a3d62;margin-bottom:6px;
     border-left:3px solid #0a3d62;padding-left:6px;">
    Dokumentasi Foto ({{ $eviden->fotos->count() }} foto)
</div>
@php
    $fotosChunked = $eviden->fotos->chunk(3);
@endphp
@foreach($fotosChunked as $row)
<table class="foto-grid">
<tr>
    @foreach($row as $foto)
    @php
        // Konversi path ke base64 untuk DomPDF (lebih reliable daripada URL)
        $fotoPath = storage_path('app/public/' . $foto->path);
        $fotoBase64 = '';
        if (file_exists($fotoPath)) {
            $ext = strtolower(pathinfo($fotoPath, PATHINFO_EXTENSION));
            $mime = $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg');
            $fotoBase64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($fotoPath));
        }
    @endphp
    <td style="width:33%">
        <div class="foto-item">
            @if($fotoBase64)
            <img src="{{ $fotoBase64 }}" alt="Foto {{ $loop->iteration }}">
            @else
            <div style="height:120px;background:#f0f4f8;display:flex;align-items:center;
                 justify-content:center;color:#aaa;font-size:7pt">Foto tidak tersedia</div>
            @endif
            <div class="foto-caption">
                {{ $foto->keterangan ?: 'Foto '.($loop->iteration) }}
            </div>
        </div>
    </td>
    @endforeach
    {{-- Isi kolom kosong jika kurang dari 3 --}}
    @for($i=0; $i < (3 - count($row)); $i++)
    <td style="width:33%"></td>
    @endfor
</tr>
</table>
@endforeach
@endif

<!-- TANDA TANGAN -->
<table class="ttd-tbl">
<tr>
    <td class="ttd-cell">
        <div>Mengetahui,</div>
        <div>Koordinator / Pengelola</div>
        <div class="ttd-space"></div>
        <div class="ttd-line">
            {{ $koordinatorName ?: '( _________________________ )' }}
        </div>
        @if($koordinatorName)
        <div class="ttd-sub">Koordinator Teknik</div>
        @endif
    </td>
    <td class="ttd-cell"></td>
    <td class="ttd-cell">
        <div>{{ \Carbon\Carbon::parse($eviden->tanggal)->translatedFormat('d F Y') }}</div>
        <div>Dibuat Oleh,</div>
        <div class="ttd-space"></div>
        <div class="ttd-line">{{ $eviden->user->name }}</div>
        @if($eviden->user->nip)
        <div class="ttd-sub">NIP. {{ $eviden->user->nip }}</div>
        @endif
    </td>
</tr>
</table>

<div class="pg">MonOTOn v{{ $appVersion }} — Monitoring Operasional Transmisi Online{{ $satkerName?' | '.$satkerName:'' }}</div>

</body>
</html>
