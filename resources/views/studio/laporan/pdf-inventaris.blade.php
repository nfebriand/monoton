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
    <h3>Inventaris Perangkat Studio</h3>
    <p>Per tanggal: {{ now()->translatedFormat('d F Y') }}</p>
</div>

@if($perangkats->isEmpty())
<p class="no-data">Belum ada data perangkat.</p>
@else
@php $grouped = $perangkats->groupBy('kategori'); $no = 1; @endphp
@foreach($grouped as $kategori => $items)
<div style="margin-top:12px;font-weight:bold;font-size:9.5pt;background:#ecf0f1;padding:4px 8px;border-left:3px solid #2c3e50">
    {{ \App\Models\StudioPerangkat::KATEGORI_LABEL[$kategori] ?? ($kategori ?: 'Lainnya') }}
    ({{ $items->count() }} item)
</div>
<table>
    <thead>
        <tr>
            <th style="width:5%">No</th>
            <th style="width:8%">Kode</th>
            <th style="width:25%">Nama Perangkat</th>
            <th style="width:12%">Merk/Tipe</th>
            <th style="width:10%">No. Seri</th>
            <th style="width:6%">Tahun</th>
            <th style="width:9%">Kondisi</th>
            <th style="width:8%">Status</th>
            <th style="width:17%">Maintenance</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $p)
        <tr>
            <td>{{ $no++ }}</td>
            <td class="mono">{{ $p->kode_inventaris ?? '-' }}</td>
            <td>{{ $p->nama }}</td>
			<td>{{ ($p->merk ?? '') . (!empty($p->tipe) ? ' '.$p->tipe : '') ?: '-' }}</td>
    		<td class="mono" style="font-size:7.5pt">{{ $p->no_seri ?? '-' }}</td>
            <td>{{ $p->tahun_pengadaan ?? '-' }}</td>
            <td style="color:{{ \App\Models\StudioPerangkat::KONDISI_COLOR[$p->kondisi] ?? '#888' }}">{{ $p->kondisi_label }}</td>
            <td>{{ \App\Models\StudioPerangkat::STATUS_LABEL[$p->status] ?? $p->status }}</td>
            <td style="font-size:7.5pt">
                @if($p->maintenance_terakhir)
                Terakhir: {{ $p->maintenance_terakhir->format('d/m/Y') }}<br>
                <span style="color:{{ $p->status_maintenance_color }}">{{ $p->status_maintenance_label }}</span>
                @else
                <span style="color:#888">Belum ada</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endforeach
<div style="margin-top:8px;font-size:8pt;color:#555">
    Total: {{ $perangkats->count() }} perangkat &nbsp;|&nbsp;
    Aktif: {{ $perangkats->where('status','aktif')->count() }} &nbsp;|&nbsp;
    Kondisi Baik: {{ $perangkats->where('kondisi','baik')->count() }}
</div>
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
