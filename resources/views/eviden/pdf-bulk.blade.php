<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Bulk Laporan Eviden</title>
<style>
@page {
        /* Mengatur margin atas, kanan, bawah, kiri sebesar 1cm */
        margin: 1cm; 
    }
body{font-family:'DejaVu Sans',Arial,sans-serif;font-size:9pt;color:#1a1a1a;}

/* Page break antar eviden */
.eviden-page { page-break-after: always; }
.eviden-page:last-child { page-break-after: avoid; }

/* Cover page */
.cover { text-align:center; padding-top:3cm; }
.cover h1 { font-size:18pt; color:#0a3d62; margin-bottom:8px; }
.cover h2 { font-size:12pt; color:#555; font-weight:normal; margin-bottom:20px; }
.cover .meta { font-size:9pt; color:#777; line-height:1.8; }
.cover .divider { border-top:3px solid #0a3d62; margin:20px auto; width:60%; }

/* KOP */
.kop{width:100%;border-bottom:3px solid #0a3d62;padding-bottom:8px;margin-bottom:10px;}
.kop-tbl{width:100%;border-collapse:collapse;}
.kop-logo{width:50px;height:50px;background:#0a3d62;border-radius:8px;text-align:center;line-height:50px;font-size:18pt;color:#00d2d3;}
.kop-brand{padding-left:8px;vertical-align:middle;}
.kop-brand h1{font-size:10pt;font-weight:bold;color:#0a3d62;margin:0;}
.kop-brand .sub{font-size:7pt;color:#555;margin-top:1px;}
.kop-brand .satker{font-size:8pt;color:#0a3d62;font-weight:bold;margin-top:1px;}
.kop-meta{text-align:right;vertical-align:middle;font-size:7.5pt;color:#555;}
.kop-meta strong{color:#0a3d62;}

/* Judul eviden */
.judul-box{background:#0a3d62;color:#fff;padding:8px 12px;border-radius:5px;margin-bottom:8px;}
.judul-box h2{font-size:11pt;font-weight:bold;margin:0 0 2px;}
.judul-box .sub{font-size:7pt;opacity:.85;}

/* Info table */
table.info{width:100%;border-collapse:collapse;margin-bottom:8px;}
table.info td{padding:3px 6px;font-size:8pt;vertical-align:top;}
table.info td:first-child{color:#555;width:33%;font-weight:600;}
table.info tr{border-bottom:1px solid #f0f0f0;}

/* Operator table */
.op-tbl{width:100%;border-collapse:collapse;margin-bottom:8px;}
.op-tbl th{background:#0a3d62;color:#fff;padding:4px 6px;text-align:left;font-size:7.5pt;}
.op-tbl td{padding:3px 6px;font-size:7.5pt;border:1px solid #e0e0e0;}
.op-tbl tr:nth-child(even) td{background:#f7fafc;}

/* Deskripsi */
.deskripsi-box{background:#f8fafc;border:1px solid #e0e0e0;border-left:4px solid #0a3d62;
               border-radius:4px;padding:8px 10px;margin-bottom:8px;
               font-size:8pt;line-height:1.5;white-space:pre-wrap;}

/* Foto grid */
.foto-grid{width:100%;border-collapse:collapse;margin-bottom:6px;}
.foto-grid td{padding:3px;vertical-align:top;text-align:center;width:33.33%;}
.foto-item{border:1px solid #ddd;border-radius:3px;background:#f4f6f8;height:150px;display:table;width:100%;}
.foto-item-inner{display:table-cell;vertical-align:middle;text-align:center;height:150px;}
.foto-item-inner img{max-width:100%;max-height:140px;width:auto;height:auto;display:inline-block;}
.foto-caption{font-size:6pt;color:#555;padding:2px 3px;background:#f0f2f5;text-align:center;border-top:1px solid #e2e6ea;}

/* TTD */
.ttd-tbl{width:100%;border-collapse:collapse;margin-top:12px;}
.ttd-cell{width:33%;text-align:center;font-size:8pt;padding:0 8px;vertical-align:top;}
.ttd-space{height:45px;}
.ttd-line{border-top:1px solid #333;padding-top:3px;font-weight:bold;font-size:8pt;}
.ttd-sub{font-size:7pt;color:#555;margin-top:1px;}

/* Nomor halaman dan footer */
.eviden-footer{font-size:6.5pt;color:#aaa;margin-top:6px;border-top:1px solid #eee;padding-top:3px;text-align:right;}

/* Section label */
.section-label{font-size:8pt;font-weight:bold;color:#0a3d62;margin-bottom:4px;border-left:3px solid #0a3d62;padding-left:6px;}

/* Nomor eviden badge */
.eviden-no{display:inline-block;background:#0a3d62;color:#fff;font-size:7pt;
           padding:2px 7px;border-radius:10px;margin-bottom:6px;}
</style>
</head>
<body>

{{-- ══ COVER PAGE ══ --}}
<div class="eviden-page">
    <div class="cover">
        <div style="font-size:32pt;margin-bottom:12px;">📋</div>
        <h1>Laporan Bulk Eviden</h1>
        <h2>Rekap Catatan Eviden / Dokumentasi Kegiatan</h2>
        @if(!empty($settings['satuan_kerja']))
        <div style="font-size:11pt;font-weight:bold;color:#0a3d62;margin-bottom:8px">{{ $settings['satuan_kerja'] }}</div>
        @endif
        <div class="divider"></div>
        <div class="meta">
            <div>Total Eviden: <strong>{{ $evidens->count() }}</strong></div>
            @php
                $tglMulai = $evidens->min('tanggal');
                $tglAkhir = $evidens->max('tanggal');
            @endphp
            <div>Periode: <strong>{{ \Carbon\Carbon::parse($tglMulai)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($tglAkhir)->format('d/m/Y') }}</strong></div>
            <div>Dicetak: <strong>{{ now()->translatedFormat('l, d F Y H:i') }}</strong></div>
            <div>Dicetak oleh: <strong>{{ auth()->user()->name }}</strong></div>
        </div>

        {{-- Daftar isi ringkas --}}
        <div style="margin-top:30px;text-align:left;max-width:80%;margin-left:auto;margin-right:auto;">
            <div style="font-size:9pt;font-weight:bold;color:#0a3d62;border-bottom:1px solid #0a3d62;padding-bottom:4px;margin-bottom:8px;">
                Daftar Isi
            </div>
            @foreach($evidens as $i => $ev)
            <div style="font-size:7.5pt;padding:2px 0;border-bottom:1px dotted #eee;display:flex;justify-content:space-between">
                <span>{{ $i+1 }}. {{ $ev->judul }}</span>
                <span style="color:#888">{{ \Carbon\Carbon::parse($ev->tanggal)->format('d/m/Y') }}</span>
            </div>
            @endforeach
        </div>

        <div style="margin-top:40px;font-size:7.5pt;color:#aaa">
            MonOTOn v{{ $settings['app_version'] ?? '1.0.0' }} — Monitoring Operasional Transmisi Online
        </div>
    </div>
</div>

{{-- ══ HALAMAN PER EVIDEN ══ --}}
@foreach($evidens as $i => $eviden)
@php
    $koordinator = $koordinatorCache[$eviden->divisi ?? 'transmisi'] ?? $koordinatorCache['transmisi'];
@endphp
<div class="eviden-page">

    {{-- KOP --}}
    <div class="kop">
        <table class="kop-tbl">
        <tr>
            <td style="width:58px"><div class="kop-logo">📡</div></td>
            <td class="kop-brand">
                <h1>MonOTOn — Monitoring Operasional Transmisi Online</h1>
                <div class="sub">LAPORAN CATATAN EVIDEN / DOKUMENTASI KEGIATAN</div>
                @if(!empty($settings['satuan_kerja']))<div class="satker">{{ $settings['satuan_kerja'] }}</div>@endif
            </td>
            <td class="kop-meta">
                <div>Tanggal: <strong>{{ \Carbon\Carbon::parse($eviden->tanggal)->format('d/m/Y') }}</strong></div>
                <div>Waktu: <strong>{{ substr($eviden->jam_mulai,0,5) }}–{{ substr($eviden->jam_selesai,0,5) }}</strong></div>
                @if(!empty($eviden->divisi))
                <div>Divisi: <strong>{{ \App\Models\User::DIVISI_LABEL[$eviden->divisi] ?? ucfirst($eviden->divisi) }}</strong></div>
                @endif
                <div>Eviden ke-: <strong>{{ $i+1 }} / {{ $evidens->count() }}</strong></div>
            </td>
        </tr>
        </table>
    </div>

    {{-- Nomor urut --}}
    <span class="eviden-no">Eviden #{{ $i+1 }}</span>

    {{-- Judul --}}
    <div class="judul-box">
        <h2>{{ $eviden->judul }}</h2>
        <div class="sub">
            {{ \Carbon\Carbon::parse($eviden->tanggal)->translatedFormat('l, d F Y') }}
            &nbsp;|&nbsp; {{ substr($eviden->jam_mulai,0,5) }}–{{ substr($eviden->jam_selesai,0,5) }}
            @if($eviden->lokasi) &nbsp;|&nbsp; 📍 {{ $eviden->lokasi }} @endif
        </div>
    </div>

    {{-- Info --}}
    <table class="info">
        <tr><td>Tanggal</td><td>{{ \Carbon\Carbon::parse($eviden->tanggal)->translatedFormat('l, d F Y') }}</td></tr>
        <tr><td>Waktu</td><td>{{ substr($eviden->jam_mulai,0,5) }}–{{ substr($eviden->jam_selesai,0,5) }} ({{ $eviden->durasi }})</td></tr>
        @if($eviden->lokasi)<tr><td>Lokasi</td><td>{{ $eviden->lokasi }}</td></tr>@endif
        <tr><td>Dibuat Oleh</td><td>{{ $eviden->user->name }}{{ $eviden->user->nip ? ' (NIP. '.$eviden->user->nip.')' : '' }}</td></tr>
        @if($eviden->supervisi)<tr><td>Supervisi</td><td><strong>{{ $eviden->supervisi }}</strong></td></tr>@endif
        <tr><td>Jumlah Foto</td><td>{{ $eviden->fotos->count() }} foto</td></tr>
    </table>

    {{-- Operator terlibat --}}
    @if($eviden->operators->isNotEmpty())
    <div class="section-label">Personil Terlibat</div>
    <table class="op-tbl">
        <thead><tr><th style="width:25px">No</th><th style="width:35%">Nama</th><th style="width:20%">NIP</th><th>Tanda Tangan</th></tr></thead>
        <tbody>
        @foreach($eviden->operators as $j => $op)
        <tr>
            <td style="text-align:center;width:30px">{{ $j+1 }}</td>
            <td>{{ $op->name }}</td>
            <td>{{ $op->nip ?? '–' }}</td>
            <td style="text-align:center;padding:5px 6px;vertical-align:middle">
                @php
                    $opTtdPath = $op->ttd_path ? storage_path('app/public/' . $op->ttd_path) : null;
                    $opTtdB64  = '';
                    if ($opTtdPath && file_exists($opTtdPath)) {
                        $ext = strtolower(pathinfo($opTtdPath, PATHINFO_EXTENSION));
                        $mime = match($ext) { 'png'=>'image/png','webp'=>'image/webp',default=>'image/jpeg' };
                        $opTtdB64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($opTtdPath));
                    }
                @endphp
                @if($opTtdB64)
                <img src="{{ $opTtdB64 }}" style="max-height:38px;max-width:110px;object-fit:contain">
                @else
                <span style="color:#ccc;font-size:6.5pt">Belum ada TTD</span>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif

    {{-- Deskripsi --}}
    @if($eviden->deskripsi)
    <div class="section-label">Uraian Kegiatan</div>
    <div class="deskripsi-box">{{ $eviden->deskripsi }}</div>
    @endif

    {{-- Foto --}}
    @if($eviden->fotos->isNotEmpty())
    <div class="section-label">Dokumentasi Foto ({{ $eviden->fotos->count() }} foto)</div>
    @php $fotosChunked = $eviden->fotos->chunk(3); @endphp
    @foreach($fotosChunked as $row)
    <table class="foto-grid">
    <tr>
        @foreach($row as $foto)
        @php
            $fotoRel    = $foto->thumb_path ?: $foto->path;
            $fotoBase64 = '';
            if ($fotoRel && \Illuminate\Support\Facades\Storage::disk('public')->exists($fotoRel)) {
                $ext  = strtolower(pathinfo($fotoRel, PATHINFO_EXTENSION));
                $mime = match($ext) { 'png'=>'image/png','webp'=>'image/webp',default=>'image/jpeg' };
                $fotoBase64 = 'data:'.$mime.';base64,'.base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($fotoRel));
            }
        @endphp
        <td>
            <div class="foto-item">
                <div class="foto-item-inner">
                    @if($fotoBase64)
                    <img src="{{ $fotoBase64 }}" alt="Foto">
                    @else
                    <span style="color:#aaa;font-size:7pt">Foto tidak<br>tersedia</span>
                    @endif
                </div>
            </div>
            <div class="foto-caption">{{ $foto->keterangan ?: 'Foto '.($loop->iteration) }}</div>
        </td>
        @endforeach
        @for($p=0;$p<(3-count($row));$p++)<td></td>@endfor
    </tr>
    </table>
    @endforeach
    @endif

    {{-- TTD --}}
    <table class="ttd-tbl">
    <tr>
        <td class="ttd-cell">
            <div>Mengetahui,</div>
            <div>{{ $koordinator['jabatan'] ?: 'Koordinator Teknik' }}</div>
            @if(!empty($koordinator['ttd_base64']))
            <div style="height:45px;display:flex;align-items:center;justify-content:center">
                <img src="{{ $koordinator['ttd_base64'] }}" alt="TTD" style="max-height:40px;max-width:120px">
            </div>
            @else
            <div class="ttd-space"></div>
            @endif
            <div class="ttd-line">{{ $koordinator['nama'] ?: '( __________________ )' }}</div>
            @if(!empty($koordinator['nip']))<div class="ttd-sub">NIP. {{ $koordinator['nip'] }}</div>@endif
        </td>
        <td class="ttd-cell"></td>
        <td class="ttd-cell">
            <div>{{ \Carbon\Carbon::parse($eviden->tanggal)->translatedFormat('d F Y') }}</div>
            <div>Dibuat Oleh,</div>
            @php $pembuatTtd = $eviden->user->ttd_base64 ?? null; @endphp
            @if($pembuatTtd)
            <div style="height:45px;display:flex;align-items:center;justify-content:center">
                <img src="{{ $pembuatTtd }}" alt="TTD" style="max-height:40px;max-width:120px;object-fit:contain">
            </div>
            @else
            <div class="ttd-space"></div>
            @endif
            <div class="ttd-line">{{ $eviden->user->name }}</div>
            @if($eviden->user->nip)<div class="ttd-sub">NIP. {{ $eviden->user->nip }}</div>@endif
        </td>
    </tr>
    </table>

    <div class="eviden-footer">
        MonOTOn v{{ $settings['app_version'] ?? '1.0.0' }}
        {{ !empty($settings['satuan_kerja']) ? '| '.$settings['satuan_kerja'] : '' }}
        | Halaman {{ $i+1 }} dari {{ $evidens->count() }} eviden
        | Dicetak: {{ now()->format('d/m/Y H:i') }}
    </div>

</div>
@endforeach

</body>
</html>
