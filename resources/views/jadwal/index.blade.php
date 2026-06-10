@extends('layouts.app')
@section('title','Jadwal Shift')
@section('page-title','Jadwal Shift Operator')

@section('content')
@php
    use App\Models\JadwalShift;
    $namaBulan = \Carbon\Carbon::create($tahun,$bulan,1)->translatedFormat('F Y');
    $bulanPrev = $bulan==1?12:$bulan-1; $tahunPrev=$bulan==1?$tahun-1:$tahun;
    $bulanNext = $bulan==12?1:$bulan+1; $tahunNext=$bulan==12?$tahun+1:$tahun;
    // Warna per shift (lintas skema)
    $shiftColors = [1=>'#0a3d62',2=>'#10ac84',3=>'#ff9f43'];
    $skemaColors = [
        'gedung_air'=>'#0a3d62','sukarame'=>'#7b1fa2','bakauheni'=>'#c62828','default'=>'#263238'
    ];
@endphp

<div class="row g-3">

{{-- KIRI: Kalender + Tabel --}}
<div class="col-12 col-xl-8">

    {{-- Kalender --}}
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <a href="?bulan={{ $bulanPrev }}&tahun={{ $tahunPrev }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-chevron-left"></i>
            </a>
            <span class="flex-fill text-center fw-bold">📅 {{ $namaBulan }}</span>
            <a href="?bulan={{ $bulanNext }}&tahun={{ $tahunNext }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-chevron-right"></i>
            </a>
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
                    @php
                        $skemaJ = $j->skema ?? 'default';
                        $sc = $skemaColors[$skemaJ] ?? '#555';
                        $sl = $j->shift_label;
                    @endphp
                    <div class="kal-shift" style="background:{{ $sc }}"
                         title="{{ $j->user->name }} — {{ $sl }} ({{ $j->jam_mulai }}–{{ $j->jam_selesai }})">
                        {{ \Str::limit($sl,4,'') }} {{ \Str::limit($j->user->name,5) }}
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>

            {{-- Legend skema --}}
            <div class="d-flex flex-wrap gap-3 mt-2 px-1" style="font-size:.68rem;color:#555">
                @foreach($skemaColors as $sk => $clr)
                @php $info = JadwalShift::SKEMA[$sk] ?? null; @endphp
                @if($info)
                <div class="d-flex align-items-center gap-1">
                    <span style="width:10px;height:10px;border-radius:2px;background:{{ $clr }};display:inline-block"></span>
                    <span>{{ $info['label'] }}</span>
                </div>
                @endif
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
                        <th>Operator</th>
                        <th>Skema</th>
                        <th>Shift</th>
                        <th>Jam</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jadwals->flatten()->sortBy('tanggal') as $j)
                    @php
                        $skemaJ = $j->skema ?? 'default';
                        $sc     = $skemaColors[$skemaJ] ?? '#555';
                    @endphp
                    <tr>
                        <td class="ps-3 mono" style="font-size:.8rem">
                            {{ \Carbon\Carbon::parse($j->tanggal)->format('d/m/Y') }}
                        </td>
                        <td style="font-size:.8rem">
                            {{ \Carbon\Carbon::parse($j->tanggal)->translatedFormat('l') }}
                        </td>
                        <td style="font-size:.85rem;font-weight:600">{{ $j->user->name }}</td>
                        <td>
                            <span class="badge" style="background:{{ $sc }};font-size:.62rem">
                                {{ JadwalShift::SKEMA[$skemaJ]['label'] ?? $skemaJ }}
                            </span>
                        </td>
                        <td>
                            <span class="badge" style="background:{{ $shiftColors[$j->shift]??'#888' }};font-size:.65rem">
                                {{ $j->shift_label }}
                            </span>
                        </td>
                        <td class="mono" style="font-size:.78rem">
                            {{ $j->jam_mulai }} – {{ $j->jam_selesai }}
                        </td>
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
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-calendar-x d-block fs-3 mb-1"></i>Belum ada jadwal bulan ini
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- KANAN: Form --}}
<div class="col-12 col-xl-4">
    <ul class="nav nav-pills mb-3 gap-2">
        <li class="nav-item flex-fill">
            <button class="nav-link active w-100" id="tab-harian" onclick="switchTab('harian')">
                <i class="bi bi-calendar-day me-1"></i>Harian
            </button>
        </li>
        <li class="nav-item flex-fill">
            <button class="nav-link w-100" id="tab-bulanan" onclick="switchTab('bulanan')">
                <i class="bi bi-calendar-month me-1"></i>Bulanan
            </button>
        </li>
    </ul>

    {{-- FORM HARIAN --}}
    <div id="form-harian">
        <div class="card">
            <div class="card-header fw-bold">
                <i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Jadwal Harian
            </div>
            <div class="card-body">
                <form action="{{ route('jadwal.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Operator <span class="text-danger">*</span></label>
                    <select name="user_id" id="selOpHarian" class="form-select" required
                            onchange="updateShiftOptionsHarian()">
                        <option value="">— Pilih Operator —</option>
                        @foreach($operators as $op)
                        <option value="{{ $op->id }}"
                                data-lokasi="{{ $op->lokasi_dinas }}"
                                data-skema="{{ \App\Models\JadwalShift::getSkemaForLokasi($op->lokasi_dinas??'') }}">
                            {{ $op->name }}{{ $op->lokasi_dinas?' ('.$op->lokasi_dinas.')':'' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>

                {{-- Info skema --}}
                <div id="skemaInfoHarian" class="mb-3 p-2 rounded" style="background:#f0f4f8;border:1px solid #dfe6e9;display:none">
                    <div style="font-size:.68rem;color:#636e72;margin-bottom:.35rem;font-weight:700">SKEMA JADWAL</div>
                    <div id="skemaNamaHarian" style="font-size:.78rem;font-weight:600"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Shift <span class="text-danger">*</span></label>
                    <div id="shiftOptionsHarian">
                        <div class="text-muted small">Pilih operator dulu</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2"></textarea>
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
                <div class="alert alert-info py-2" style="font-size:.73rem">
                    <i class="bi bi-info-circle me-1"></i>
                    Pilih operator terlebih dahulu. Opsi shift akan menyesuaikan skema lokasi operator.
                </div>
                <form action="{{ route('jadwal.bulanan') }}" method="POST" id="formBulanan">
                @csrf
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Operator <span class="text-danger">*</span></label>
                        <select name="user_id" id="selOpBulanan" class="form-select form-select-sm"
                                required onchange="generateGrid()">
                            <option value="">— Pilih —</option>
                            @foreach($operators as $op)
                            <option value="{{ $op->id }}"
                                    data-lokasi="{{ $op->lokasi_dinas }}"
                                    data-skema="{{ \App\Models\JadwalShift::getSkemaForLokasi($op->lokasi_dinas??'') }}"
                                    data-nama="{{ $op->name }}">
                                {{ $op->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3">
                        <label class="form-label">Bulan</label>
                        <select name="bulan_target" id="selBulan" class="form-select form-select-sm"
                                required onchange="generateGrid()">
                            @for($m=1;$m<=12;$m++)
                            <option value="{{ $m }}" {{ $bulan==$m?'selected':'' }}>
                                {{ \Carbon\Carbon::create(null,$m)->translatedFormat('M') }}
                            </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-3">
                        <label class="form-label">Tahun</label>
                        <select name="tahun_target" id="selTahun" class="form-select form-select-sm"
                                required onchange="generateGrid()">
                            @for($y=now()->year;$y<=now()->year+1;$y++)
                            <option value="{{ $y }}" {{ $tahun==$y?'selected':'' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                {{-- Info skema aktif --}}
                <div id="skemaInfoBulanan" class="mb-2 p-2 rounded" style="background:#f0f4f8;border:1px solid #dfe6e9;display:none">
                    <div style="font-size:.62rem;color:#636e72;font-weight:700">SKEMA JADWAL</div>
                    <div id="skemaNamaBulanan" style="font-size:.78rem;font-weight:600"></div>
                    <div id="skemaShiftsBulanan" style="font-size:.68rem;color:#555;margin-top:.2rem"></div>
                </div>

                {{-- Template Cepat --}}
                <div id="templateCepat" style="display:none">
                    <div class="mb-1" style="font-size:.72rem;font-weight:600;color:#636e72">Template Cepat:</div>
                    <div id="btnTemplates" class="d-flex gap-1 mb-3 flex-wrap"></div>
                </div>

                {{-- Grid Tanggal --}}
                <div id="gridBulanan" style="max-height:380px;overflow-y:auto;padding-right:4px"></div>

                <button type="submit" class="btn btn-success w-100 mt-3" id="btnSimpanBulanan" disabled>
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
.kal-shift{border-radius:2px;padding:1px 3px;color:#fff;font-size:.56rem;margin-bottom:2px;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.nav-pills .nav-link{font-size:.8rem;padding:.4rem .8rem;border-radius:7px;}
.nav-pills .nav-link.active{background:var(--primary);}
.grid-row{display:flex;align-items:center;gap:.5rem;padding:.28rem 0;border-bottom:1px solid #f0f4f8;}
.grid-row:last-child{border-bottom:none;}
.grid-tgl{width:80px;font-size:.75rem;flex-shrink:0;color:#555;}
.grid-sel{flex:1;}
.grid-sel select{font-size:.72rem;padding:.15rem .3rem;border-radius:5px;border:1px solid #dfe6e9;width:100%;transition:background .2s,color .2s;}
</style>
@endpush

@push('scripts')
<script>
// Data skema dari server
const SKEMA = @json(\App\Models\JadwalShift::SKEMA);
const SKEMA_COLORS = {
    gedung_air:'#0a3d62', sukarame:'#7b1fa2',
    bakauheni:'#c62828', 'default':'#263238'
};
const SHIFT_COLORS = {1:'#0a3d62',2:'#10ac84',3:'#ff9f43'};

// Data jadwal existing
@php
$existingJadwal = $jadwals->flatten()->mapWithKeys(function ($j) {
    return [
        \Carbon\Carbon::parse($j->tanggal)->format('Y-m-d') => [
            'shift'   => $j->shift,
            'user_id' => $j->user_id,
            'skema'   => $j->skema ?? 'default',
        ]
    ];
});
@endphp

const existingJadwal = @json($existingJadwal);


// Tab switch
function switchTab(tab){
    document.getElementById('form-harian').style.display  = tab==='harian'?'':'none';
    document.getElementById('form-bulanan').style.display = tab==='bulanan'?'':'none';
    document.getElementById('tab-harian').classList.toggle('active', tab==='harian');
    document.getElementById('tab-bulanan').classList.toggle('active', tab==='bulanan');
    if(tab==='bulanan') generateGrid();
}

// ── HARIAN: update shift options berdasar skema operator ──
function updateShiftOptionsHarian(){
    const sel     = document.getElementById('selOpHarian');
    const opt     = sel.options[sel.selectedIndex];
    const skema   = opt?.dataset?.skema || 'default';
    const shifts  = SKEMA[skema]?.shifts || {};
    const infoBox = document.getElementById('skemaInfoHarian');
    const namaEl  = document.getElementById('skemaNamaHarian');
    const contEl  = document.getElementById('shiftOptionsHarian');

    if(!sel.value){ contEl.innerHTML='<div class="text-muted small">Pilih operator dulu</div>'; infoBox.style.display='none'; return; }

    infoBox.style.display='block';
    namaEl.textContent = SKEMA[skema]?.label || skema;
    namaEl.style.color = SKEMA_COLORS[skema] || '#555';

    contEl.innerHTML = Object.entries(shifts).map(([no, sh]) => `
        <div class="form-check mb-2">
            <input type="radio" name="shift" value="${no}" id="sh${no}" class="form-check-input" required>
            <label for="sh${no}" class="form-check-label">
                <span class="badge me-1" style="background:${SHIFT_COLORS[no]||'#888'}">${sh.label}</span>
                <span class="mono text-muted" style="font-size:.78rem">${sh.mulai}–${sh.selesai}</span>
            </label>
        </div>
    `).join('');
}

// ── BULANAN: generate grid ──
function generateGrid(){
    const sel    = document.getElementById('selOpBulanan');
    const opt    = sel.options[sel.selectedIndex];
    const opId   = sel.value;
    const skema  = opt?.dataset?.skema || 'default';
    const bulan  = parseInt(document.getElementById('selBulan').value);
    const tahun  = parseInt(document.getElementById('selTahun').value);
    const shifts = SKEMA[skema]?.shifts || {};
    const btn    = document.getElementById('btnSimpanBulanan');

    // Info skema
    const infoBox  = document.getElementById('skemaInfoBulanan');
    const namaEl   = document.getElementById('skemaNamaBulanan');
    const shiftsEl = document.getElementById('skemaShiftsBulanan');
    const tplBox   = document.getElementById('templateCepat');

    if(!opId){
        document.getElementById('gridBulanan').innerHTML='<div class="text-muted text-center py-3 small">Pilih operator dulu</div>';
        btn.disabled=true; infoBox.style.display='none'; tplBox.style.display='none';
        return;
    }

    infoBox.style.display='block';
    namaEl.textContent  = SKEMA[skema]?.label || skema;
    namaEl.style.color  = SKEMA_COLORS[skema] || '#555';
    shiftsEl.innerHTML  = Object.entries(shifts).map(([no,sh])=>`<span class="me-2">S${no}: ${sh.label} (${sh.mulai}–${sh.selesai})</span>`).join('');

    // Template cepat
    tplBox.style.display='block';
    const btnTpl = document.getElementById('btnTemplates');
    const shiftNos = Object.keys(shifts);
    btnTpl.innerHTML = '';

    // Tombol tiap shift
    shiftNos.forEach(no=>{
        const b=document.createElement('button');
        b.type='button';b.className='btn btn-xs btn-outline-secondary';
        b.style.cssText='font-size:.7rem;padding:.2rem .5rem;';
        b.style.borderColor=SHIFT_COLORS[no]||'#888';b.style.color=SHIFT_COLORS[no]||'#888';
        b.textContent='Semua '+shifts[no].label;
        b.onclick=()=>setTemplate('all',no);
        btnTpl.appendChild(b);
    });

    // Rotasi
    if(shiftNos.length>=2){
        const br=document.createElement('button');
        br.type='button';br.className='btn btn-xs btn-outline-primary';
        br.style.cssText='font-size:.7rem;padding:.2rem .5rem;';
        br.textContent='Rotasi';
        br.onclick=()=>setTemplate('rotasi');
        btnTpl.appendChild(br);
    }

    // Kosongkan
    const bc=document.createElement('button');
    bc.type='button';bc.className='btn btn-xs btn-outline-secondary';
    bc.style.cssText='font-size:.7rem;padding:.2rem .5rem;';
    bc.textContent='Kosongkan';
    bc.onclick=()=>setTemplate('clear');
    btnTpl.appendChild(bc);

    // Generate grid
    const hari   = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    const jumlah = new Date(tahun,bulan,0).getDate();
    let html='';
    for(let d=1;d<=jumlah;d++){
        const tgl=`${tahun}-${String(bulan).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const dt=new Date(tgl); const hariNama=hari[dt.getDay()]; const isMinggu=dt.getDay()===0;
        let existShift='';
        if(opId && existingJadwal[tgl] && existingJadwal[tgl].user_id==opId) existShift=existingJadwal[tgl].shift;

        const opts=Object.entries(shifts).map(([no,sh])=>
            `<option value="${no}" ${existShift==no?'selected':''}>${sh.label} (${sh.mulai}–${sh.selesai})</option>`
        ).join('');

        html+=`<div class="grid-row">
            <div class="grid-tgl" style="${isMinggu?'color:#ee5a24;font-weight:600':''}">${d} ${hariNama}${isMinggu?' 🔴':''}</div>
            <div class="grid-sel">
                <select name="jadwal_harian[${tgl}]" class="grid-sel-input" data-skema="${skema}"
                        onchange="onShiftChange(this)">
                    <option value="">— Libur —</option>
                    ${opts}
                </select>
            </div>
        </div>`;
    }
    document.getElementById('gridBulanan').innerHTML=html;
    // Warnai yang sudah ada nilai
    document.querySelectorAll('.grid-sel-input').forEach(s=>{ if(s.value) onShiftChange(s); });
    btn.disabled=false;
}

function onShiftChange(sel){
    const no=sel.value;
    if(no){
        sel.style.background=SHIFT_COLORS[no]+'22'||'#f0f0f0';
        sel.style.color=SHIFT_COLORS[no]||'#333';
        sel.style.fontWeight='700'; sel.style.borderColor=SHIFT_COLORS[no]||'#dfe6e9';
    } else {
        sel.style.background=''; sel.style.color=''; sel.style.fontWeight=''; sel.style.borderColor='';
    }
}

function setTemplate(type, fixedNo=null){
    const sels = document.querySelectorAll('.grid-sel-input');
    const skema = document.getElementById('selOpBulanan').options[document.getElementById('selOpBulanan').selectedIndex]?.dataset?.skema||'default';
    const shiftNos = Object.keys(SKEMA[skema]?.shifts||{});
    sels.forEach((sel,idx)=>{
        if(type==='clear') sel.value='';
        else if(type==='all') sel.value=fixedNo;
        else if(type==='rotasi') sel.value=shiftNos[idx%shiftNos.length]||'';
        onShiftChange(sel);
    });
}
</script>
@endpush
