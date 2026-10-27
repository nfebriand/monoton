{{-- Widget perawatan rutin yang terlambat / segera jatuh tempo (otomatis tersembunyi bila tidak ada) --}}
@can('perawatan.menu')
@php $__pr = \App\Models\PerawatanRutin::perhatian(auth()->user(), 6); @endphp
@if($__pr->isNotEmpty())
<div class="card mb-3" style="border-left:4px solid {{ $__pr->contains(fn($x) => $x['hasil']['status'] === 'terlambat') ? '#ee5a24' : '#ff9f43' }}">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-wrench-adjustable text-warning"></i>
        <strong style="font-size:.88rem">Perawatan Rutin Perlu Perhatian</strong>
        <a href="{{ route('perawatan.index') }}" class="btn btn-sm btn-outline-secondary ms-auto" style="font-size:.7rem">Lihat semua</a>
    </div>
    <div class="list-group list-group-flush">
        @foreach($__pr as $__x)
        @php $__i = $__x['item']; $__h = $__x['hasil']; @endphp
        <a href="{{ route('perawatan.show', $__i) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2">
            <span class="badge" style="background:{{ \App\Support\PerawatanHitung::WARNA[$__h['status']] }}">{{ \App\Support\PerawatanHitung::LABEL[$__h['status']] }}</span>
            <span style="font-size:.82rem" class="flex-fill">
                <strong>{{ $__i->nama_objek }}</strong> &mdash; {{ $__i->nama_tugas }}
                <span class="text-muted d-block" style="font-size:.72rem">{{ $__h['ringkas'] }}</span>
            </span>
            <i class="bi bi-chevron-right text-muted"></i>
        </a>
        @endforeach
    </div>
</div>
@endif
@endcan
