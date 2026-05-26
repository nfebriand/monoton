<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Operasional — MonOTOn</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DejaVu Sans',Arial,sans-serif;font-size:7pt;color:#1a1a1a;}

/* KOP */
.kop{width:100%;border-bottom:3px solid #0a3d62;padding-bottom:7px;margin-bottom:8px;}
.kop-tbl{width:100%;border-collapse:collapse;}
.kop-logo-box{width:46px;height:46px;background:#0a3d62;border-radius:7px;text-align:center;
    line-height:46px;font-size:18pt;font-weight:bold;color:#00d2d3;}
.kop-brand{padding-left:8px;vertical-align:middle;}
.kop-brand h1{font-size:11pt;font-weight:bold;color:#0a3d62;margin:0;}
.kop-brand p{font-size:6.5pt;color:#555;margin:1px 0 0;}
.kop-meta{text-align:right;vertical-align:middle;font-size:6.8pt;color:#555;}
.kop-meta strong{color:#0a3d62;}

/* RINGKASAN */
.ring-tbl{width:100%;border-collapse:separate;border-spacing:4px;margin-bottom:8px;}
.ring-cell{border:1px solid #ddd;border-radius:5px;padding:5px 8px;text-align:center;}
.ring-lbl{font-size:6pt;color:#888;text-transform:uppercase;letter-spacing:.4px;}
.ring-val{font-size:12pt;font-weight:bold;color:#0a3d62;}

/* TABEL LOG */
table.log{width:100%;border-collapse:collapse;}
table.log thead tr{background:#0a3d62;color:#fff;}
table.log th{padding:4px 3px;text-align:center;border:1px solid #0a3d62;font-size:6.2pt;letter-spacing:.2px;}
table.log td{padding:3px 3px;border:1px solid #e0e0e0;vertical-align:middle;}
table.log tr:nth-child(even) td{background:#f7fafc;}
.tc{text-align:center;} .tr{text-align:right;} .tl{text-align:left;}
.ok{color:#10ac84;font-weight:bold;} .wrn{color:#e67e22;font-weight:bold;} .bad{color:#e74c3c;font-weight:bold;}

/* TANDA TANGAN */
.ttd-tbl{width:100%;border-collapse:collapse;margin-top:16px;}
.ttd-cell{width:33%;text-align:center;vertical-align:top;padding:0 10px;font-size:7.5pt;}
.ttd-space{height:55px;}
.ttd-line{border-top:1px solid #333;padding-top:3px;}
.pg{text-align:right;font-size:6pt;color:#aaa;margin-top:8px;border-top:1px solid #eee;padding-top:4px;}
</style>
</head>
<body>

<!-- KOP -->
<div class="kop">
<table class="kop-tbl">
<tr>
    <td style="width:54px"><div class="kop-logo-box">📡</div></td>
    <td class="kop-brand">
        <h1>MonOTOn — Monitoring Operasional Transmisi Online</h1>
        <p>LAPORAN OPERASIONAL PEMANCAR RADIO</p>
    </td>
    <td class="kop-meta">
        <div>Periode: <strong>{{ \Carbon\Carbon::parse($tanggal_dari)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($tanggal_sampai)->format('d/m/Y') }}</strong></div>
        <div>Pemancar: <strong>{{ $summary['pemancar_filter'] ? $summary['pemancar_filter']->nama_stasiun : 'Semua' }}</strong></div>
        <div>Lokasi: <strong>{{ $summary['lokasi_filter'] ?? 'Semua Lokasi' }}</strong></div>
        <div>Operator: <strong>{{ $summary['operator_filter'] ? $summary['operator_filter']->name : 'Semua' }}</strong></div>
        <div>Dicetak: <strong>{{ $generated_at }}</strong></div>
    </td>
</tr>
</table>
</div>

<!-- RINGKASAN -->
<table class="ring-tbl">
<tr>
    <td class="ring-cell"><div class="ring-lbl">Total Log</div><div class="ring-val">{{ $summary['total_pencatatan'] }}</div></td>
    <td class="ring-cell"><div class="ring-lbl">Rata Out Final</div><div class="ring-val" style="font-size:10pt">{{ $summary['rata_output_final'] ? number_format($summary['rata_output_final'],1).'W' : '–' }}</div></td>
    <td class="ring-cell"><div class="ring-lbl">Rata VSWR Final</div><div class="ring-val" style="font-size:10pt">{{ $summary['rata_vswr_final'] ? number_format($summary['rata_vswr_final'],3) : '–' }}</div></td>
    <td class="ring-cell"><div class="ring-lbl">Rata Suhu Ruang</div><div class="ring-val" style="font-size:10pt">{{ $summary['rata_suhu_ruangan'] ? number_format($summary['rata_suhu_ruangan'],1).'°C' : '–' }}</div></td>
    <td class="ring-cell"><div class="ring-lbl">Rata Kelembaban</div><div class="ring-val" style="font-size:10pt">{{ $summary['rata_kelembaban'] ? number_format($summary['rata_kelembaban'],1).'%' : '–' }}</div></td>
</tr>
</table>

<!-- TABEL LOG -->
<table class="log">
<thead>
<tr>
    <th style="width:22px">No</th>
    <th style="width:60px">Tgl & Waktu</th>
    <th style="width:82px">Pemancar</th>
    <th style="width:45px">Lokasi</th>
    <th style="width:62px">Operator</th>
    <th style="width:28px">Shift</th>
    <th style="width:42px">Out Final (W)</th>
    <th style="width:38px">Out Driver (W)</th>
    <th style="width:38px">Out Exciter (W)</th>
    <th style="width:36px">Ref Final (W)</th>
    <th style="width:36px">Rej Final (W)</th>
    <th style="width:40px">VSWR Final</th>
    <th style="width:34px">Suhu Pmcr</th>
    <th style="width:34px">Suhu Rmg</th>
    <th style="width:28px">RH%</th>
    <th>Keterangan</th>
</tr>
</thead>
<tbody>
@forelse($logs as $i => $log)
@php $v=$log->vswr_final; $vc=$v&&$v<=1.5?'ok':($v&&$v<=2?'wrn':'bad'); @endphp
<tr>
    <td class="tc">{{ $i+1 }}</td>
    <td class="tc" style="font-family:monospace">{{ $log->dicatat_pada->format('d/m/y H:i') }}</td>
    <td class="tl">{{ $log->pemancar->nama_stasiun }}</td>
    <td class="tc" style="font-size:6pt">{{ $log->pemancar->lokasi ?? '–' }}</td>
    <td class="tl">{{ $log->user->name }}</td>
    <td class="tc">{{ $log->jadwalShift ? 'S'.$log->jadwalShift->shift : '–' }}</td>
    <td class="tr" style="font-family:monospace">{{ $log->output_final_pa ? number_format($log->output_final_pa,1):'–' }}</td>
    <td class="tr" style="font-family:monospace">{{ $log->output_driver ? number_format($log->output_driver,1):'–' }}</td>
    <td class="tr" style="font-family:monospace">{{ $log->output_exciter ? number_format($log->output_exciter,1):'–' }}</td>
    <td class="tr" style="font-family:monospace">{{ $log->reflect_final ? number_format($log->reflect_final,1):'–' }}</td>
    <td class="tr" style="font-family:monospace">{{ $log->reject_final ? number_format($log->reject_final,1):'–' }}</td>
    <td class="tc {{ $v?$vc:'' }}" style="font-family:monospace">{{ $v?number_format($v,3):'–' }}</td>
    <td class="tc" style="font-family:monospace">{{ $log->suhu_pemancar!==null?$log->suhu_pemancar.'°':'–' }}</td>
    <td class="tc" style="font-family:monospace">{{ $log->suhu_ruangan!==null?$log->suhu_ruangan.'°':'–' }}</td>
    <td class="tc" style="font-family:monospace">{{ $log->kelembaban!==null?$log->kelembaban.'%':'–' }}</td>
    <td class="tl" style="font-size:6.2pt">{{ Str::limit($log->keterangan,32) }}</td>
</tr>
@empty
<tr><td colspan="16" class="tc" style="padding:10px;color:#aaa">Tidak ada data pada periode ini</td></tr>
@endforelse
</tbody>
</table>

<!-- TANDA TANGAN -->
<table class="ttd-tbl">
<tr>
    <td class="ttd-cell">
        <div>Mengetahui,</div>
        <div>Pengelola / Koordinator</div>
        <div class="ttd-space"></div>
        <div class="ttd-line">( {{ $pengelola ?: '________________________' }} )</div>
    </td>
    <td class="ttd-cell"></td>
    <td class="ttd-cell">
        <div>{{ \Carbon\Carbon::parse($tanggal_sampai)->translatedFormat('d F Y') }}</div>
        <div>Dibuat Oleh,</div>
        <div class="ttd-space"></div>
        <div class="ttd-line">( {{ $generated_by }} )</div>
    </td>
</tr>
</table>

<div class="pg">MonOTOn — Monitoring Operasional Transmisi Online | Laporan Operasional Pemancar</div>
</body>
</html>
