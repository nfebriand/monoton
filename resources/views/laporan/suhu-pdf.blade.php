<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Suhu — MonOTOn</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DejaVu Sans',Arial,sans-serif;font-size:8.5pt;color:#1a1a1a;}
.kop{width:100%;border-bottom:3px solid #0a3d62;padding-bottom:8px;margin-bottom:10px;}
.kop-tbl{width:100%;border-collapse:collapse;}
.kop-logo{width:46px;height:46px;background:#0a3d62;border-radius:7px;text-align:center;line-height:46px;font-size:16pt;color:#00d2d3;}
.kop-brand{padding-left:8px;vertical-align:middle;}
.kop-brand h1{font-size:11pt;font-weight:bold;color:#0a3d62;}
.kop-brand .sub{font-size:7pt;color:#555;margin-top:2px;}
.kop-brand .satker{font-size:7.5pt;color:#0a3d62;font-weight:bold;margin-top:1px;}
.kop-meta{text-align:right;vertical-align:middle;font-size:7pt;color:#555;}
.kop-meta strong{color:#0a3d62;}
.stat-tbl{width:100%;border-collapse:separate;border-spacing:4px;margin-bottom:10px;}
.stat-cell{border:1.5px solid #dde;border-radius:5px;padding:5px 8px;text-align:center;}
.stat-lbl{font-size:6pt;color:#888;text-transform:uppercase;letter-spacing:.4px;}
.stat-val{font-size:13pt;font-weight:bold;color:#0a3d62;}
table.suhu{width:100%;border-collapse:collapse;font-size:8pt;margin-bottom:14px;}
table.suhu thead tr{background:#0a3d62;color:#fff;}
table.suhu th{padding:5px 6px;text-align:center;border:1px solid #0a3d62;font-size:7.5pt;}
table.suhu td{padding:3.5px 6px;border:1px solid #e0e0e0;}
table.suhu tr:nth-child(even) td{background:#f7fafc;}
.tc{text-align:center;} .tl{text-align:left;}
.ok{color:#10ac84;font-weight:bold;} .wrn{color:#e67e22;font-weight:bold;} .bad{color:#e74c3c;font-weight:bold;}
.ket{font-size:7pt;margin-bottom:12px;color:#555;}
.ttd-tbl{width:100%;border-collapse:collapse;margin-top:16px;}
.ttd-cell{width:33%;text-align:center;font-size:7.5pt;padding:0 8px;vertical-align:top;}
.ttd-space{height:52px;}
.ttd-line{border-top:1px solid #333;padding-top:3px;}
.ttd-nip{font-size:6.5pt;color:#555;margin-top:2px;}
.pg{text-align:right;font-size:6pt;color:#aaa;margin-top:8px;border-top:1px solid #eee;padding-top:4px;}
</style>
</head>
<body>

<!-- KOP -->
<div class="kop">
<table class="kop-tbl">
<tr>
    <td style="width:54px"><div class="kop-logo">📡</div></td>
    <td class="kop-brand">
        <h1>MonOTOn — Monitoring Operasional Transmisi Online</h1>
        <div class="sub">LAPORAN MONITORING SUHU & KELEMBABAN BULANAN</div>
        @if(!empty($satkerName))
        <div class="satker">{{ $satkerName }}</div>
        @endif
    </td>
    <td class="kop-meta">
        <div>Periode: <strong>{{ $bulanLabel }}</strong></div>
        @if(isset($pemancar) && $pemancar)
        <div>Pemancar: <strong>{{ $pemancar->nama_stasiun }}</strong></div>
        @else
        <div>Pemancar: <strong>Semua Pemancar</strong></div>
        @endif
        @if(isset($lokasi) && $lokasi)
        <div>Lokasi: <strong>{{ $lokasi }}</strong></div>
        @endif
        <div>Dicetak: <strong>{{ now()->format('d/m/Y H:i') }}</strong></div>
    </td>
</tr>
</table>
</div>

<!-- STATISTIK -->
@php
    $logSuhu  = $logs->whereNotNull('suhu_ruangan');
    $avgRuang = round($logSuhu->avg('suhu_ruangan'),1);
    $maxRuang = $logSuhu->max('suhu_ruangan');
    $minRuang = $logSuhu->min('suhu_ruangan');
    $avgRH    = round($logs->whereNotNull('kelembaban')->avg('kelembaban'),1);
@endphp
<table class="stat-tbl">
<tr>
    <td class="stat-cell"><div class="stat-lbl">Rata-rata Suhu Ruang</div><div class="stat-val">{{ $avgRuang ?? '–' }}°C</div></td>
    <td class="stat-cell"><div class="stat-lbl">Suhu Tertinggi</div><div class="stat-val" style="color:#ee5a24">{{ $maxRuang ?? '–' }}°C</div></td>
    <td class="stat-cell"><div class="stat-lbl">Suhu Terendah</div><div class="stat-val" style="color:#10ac84">{{ $minRuang ?? '–' }}°C</div></td>
    <td class="stat-cell"><div class="stat-lbl">Rata-rata Kelembaban</div><div class="stat-val" style="color:#ff9f43">{{ $avgRH ?: '–' }}%</div></td>
    <td class="stat-cell"><div class="stat-lbl">Total Pencatatan</div><div class="stat-val">{{ $logs->count() }}</div></td>
</tr>
</table>

<!-- TABEL DATA -->
<table class="suhu">
<thead>
<tr>
    <th style="width:28px">No</th>
    <th style="width:85px">Tgl & Waktu</th>
    <th>Pemancar</th>
    <th style="width:55px">Lokasi</th>
    <th>Operator</th>
    <th style="width:40px">Shift</th>
    <th style="width:65px">Suhu Ruang</th>
    <th style="width:65px">Suhu Pmcr</th>
    <th style="width:50px">RH (%)</th>
    <th>Keterangan</th>
</tr>
</thead>
<tbody>
@forelse($logs as $i => $log)
@php
    $sr=$log->suhu_ruangan;
    $sc=$sr!==null?($sr>30?'bad':($sr>27?'wrn':'ok')):'';
@endphp
<tr>
    <td class="tc">{{ $i+1 }}</td>
    <td class="tc" style="font-family:monospace;font-size:7.5pt">{{ $log->dicatat_pada->format('d/m/y H:i') }}</td>
    <td class="tl">{{ $log->pemancar->nama_stasiun }}</td>
    <td class="tc" style="font-size:6.5pt">{{ $log->pemancar->lokasi ?? '–' }}</td>
    <td class="tl">{{ $log->user->name }}</td>
    <td class="tc">{{ $log->jadwalShift ? 'S'.$log->jadwalShift->shift : '–' }}</td>
    <td class="tc {{ $sc }}" style="font-family:monospace">{{ $sr!==null?$sr.'°C':'–' }}</td>
    <td class="tc" style="font-family:monospace">{{ $log->suhu_pemancar!==null?$log->suhu_pemancar.'°C':'–' }}</td>
    <td class="tc" style="font-family:monospace">{{ $log->kelembaban!==null?$log->kelembaban.'%':'–' }}</td>
    <td class="tl" style="font-size:7pt">{{ \Illuminate\Support\Str::limit($log->keterangan,30) }}</td>
</tr>
@empty
<tr><td colspan="10" class="tc" style="padding:12px;color:#aaa">Tidak ada data suhu pada periode ini</td></tr>
@endforelse
</tbody>
</table>

<div class="ket">
    Keterangan warna suhu ruang:
    <span class="ok">■ Normal (≤27°C)</span> &nbsp;
    <span class="wrn">■ Hangat (27–30°C)</span> &nbsp;
    <span class="bad">■ Panas (&gt;30°C)</span>
</div>

<!-- TANDA TANGAN -->
<table class="ttd-tbl">
<tr>
    <td class="ttd-cell">
        <div>Mengetahui,</div>
        <div>Koordinator / Pengelola</div>
        <div class="ttd-space"></div>
        <div class="ttd-line">
            <strong>{{ !empty($koordinatorName) ? $koordinatorName : '( _________________________ )' }}</strong>
        </div>
        @if(!empty($koordinatorName))
        <div class="ttd-nip">Koordinator Teknik</div>
        @endif
    </td>
    <td class="ttd-cell"></td>
    <td class="ttd-cell">
        <div>{{ now()->translatedFormat('d F Y') }}</div>
        <div>Dibuat Oleh,</div>
        <div>Operator Penanggung Jawab</div>
        <div class="ttd-space"></div>
        <div class="ttd-line">
            <strong>{{ $operatorNama }}</strong>
        </div>
        @if(!empty($operatorNip))
        <div class="ttd-nip">NIP. {{ $operatorNip }}</div>
        @endif
    </td>
</tr>
</table>

<div class="pg">MonOTOn — Monitoring Operasional Transmisi Online | Laporan Suhu & Kelembaban | {{ $bulanLabel }}</div>
</body>
</html>
