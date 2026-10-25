{{-- Tab pembeda lokasi studio. Butuh: $lokasiAktif (?string), $hitung (array lokasi=>jumlah), $labelHitung (string) --}}
@php
    $__semua = array_sum($hitung ?? []);
    $__item = ['' => ['Semua Lokasi', $__semua, '#2d3436']];
    foreach (\App\Support\StudioLokasi::LIST as $__l) {
        $__item[$__l] = [\App\Support\StudioLokasi::label($__l), $hitung[$__l] ?? 0, \App\Support\StudioLokasi::color($__l)];
    }
@endphp
<div class="d-flex gap-2 flex-wrap mb-3">
    @foreach($__item as $__k => [$__lbl, $__n, $__clr])
    @php $__aktif = ((string) $lokasiAktif === (string) $__k); @endphp
    <a href="{{ request()->url() }}?{{ http_build_query(array_merge(request()->except('page', 'lokasi'), ['lokasi' => $__k])) }}"
       class="btn btn-sm"
       style="{{ $__aktif ? 'background:'.$__clr.';color:#fff;border-color:'.$__clr : 'background:#fff;color:'.$__clr.';border-color:'.$__clr }};font-weight:600">
        {{ $__lbl }}
        <span class="badge ms-1" style="background:{{ $__aktif ? 'rgba(255,255,255,.25)' : $__clr }};color:#fff">{{ $__n }}</span>
    </a>
    @endforeach
    @isset($labelHitung)<small class="text-muted align-self-center">{{ $labelHitung }}</small>@endisset
</div>
