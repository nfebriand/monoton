@extends('layouts.app')
@section('title','Jadwal Shift')
@section('page-title','Jadwal Shift Operator')

@section('content')
@php
    $sc = [1=>'#0a3d62',2=>'#10ac84',3=>'#ff9f43'];
    $namaBulan = \Carbon\Carbon::create($tahun,$bulan,1)->translatedFormat('F Y');
    $bulanPrev = $bulan==1?12:$bulan-1; $tahunPrev=$bulan==1?$tahun-1:$tahun;
    $bulanNext = $bulan==12?1:$bulan+1; $tahunNext=$bulan==12?$tahun+1:$tahun;
@endphp

<div class="row g-3">

{{-- ─── KIRI: Kalender + Tabel ─────────────────────────────── --}}
<div class="col-12 col-xl-8">

    {{-- Kalender --}}
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <a href="?bulan={{ $bulanPrev }}&tahun={{ $tahunPrev }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
            <span class="flex-fill text-center fw-bold">📅 {{ $namaBulan }}</span>
            <a href="?bulan={{ $bulanNext }}&tahun={{ $tahunNext }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="card-body p-2">
            <div class="kal-grid mb-1">
                @foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $h)
                <div class="kal-head">{{ $h }}</div>
                @endforeach
            </div>
            @php $hariPertama = $hariKalender->first()->dayOfWeek; @endphp
            <div class="kal-grid">
                @for($i=0;$i<$hariPertama;$i++)<div class="kal-cell empty"></div>@endfor
                @foreach($hariKalender as $hari)
                @php $tgl=$hari->toDateString(); $jh=$jadwals->get($tgl,collect()); @endphp
                <div class="kal-cell {{ $hari->isToday()?'today':'' }}">
                    <div class="kal-tgl">{{ $hari->day }}</div>
                    @foreach($jh->sortBy('shift') as $j)
                    <div class="kal-shift" style="background:{{ $sc[$j->shift]??'#888' }}"
                         title="Shift {{ $j->shift }}: {{ $j->user->name }}">
                        S{{ $j->shift }} {{ \Str::limit($j->user->name,6) }}
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>
            <div class="d-flex flex-wrap gap-3 mt-2 px-1" style="font-size:.68rem;color:#555">
                @foreach($shiftDefs as $no=>$sh)
                <div class="d-flex align-items-center gap-1">
                    <span style="width:10px;height:10px;border-radius:2px;background:{{ $sc[$no] }};display:inline-block"></span>
                    <span>{{ $sh['label'] }}: {{ $sh['mulai'] }}–{{ $sh['selesai'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Tabel Detail --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="bi bi-table me-1"></i>Detail Jadwal — {{ $namaBulan }}</span>
            <span class="badge bg-secondary">{{ $jadwals->flatten()->count() }} shift</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tanggal</th>
                        <th>Hari</th>
                        <th>Shift</th>
                        <th>Jam</th>
                        <th>Operator</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jadwals->flatten()->sortBy('tanggal') as $j)
                    <tr>
                        <td class="ps-3 mono" style="font-size:.8rem">{{ \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') }}</td>
                        <td style="font-size:.8rem">{{ \Carbon\Carbon::parse($j->tanggal)->translatedFormat('l') }}</td>
                        <td><span class="badge" style="background:{{ $sc[$j->shift]??'#888' }}">Shift {{ $j->shift }}</span></td>
                        <td class="mono" style="font-size:.78rem">{{ $j->jam_mulai }} – {{ $j->jam_selesai }}</td>
                        <td style="font-size:.85rem;font-weight:600">{{ $j->user->name }}</td>
                        <td class="pe-2">
                            <form action="{{ route('jadwal.destroy',$j) }}" method="POST"
                                  onsubmit="return confirm('Hapus jadwal ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" style="padding:.1rem .35rem">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-calendar-x d-block fs-3 mb-1"></i>Belum ada jadwal bulan ini
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ─── KANAN: Form ────────────────────────────────────────── --}}
<div class="col-12 col-xl-4">

    {{-- TAB: Harian / Bulanan --}}
    <ul class="nav nav-pills mb-3 gap-2">
        <li class="nav-item flex-fill">
            <button class="nav-link active w-100" id="tab-harian" onclick="switchTab('harian')">
                <i class="bi bi-calendar-day me-1"></i>Jadwal Harian
            </button>
        </li>
        <li class="nav-item flex-fill">
            <button class="nav-link w-100" id="tab-bulanan" onclick="switchTab('bulanan')">
                <i class="bi bi-calendar-month me-1"></i>Input Bulanan
            </button>
        </li>
    </ul>

    {{-- FORM HARIAN --}}
    <div id="form-harian">
        <div class="card">
            <div class="card-header fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Jadwal Harian</div>
            <div class="card-body">
                <form action="{{ route('jadwal.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Operator <span class="text-danger">*</span></label>
                    <select name="user_id" class="form-select" required>
                        <option value="">— Pilih Operator —</option>
                        @foreach($operators as $op)
                        <option value="{{ $op->id }}">{{ $op->name }}{{ $op->lokasi_dinas?' ('.$op->lokasi_dinas.')':'' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Shift <span class="text-danger">*</span></label>
                    @foreach($shiftDefs as $no=>$sh)
                    <div class="form-check mb-1">
                        <input type="radio" name="shift" value="{{ $no }}" id="sh{{ $no }}" class="form-check-input" required>
                        <label for="sh{{ $no }}" class="form-check-label">
                            <span class="badge me-1" style="background:{{ $sc[$no] }}">{{ $sh['label'] }}</span>
                            <span class="mono" style="font-size:.78rem">{{ $sh['mulai'] }}–{{ $sh['selesai'] }}</span>
                        </label>
                    </div>
                    @endforeach
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2" placeholder="Opsional..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-save me-1"></i>Simpan Jadwal
                </button>
                </form>
            </div>
        </div>
    </div>

    {{-- FORM BULANAN --}}
    <div id="form-bulanan" style="display:none">
        <div class="card">
            <div class="card-header fw-bold">
                <i class="bi bi-calendar-month me-2 text-success"></i>Input Jadwal Bulanan
            </div>
            <div class="card-body">
                <div class="alert alert-info py-2" style="font-size:.75rem">
                    <i class="bi bi-info-circle me-1"></i>
                    Atur shift untuk setiap tanggal dalam satu bulan. Pilih <strong>—</strong> untuk melewati/mengosongkan tanggal tersebut.
                </div>

                <form action="{{ route('jadwal.bulanan') }}" method="POST" id="formBulanan">
                @csrf
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Operator <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select form-select-sm" id="selOpBulanan" required onchange="loadJadwalOp()">
                            <option value="">— Pilih —</option>
                            @foreach($operators as $op)
                            <option value="{{ $op->id }}" data-nama="{{ $op->name }}">{{ $op->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3">
                        <label class="form-label">Bulan</label>
                        <select name="bulan_target" id="selBulan" class="form-select form-select-sm" required onchange="generateGrid()">
                            @for($m=1;$m<=12;$m++)
                            <option value="{{ $m }}" {{ $bulan==$m?'selected':'' }}>
                                {{ \Carbon\Carbon::create(null,$m)->translatedFormat('M') }}
                            </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-3">
                        <label class="form-label">Tahun</label>
                        <select name="tahun_target" id="selTahun" class="form-select form-select-sm" required onchange="generateGrid()">
                            @for($y=now()->year;$y<=now()->year+1;$y++)
                            <option value="{{ $y }}" {{ $tahun==$y?'selected':'' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                {{-- Template Cepat --}}
                <div class="mb-2" style="font-size:.75rem;font-weight:600;color:#636e72">Template Cepat:</div>
                <div class="d-flex gap-1 mb-3 flex-wrap">
                    <button type="button" class="btn btn-xs btn-outline-primary" style="font-size:.7rem;padding:.2rem .5rem"
                            onclick="setTemplate('rotasi123')">Rotasi S1→S2→S3</button>
                    <button type="button" class="btn btn-xs btn-outline-success" style="font-size:.7rem;padding:.2rem .5rem"
                            onclick="setTemplate('shift1')">Semua Shift 1</button>
                    <button type="button" class="btn btn-xs btn-outline-warning" style="font-size:.7rem;padding:.2rem .5rem"
                            onclick="setTemplate('shift2')">Semua Shift 2</button>
                    <button type="button" class="btn btn-xs btn-outline-danger" style="font-size:.7rem;padding:.2rem .5rem"
                            onclick="setTemplate('shift3')">Semua Shift 3</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary" style="font-size:.7rem;padding:.2rem .5rem"
                            onclick="setTemplate('clear')">Kosongkan</button>
                </div>

                {{-- Grid Tanggal --}}
                <div id="gridBulanan" style="max-height:380px;overflow-y:auto;padding-right:4px">
                    {{-- Diisi oleh JS --}}
                </div>

                <button type="submit" class="btn btn-success w-100 mt-3">
                    <i class="bi bi-save me-1"></i>Simpan Jadwal Bulanan
                </button>
                </form>
            </div>
        </div>
    </div>

</div>
</div>
@endsection

@push('styles')
<style>
.kal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:3px;}
.kal-head{text-align:center;font-size:.65rem;font-weight:700;color:#636e72;padding:.22rem 0;text-transform:uppercase;}
.kal-cell{min-height:62px;border-radius:5px;padding:2px;background:#f8fafc;border:1px solid #edf2f7;overflow:hidden;}
.kal-cell.empty{background:transparent;border-color:transparent;}
.kal-cell.today{background:#e8f4fd;border-color:var(--primary);}
.kal-tgl{font-size:.72rem;padding:1px 3px;font-weight:500;}
.kal-cell.today .kal-tgl{background:var(--primary);color:#fff;border-radius:3px;display:inline-block;}
.kal-shift{border-radius:2px;padding:1px 3px;color:#fff;font-size:.58rem;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.nav-pills .nav-link{font-size:.8rem;padding:.4rem .8rem;border-radius:7px;}
.nav-pills .nav-link.active{background:var(--primary);}
.grid-row{display:flex;align-items:center;gap:.5rem;padding:.28rem 0;border-bottom:1px solid #f0f4f8;}
.grid-row:last-child{border-bottom:none;}
.grid-tgl{width:75px;font-size:.75rem;flex-shrink:0;color:#555;}
.grid-sel{flex:1;}
.grid-sel select{font-size:.72rem;padding:.15rem .3rem;border-radius:5px;border:1px solid #dfe6e9;width:100%;}
.grid-sel select.has-shift{font-weight:700;}
</style>
@endpush

@push('scripts')
<script>
const SC={1:'#0a3d62',2:'#10ac84',3:'#ff9f43'};
const SL={1:'Shift 1 (00:15–07:45)',2:'Shift 2 (07:45–15:45)',3:'Shift 3 (15:45–23:45)'};

// Data jadwal existing dari server
const existingJadwal = {!! json_encode(
    $jadwals->flatten()->mapWithKeys(fn($j) => [
        \Carbon\Carbon::parse($j->tanggal)->format('Y-m-d') => [
            'shift'   => $j->shift,
            'user_id' => $j->user_id,
        ]
    ])
) !!};

function switchTab(tab){
    document.getElementById('form-harian').style.display  = tab==='harian'?'':'none';
    document.getElementById('form-bulanan').style.display = tab==='bulanan'?'':'none';
    document.getElementById('tab-harian').classList.toggle('active', tab==='harian');
    document.getElementById('tab-bulanan').classList.toggle('active', tab==='bulanan');
    if(tab==='bulanan') generateGrid();
}

function generateGrid(){
    const bulan = parseInt(document.getElementById('selBulan').value);
    const tahun = parseInt(document.getElementById('selTahun').value);
    const opId  = document.getElementById('selOpBulanan').value;
    const container = document.getElementById('gridBulanan');

    const hari = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    const namaBulan=['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    // Hitung jumlah hari
    const jumlahHari = new Date(tahun,bulan,0).getDate();

    let html='';
    for(let d=1;d<=jumlahHari;d++){
        const tgl=`${tahun}-${String(bulan).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const dt=new Date(tgl);
        const hariNama=hari[dt.getDay()];
        const isMinggu=dt.getDay()===0;

        // Ambil shift existing untuk operator ini
        let existShift='';
        if(opId && existingJadwal[tgl] && existingJadwal[tgl].user_id==opId){
            existShift=existingJadwal[tgl].shift;
        }

        const optS1=existShift==1?'selected':'';
        const optS2=existShift==2?'selected':'';
        const optS3=existShift==3?'selected':'';
        const style=isMinggu?'color:#ee5a24;font-weight:600':'';

        html+=`<div class="grid-row">
            <div class="grid-tgl" style="${style}">${d} ${hariNama}${isMinggu?' 🔴':''}</div>
            <div class="grid-sel">
                <select name="jadwal_harian[${tgl}]" class="grid-sel-input ${existShift?'has-shift':''}"
                        onchange="onShiftChange(this,${existShift?existShift:0})">
                    <option value="">— Libur —</option>
                    <option value="1" ${optS1}>S1 (00:15)</option>
                    <option value="2" ${optS2}>S2 (07:45)</option>
                    <option value="3" ${optS3}>S3 (15:45)</option>
                </select>
            </div>
        </div>`;
    }
    container.innerHTML=html||'<div class="text-muted text-center py-3">Pilih bulan & tahun</div>';
}

function onShiftChange(sel,prev){
    if(sel.value){
        sel.style.background=SC[sel.value]+'22';
        sel.style.color=SC[sel.value];
        sel.style.fontWeight='700';
        sel.style.borderColor=SC[sel.value];
    } else {
        sel.style.background='';
        sel.style.color='';
        sel.style.fontWeight='';
        sel.style.borderColor='';
    }
}

function setTemplate(type){
    const selects=document.querySelectorAll('.grid-sel-input');
    selects.forEach((sel,idx)=>{
        if(type==='clear') sel.value='';
        else if(type==='shift1') sel.value='1';
        else if(type==='shift2') sel.value='2';
        else if(type==='shift3') sel.value='3';
        else if(type==='rotasi123') sel.value=((idx%3)+1).toString();
        onShiftChange(sel,0);
    });
}

// Init grid
document.addEventListener('DOMContentLoaded',()=>{
    // Warnai selects yang sudah ada nilai
    document.querySelectorAll('.grid-sel-input').forEach(sel=>{
        if(sel.value) onShiftChange(sel,0);
    });
});
</script>
@endpush
