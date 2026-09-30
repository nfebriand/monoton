@extends('layouts.app')
@section('title','Generate Laporan')
@section('page-title','Generate Laporan Operasional')

@section('content')

@if($errors->any())
<div class="alert alert-danger py-2 mb-3">
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('laporan.generate') }}" method="POST" target="_blank" id="formLaporan">
@csrf

{{-- STEP 1: JENIS LAPORAN --}}
<div class="card mb-3">
    <div class="card-header d-flex align-items-center gap-2">
        <span class="badge bg-primary rounded-pill">1</span>
        <strong style="font-size:.9rem">Pilih Jenis Laporan</strong>
    </div>
    <div class="card-body">
        @php
        $jenisLaporan = [
            'operasional'        => ['icon'=>'bi-broadcast',        'label'=>'Operasional Pemancar', 'divisi'=>'transmisi', 'warna'=>'#0a3d62'],
            'suhu'               => ['icon'=>'bi-thermometer-half', 'label'=>'Suhu Bulanan',         'divisi'=>'transmisi', 'warna'=>'#e17055'],
            'eviden'             => ['icon'=>'bi-camera',           'label'=>'Catatan Eviden',        'divisi'=>'all',       'warna'=>'#00b894'],
            'logbook_studio'     => ['icon'=>'bi-broadcast-pin',    'label'=>'Logbook Studio',        'divisi'=>'studio',    'warna'=>'#6c5ce7'],
            'maintenance_studio' => ['icon'=>'bi-tools',            'label'=>'Maintenance Studio',    'divisi'=>'studio',    'warna'=>'#fdcb6e'],
            'inventaris_studio'  => ['icon'=>'bi-speaker',          'label'=>'Inventaris Studio',     'divisi'=>'studio',    'warna'=>'#74b9ff'],
        ];
        $user = auth()->user();
        @endphp

        <div class="row g-2">
            @foreach($jenisLaporan as $val => $info)
            @php $tampil = $user->isAdmin() || $info['divisi']==='all' || $info['divisi']===$user->divisi; @endphp
            @if($tampil)
            <div class="col-6 col-md-4 col-lg-2">
                <label class="jenis-card w-100 h-100" style="cursor:pointer">
                    <input type="radio" name="_jenis" value="{{ $val }}" class="d-none jenis-radio">
                    <div class="jenis-btn h-100 d-flex flex-column align-items-center justify-content-center p-2 text-center rounded border"
                         style="border-color:#dee2e6!important;min-height:80px;transition:all .2s">
                        <i class="bi {{ $info['icon'] }} mb-1" style="font-size:1.4rem;color:{{ $info['warna'] }}"></i>
                        <span style="font-size:.72rem;line-height:1.2;color:#444">{{ $info['label'] }}</span>
                    </div>
                </label>
            </div>
            @endif
            @endforeach
        </div>
        <input type="hidden" name="jenis_laporan" id="jenis_laporan_val">
    </div>
</div>

{{-- STEP 2: DIVISI (Admin + Eviden only) --}}
@if($user->isAdmin())
<div id="step-divisi" class="card mb-3 d-none">
    <div class="card-header d-flex align-items-center gap-2">
        <span class="badge bg-primary rounded-pill">2</span>
        <strong style="font-size:.9rem">Divisi</strong>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-12 col-md-4">
                <select name="divisi" id="sel-divisi" class="form-select">
                    <option value="">Semua Divisi</option>
                    @foreach(\App\Models\User::DIVISI_LABEL as $val=>$label)
                    <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
@endif

