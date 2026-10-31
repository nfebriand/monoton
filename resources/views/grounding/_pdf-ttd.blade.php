<table class="ttd"><tr>
    <td>
        <div>Pengelola Teknik Sarana &amp; Prasarana</div>
        @if(!empty($pengelola['ttd_base64']))<div class="sg"><img src="{{ $pengelola['ttd_base64'] }}"></div>@else<div class="sp"></div>@endif
        <div class="nm">{{ !empty($pengelola['nama']) ? $pengelola['nama'] : '( ____________________ )' }}</div>
        @if(!empty($pengelola['nip']))<div style="font-size:7pt;color:#555">NIP. {{ $pengelola['nip'] }}</div>@endif
    </td>
    <td>
        <div>Dibuat oleh,</div>
        @php $ttdU = $pembuat->ttd_base64 ?? null; @endphp
        @if($ttdU)<div class="sg"><img src="{{ $ttdU }}"></div>@else<div class="sp"></div>@endif
        <div class="nm">{{ $pembuat->name }}</div>
        @if($pembuat->nip)<div style="font-size:7pt;color:#555">NIP. {{ $pembuat->nip }}</div>@endif
    </td>
</tr></table>
<div class="ft">MonOTOn | {{ $settings['satuan_kerja'] ?? 'LPP RRI Bandarlampung' }} | dicetak {{ now()->format('d/m/Y H:i') }}</div>
