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
.footer-info { margin-top:15px; font-size:7.5pt; color:#888; text-align:right; }
.no-data { text-align:center; color:#888; padding:20px; font-style:italic; }
</style>
</head>
<body>
<div class="page">
<div class="header">
    <h2>{{ $settings['satuan_kerja'] ?? 'RRI/LPPL Lampung' }}</h2>
    <h3>Laporan Maintenance Perangkat Studio</h3>
    <p>Periode: {{ \Carbon\Carbon::parse($tanggalDari)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($tanggalSampai)->format('d/m/Y') }}</p>
</div>

@if($logs->isEmpty())
<p class="no-data">Tidak ada data maintenance pada periode ini.</p>
@else
<table>
    <thead>
        <tr>
            <th style="width:4%">No</th>
            <th style="width:10%">Tanggal</th>
            <th style="width:22%">Perangkat</th>
            <th style="width:9%">Jenis</th>
            <th style="width:30%">Uraian Pekerjaan</th>
            <th style="width:8%">Hasil</th>
            <th style="width:10%">Biaya</th>
            <th style="width:7%">Teknisi</th>
        </tr>
    </thead>
    <tbody>
        @php $totalBiaya = 0; @endphp
        @foreach($logs as $i=>$log)
        @php $totalBiaya += $log->biaya ?? 0; @endphp
        <tr>
            <td>{{ $i+1 }}</td>
            <td class="mono">{{ $log->tanggal->format('d/m/Y') }}</td>
            <td>{{ $log->perangkat->nama }}</td>
            <td>{{ $log->jenis_label }}</td>
            <td>
                {{ $log->uraian_pekerjaan }}
                @if($log->sparepart_terpakai)
                <br><small style="color:#666">Sparepart: {{ collect($log->sparepart_terpakai)->pluck('nama')->join(', ') }}</small>
                @endif
            </td>
            <td style="color:{{ $log->hasil=='selesai'?'#27ae60':($log->hasil=='sebagian'?'#e67e22':'#888') }}">{{ $log->hasil_label }}</td>
            <td class="mono">{{ $log->biaya ? 'Rp '.number_format($log->biaya,0,',','.') : '-' }}</td>
            <td>{{ $log->user->name }}</td>
        </tr>
        @endforeach
        <tr>
            <td colspan="6" style="text-align:right;font-weight:bold">Total Biaya:</td>
            <td class="mono" style="font-weight:bold">Rp {{ number_format($totalBiaya,0,',','.') }}</td>
            <td></td>
        </tr>
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