{{-- STEP 3: PERIODE --}}
<div id="step-periode" class="card mb-3 d-none">
    <div class="card-header d-flex align-items-center gap-2">
        <span class="badge bg-primary rounded-pill">3</span>
        <strong style="font-size:.9rem">Periode Laporan</strong>
    </div>
    <div class="card-body">
        {{-- Bulanan --}}
        <div id="mode-bulanan" class="row g-2 d-none">
            <div class="col-6 col-md-3">
                <label class="form-label" style="font-size:.8rem">Bulan</label>
                <select name="bulan" class="form-select">
                    @for($m=1;$m<=12;$m++)
                    <option value="{{ $m }}" {{ now()->month==$m?'selected':'' }}>{{ \Carbon\Carbon::create(null,$m)->translatedFormat('F') }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" style="font-size:.8rem">Tahun</label>
                <select name="tahun" class="form-select">
                    @for($y=now()->year;$y>=now()->year-3;$y--)
                    <option value="{{ $y }}" {{ now()->year==$y?'selected':'' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>
        {{-- Rentang Tanggal --}}
        <div id="mode-rentang" class="row g-2">
            <div class="col-6 col-md-3">
                <label class="form-label" style="font-size:.8rem">Dari Tanggal</label>
                <input type="date" name="tanggal_dari" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" style="font-size:.8rem">Sampai Tanggal</label>
                <input type="date" name="tanggal_sampai" class="form-control" value="{{ now()->toDateString() }}">
            </div>
            <div class="col-12 col-md-6 d-flex align-items-end">
                <div class="d-flex flex-wrap gap-1">
                    @php $presets=['Hari Ini'=>[now()->toDateString(),now()->toDateString()],'Bulan Ini'=>[now()->startOfMonth()->toDateString(),now()->toDateString()],'Bulan Lalu'=>[now()->subMonth()->startOfMonth()->toDateString(),now()->subMonth()->endOfMonth()->toDateString()],'3 Bulan'=>[now()->subMonths(3)->startOfMonth()->toDateString(),now()->toDateString()]]; @endphp
                    @foreach($presets as $lbl=>[$d,$s])
                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="setPreset('{{ $d }}','{{ $s }}')">{{ $lbl }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- STEP 4: FILTER --}}
<div id="step-filter" class="card mb-3 d-none">
    <div class="card-header d-flex align-items-center gap-2">
        <span class="badge bg-secondary rounded-pill">4</span>
        <strong style="font-size:.9rem">Filter Data</strong>
        <small class="text-muted">(opsional)</small>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div id="filter-lokasi-transmisi" class="col-12 col-md-4 d-none">
                <label class="form-label" style="font-size:.8rem">Lokasi Pemancar</label>
                <select name="lokasi" id="sel-lokasi-transmisi" class="form-select">
                    <option value="">Semua Lokasi</option>
                    @foreach($lokasiTransmisi as $lok)
                    <option value="{{ $lok }}">{{ $lok }}</option>
                    @endforeach
                </select>
            </div>
            <div id="filter-pemancar" class="col-12 col-md-4 d-none">
                <label class="form-label" style="font-size:.8rem">Pemancar</label>
                <select name="pemancar_id" id="sel-pemancar" class="form-select">
                    <option value="">Semua Pemancar</option>
                    @foreach($pemancars as $p)
                    <option value="{{ $p->id }}" data-lokasi="{{ $p->lokasi }}">{{ $p->nama_stasiun }}{{ $p->lokasi?' ('.$p->lokasi.')':'' }}</option>
                    @endforeach
                </select>
            </div>
            <div id="filter-lokasi-eviden" class="col-12 col-md-4 d-none">
                <label class="form-label" style="font-size:.8rem">Lokasi Kegiatan</label>
                <select name="lokasi_eviden" class="form-select">
                    <option value="">Semua Lokasi</option>
                    @foreach($lokasiEviden as $lok)
                    <option value="{{ $lok }}">{{ $lok }}</option>
                    @endforeach
                </select>
            </div>
            <div id="filter-operator" class="col-12 col-md-4 d-none">
                <label class="form-label" style="font-size:.8rem">Operator / Petugas</label>
                <select name="user_id" id="sel-operator" class="form-select">
                    <option value="">Semua Operator</option>
                    @foreach($operatorPerDivisi as $divOp => $users)
                    @if(count($users))
                    <optgroup label="{{ \App\Models\User::DIVISI_LABEL[$divOp] ?? $divOp }}" data-divisi="{{ $divOp }}">
                        @foreach($users as $op)
                        <option value="{{ $op['id'] }}" data-divisi="{{ $divOp }}">{{ $op['name'] }}{{ !empty($op['nip'])?' ('.$op['nip'].')':'' }}</option>
                        @endforeach
                    </optgroup>
                    @endif
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

{{-- TOMBOL GENERATE --}}
<div id="step-generate" class="d-none">
    <div class="d-flex gap-3 align-items-center flex-wrap">
        <button type="submit" class="btn btn-danger btn-lg" id="btn-generate">
            <i class="bi bi-file-earmark-pdf me-2"></i>Generate PDF
        </button>
        <div>
            <div style="font-size:.78rem;color:#888">Jenis laporan:</div>
            <div style="font-size:.9rem;font-weight:600;color:#0a3d62" id="label-jenis-terpilih">—</div>
        </div>
    </div>
</div>

</form>

@push('styles')
<style>
.jenis-card input:checked + .jenis-btn {
    border-color: #0a3d62 !important;
    background: #eaf2ff;
    box-shadow: 0 0 0 2px #0a3d62;
}
.jenis-btn:hover { border-color: #0a3d62 !important; background: #f0f4f8; }
.btn-xs { padding:2px 8px; font-size:.72rem; }
</style>
@endpush

@push('scripts')
<script>
const divisiUser = '{{ auth()->user()->divisi }}';
const isAdmin    = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};
const operatorPerDivisi = @json($operatorPerDivisi);
const lokasiPerDivisi   = @json($lokasiPerDivisi);

const jenisConfig = {
    operasional:         { action:'{{ route('laporan.generate') }}',              method:'POST', periode:'rentang', filter:['lokasi_transmisi','pemancar','operator'], showDivisi:false },
    suhu:                { action:'{{ route('laporan.suhu.pdf') }}',              method:'GET',  periode:'bulanan', filter:['lokasi_transmisi','pemancar'],             showDivisi:false },
    eviden:              { action:'{{ route('laporan.eviden-rekap-pdf') }}',      method:'GET',  periode:'rentang', filter:['lokasi_eviden','operator'],                showDivisi:true  },
    logbook_studio:      { action:'{{ route('studio.logbook.cetak-bulanan') }}',  method:'GET',  periode:'bulanan', filter:['operator'],                               showDivisi:false },
    maintenance_studio:  { action:'{{ route('studio.maintenance.cetak') }}',      method:'GET',  periode:'rentang', filter:['operator'],                                showDivisi:false },
    inventaris_studio:   { action:'{{ route('studio.perangkat.cetak-inventaris') }}', method:'GET', periode:'none', filter:[],                                          showDivisi:false },
};

const jenisLabel = {
    operasional:'Laporan Operasional Pemancar', suhu:'Laporan Suhu Bulanan',
    eviden:'Rekap Catatan Eviden', logbook_studio:'Logbook Studio',
    maintenance_studio:'Maintenance Studio', inventaris_studio:'Inventaris Perangkat Studio',
};

let selectedJenis = null;

document.querySelectorAll('.jenis-radio').forEach(radio => {
    radio.addEventListener('change', function(){ selectedJenis = this.value; applyConfig(this.value); });
});
document.querySelectorAll('.jenis-card').forEach(card => {
    card.addEventListener('click', function(){
        const r = this.querySelector('.jenis-radio'); r.checked = true; r.dispatchEvent(new Event('change'));
    });
});

function applyConfig(jenis) {
    const cfg = jenisConfig[jenis]; if (!cfg) return;
    const form = document.getElementById('formLaporan');
    form.action = cfg.action;
    form.method = cfg.method === 'GET' ? 'GET' : 'POST';
    const csrf = form.querySelector('input[name=_token]');
    if (csrf) csrf.disabled = cfg.method !== 'POST';

    // Divisi
    const stepDivisi = document.getElementById('step-divisi');
    if (stepDivisi) stepDivisi.classList.toggle('d-none', !cfg.showDivisi);

    // Periode
    const stepPeriode = document.getElementById('step-periode');
    if (cfg.periode === 'none') {
        stepPeriode.classList.add('d-none');
    } else {
        stepPeriode.classList.remove('d-none');
        document.getElementById('mode-bulanan').classList.toggle('d-none', cfg.periode !== 'bulanan');
        document.getElementById('mode-rentang').classList.toggle('d-none', cfg.periode === 'bulanan');
    }

    // Filter
    const stepFilter = document.getElementById('step-filter');
    const anyFilter  = cfg.filter.length > 0;
    stepFilter.classList.toggle('d-none', !anyFilter);
    ['lokasi_transmisi','pemancar','lokasi_eviden','operator'].forEach(f => {
        const el = document.getElementById('filter-' + f);
        if (el) el.classList.toggle('d-none', !cfg.filter.includes(f));
    });
    updateOperatorDropdown(jenis);

    // Generate
    document.getElementById('step-generate').classList.remove('d-none');
    document.getElementById('label-jenis-terpilih').textContent = jenisLabel[jenis] || jenis;
    document.getElementById('jenis_laporan_val').value = jenis;
}

function updateOperatorDropdown(jenis) {
    const sel = document.getElementById('sel-operator'); if (!sel) return;
    let targetDivisi = null;
    if (['operasional','suhu'].includes(jenis)) targetDivisi = 'transmisi';
    else if (['logbook_studio','maintenance_studio','inventaris_studio'].includes(jenis)) targetDivisi = 'studio';
    else if (jenis === 'eviden') {
        const sd = document.getElementById('sel-divisi');
        targetDivisi = sd ? sd.value : divisiUser;
    }
    sel.querySelectorAll('optgroup').forEach(g => { g.hidden = !!(targetDivisi && g.dataset.divisi !== targetDivisi); });
    sel.value = '';
}

document.getElementById('sel-lokasi-transmisi')?.addEventListener('change', function(){
    const lok = this.value;
    document.querySelectorAll('#sel-pemancar option[data-lokasi]').forEach(o => { o.style.display = (!lok || o.dataset.lokasi===lok)?'':'none'; });
    document.getElementById('sel-pemancar').value = '';
});
document.getElementById('sel-divisi')?.addEventListener('change', function(){
    if (selectedJenis === 'eviden') updateOperatorDropdown('eviden');
});

function setPreset(dari, sampai) {
    const d = document.querySelector('[name=tanggal_dari]');
    const s = document.querySelector('[name=tanggal_sampai]');
    if (d) d.value = dari; if (s) s.value = sampai;
}
</script>
@endpush
@endsection
