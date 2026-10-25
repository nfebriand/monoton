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
</style>
</head>
<body>
<div class="page">
<div class="header">
    <h2>{{ $settings['satuan_kerja'] ?? 'RRI/LPPL Lampung' }}</h2>
    <h3>Rekap Logbook Studio Bulanan{{ !empty($lokasiFilter) ? ' — '.\App\Support\StudioLokasi::label($lokasiFilter) : '' }}</h3>
    <p>Periode: {{ \Carbon\Carbon::createFromDate($year,$month,1)->translatedFormat('F Y') }}</p>
</div>

{{-- Statistik --}}
@php
    $totalGangguan = $logs->filter(fn($l) => $l->ada_gangguan)->count();
@endphp
<div style="margin:10px 0;text-align:center">
    <div class="stat-box"><div class="stat-val">{{ $logs->count() }}</div><div class="stat-lbl">Total Logbook</div></div>
    @foreach(\App\Models\StudioLog::SHIFT as $kode => $label)
    <div class="stat-box"><div class="stat-val">{{ $logs->where('shift',$kode)->count() }}</div><div class="stat-lbl">{{ ucfirst($kode) }}</div></div>
    @endforeach
    <div class="stat-box">
        <div class="stat-val" style="color:{{ $totalGangguan>0?'#c0392b':'#27ae60' }}">{{ $totalGangguan }}</div>
        <div class="stat-lbl">Ada Gangguan</div>
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
            <th style="width:10%">Shift</th>
            <th style="width:12%">Jam</th>
            <th style="width:16%">Operator</th>
            <th style="width:8%">Status</th>
            <th style="width:40%">Gangguan / Catatan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $i=>$log)
        @php
            $gangguan = [];
            foreach (\App\Models\StudioLog::CHECKLIST_ITEMS as $key => $item) {
                if ($log->$key === 'gangguan') {
                    $ket = $log->{$key.'_ket'} ?? '';
                    $gangguan[] = $item['label'].($ket ? ': '.$ket : '');
                }
            }
        @endphp
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ optional($log->tanggal)->format('d/m/Y') }}</td>
            <td>{{ ucfirst((string) $log->shift) }}@if(empty($lokasiFilter) && !empty($log->lokasi))<br><small style="color:#666">{{ $log->lokasi }}</small>@endif</td>
            <td>{{ substr((string) $log->jam_mulai,0,5) }}{{ $log->jam_selesai ? ' – '.substr((string) $log->jam_selesai,0,5) : '' }}</td>
            <td>{{ optional($log->user)->name ?? '-' }}</td>
            <td style="color:{{ count($gangguan) ? '#c0392b' : '#27ae60' }}">{{ count($gangguan) ? 'Gangguan' : 'Normal' }}</td>
            <td>
                @foreach($gangguan as $g)<div>• {{ $g }}</div>@endforeach
                @if($log->catatan_petugas)<div style="color:#555">{{ $log->catatan_petugas }}</div>@endif
                @if(!count($gangguan) && !$log->catatan_petugas)-@endif
            </td>
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
                <div style="font-size:8.5pt">{{ ($koordinator['jabatan'] ?? '') }}</div>
                @if(!empty($koordinator['ttd_base64']))
                <img src="{{ $koordinator['ttd_base64'] }}" style="height:45px;margin:5px auto;display:block">
                @else
                <div style="height:55px"></div>
                @endif
                <div style="border-top:1px solid #333;padding-top:3px;font-weight:bold;font-size:8.5pt">{{ $koordinator['nama'] ?: '___________________' }}</div>
                @if($koordinator['nip'])<div style="font-size:7.5pt;color:#555">NIP. {{ $koordinator['nip'] }}</div>@endif
            </td>
            <td style="width:50%;text-align:center;border:none;padding-top:0">
                <div style="font-size:8.5pt">Mengesahkan,</div>
                <div style="font-size:8.5pt">{{ ($kabid['jabatan'] ?? '') }}</div>
                @if(!empty($kabid['ttd_base64']))
                <img src="{{ $kabid['ttd_base64'] }}" style="height:45px;margin:5px auto;display:block">
                @else
                <div style="height:55px"></div>
                @endif
                <div style="border-top:1px solid #333;padding-top:3px;font-weight:bold;font-size:8.5pt">{{ $kabid['nama'] ?: '___________________' }}</div>
                @if($kabid['nip'])<div style="font-size:7.5pt;color:#555">NIP. {{ $kabid['nip'] }}</div>@endif
            </td>
        </tr>
    </table>
</div>
<div class="footer-info">Dicetak: {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; MonOTOn</div>
</div>
</body></html>
