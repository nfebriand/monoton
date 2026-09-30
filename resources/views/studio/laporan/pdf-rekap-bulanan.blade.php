<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',sans-serif; font-size:9pt; color:#222; }
.page { padding:1cm; }
.header { text-align:center; margin-bottom:12px; border-bottom:2px solid #333; padding-bottom:8px; }
.header h2 { font-size:13pt; font-weight:bold; text-transform:uppercase; }
.header h3 { font-size:10pt; margin-top:2px; }
.header p  { font-size:8pt; color:#555; margin-top:2px; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th { background:#2c3e50; color:#fff; padding:5px 6px; font-size:8pt; text-align:left; }
td { padding:4px 6px; font-size:8pt; border-bottom:1px solid #eee; vertical-align:top; }
tr:nth-child(even) td { background:#f8f9fa; }
.stat-box { display:inline-block; border:1px solid #ddd; border-radius:4px; padding:6px 12px; margin:4px; text-align:center; min-width:90px; }
.stat-val { font-size:14pt; font-weight:bold; color:#2c3e50; }
.stat-lbl { font-size:7pt; color:#888; text-transform:uppercase; }
.footer-info { margin-top:15px; font-size:7.5pt; color:#888; text-align:right; }
.no-data { text-align:center; color:#888; padding:20px; font-style:italic; }
@php $namaBulan = \Carbon\Carbon::createFromDate($year,$month,1)->translatedFormat('F Y'); @endphp
</style>
</head>
<body>
<div class="page">
<div class="header">
    <h2>{{ $settings['satuan_kerja'] ?? 'RRI/LPPL Lampung' }}</h2>
    <h3>Rekap Logbook Studio Bulanan</h3>
    <p>Periode: {{ \Carbon\Carbon::createFromDate($year,$month,1)->translatedFormat('F Y') }}</p>
</div>

{{-- Statistik --}}
<div style="margin:10px 0;text-align:center">
    <div class="stat-box">
        <div class="stat-val">{{ $logs->count() }}</div>
        <div class="stat-lbl">Total Siaran</div>
    </div>
    <div class="stat-box">
        <div class="stat-val">{{ $logs->where('jenis_siaran','live')->count() }}</div>
        <div class="stat-lbl">Live</div>
    </div>
    <div class="stat-box">
        <div class="stat-val">{{ $logs->where('jenis_siaran','recorded')->count() }}</div>
        <div class="stat-lbl">Recorded</div>
    </div>
    <div class="stat-box">
        <div class="stat-val">{{ $logs->where('jenis_siaran','relay')->count() }}</div>
        <div class="stat-lbl">Relay</div>
    </div>
    <div class="stat-box">
        <div class="stat-val" style="color:{{ $logs->where('kondisi_perangkat','gangguan')->count()>0?'#c0392b':'#27ae60' }}">
            {{ $logs->where('kondisi_perangkat','gangguan')->count() }}
        </div>
        <div class="stat-lbl">Gangguan</div>
    </div>
</div>

@if($logs->isEmpty())
<p class="no-data">Tidak ada data siaran pada periode ini.</p>
@else
<table>
    <thead>
        <tr>
            <th style="width:4%">No</th>
            <th style="width:10%">Tanggal</th>
            <th style="width:18%">Jam Siaran</th>
            <th style="width:28%">Nama Program</th>
            <th style="width:10%">Jenis</th>
            <th style="width:12%">Frekuensi</th>
            <th style="width:8%">Kondisi</th>
            <th style="width:10%">Operator</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $i=>$log)
        <tr>
            <td>{{ $i+1 }}</td>
            <td class="mono">{{ $log->tanggal->format('d/m/Y') }}</td>
            <td class="mono">{{ substr($log->jam_mulai,0,5) }}{{ $log->jam_selesai?' – '.substr($log->jam_selesai,0,5):'' }}</td>
            <td>{{ $log->nama_program }}</td>
            <td>{{ $log->jenis_siaran_label }}</td>
            <td>{{ $log->frekuensi ?? '-' }}</td>
            <td style="color:{{ $log->kondisi_perangkat=='baik'?'#27ae60':'#c0392b' }}">{{ $log->kondisi_label }}</td>
            <td>{{ $log->user->name }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div style="margin-top:30px">
    <table style="width:100%;border:none;margin-top:0">
        <tr>
            <td style="width:50%;text-align:center;border:none;padding-top:0">
                <div style="font-size:8.5pt">Mengetahui,</div>
                <div style="font-size:8.5pt">{{ $koordinator['jabatan'] }}</div>
                @if(!empty($koordinator['ttd_url']))
                <img src="{{ $koordinator['ttd_url'] }}" style="height:45px;margin:5px auto;display:block">
                @else
                <div style="height:55px"></div>
                @endif
                <div style="border-top:1px solid #333;padding-top:3px;font-weight:bold;font-size:8.5pt">{{ $koordinator['nama'] ?: '___________________' }}</div>
                @if($koordinator['nip'])<div style="font-size:7.5pt;color:#555">NIP. {{ $koordinator['nip'] }}</div>@endif
            </td>
            <td style="width:50%;text-align:center;border:none;padding-top:0">
                <div style="font-size:8.5pt">Mengesahkan,</div>
                <div style="font-size:8.5pt">{{ $kabid['jabatan'] }}</div>
                @if(!empty($kabid['ttd_url']))
                <img src="{{ $kabid['ttd_url'] }}" style="height:45px;margin:5px auto;display:block">
                @else
                <div style="height:55px"></div>
                @endif
                <div style="border-top:1px solid #333;padding-top:3px;font-weight:bold;font-size:8.5pt">{{ $kabid['nama'] ?: '___________________' }}</div>
                @if($kabid['nip'])<div style="font-size:7.5pt;color:#555">NIP. {{ $kabid['nip'] }}</div>@endif
            </td>
        </tr>
    </table>
</div>
<div class="footer-info">Dicetak: {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; MonOTOn v{{ $settings['app_version'] ?? '1.0.0' }}</div>
</div>
</body></html>
