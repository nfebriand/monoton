@extends('layouts.app')
@section('title','Detail Log')
@section('page-title','Detail Log Operasional')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="{{ route('operasional.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <span class="fw-bold">Log #{{ $operasional->id }}</span>
    @if($operasional->status === 'off')
    <span class="badge bg-warning text-dark"><i class="bi bi-power me-1"></i>OFF — tidak dihitung rata-rata</span>
    @endif
    <span class="badge bg-secondary ms-auto mono">{{ $operasional->dicatat_pada->format('d/m/Y H:i') }}</span>
    @if(auth()->user()->isAdmin() || auth()->id() === $operasional->user_id)
    <a href="{{ route('operasional.edit',$operasional) }}" class="btn btn-sm btn-warning">
        <i class="bi bi-pencil me-1"></i>Edit
    </a>
    @endif
</div>

{{-- Info Header --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="text-muted" style="font-size:.7rem">Pemancar</div>
                <div class="fw-bold" style="font-size:.88rem">{{ $operasional->pemancar->nama_stasiun }}</div>
                <small class="text-muted">{{ $operasional->pemancar->merk }} {{ $operasional->pemancar->tipe_unit }}</small>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted" style="font-size:.7rem">Operator</div>
                <div class="fw-bold" style="font-size:.88rem">{{ $operasional->user->name }}</div>
                <small class="text-muted">{{ $operasional->user->lokasi_dinas ?? '' }}</small>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted" style="font-size:.7rem">Shift</div>
                <div class="fw-bold" style="font-size:.88rem">
                    @if($operasional->jadwalShift)
                        Shift {{ $operasional->jadwalShift->shift }}
                        <span class="mono text-muted" style="font-size:.75rem">
                            {{ $operasional->jadwalShift->jam_mulai }}–{{ $operasional->jadwalShift->jam_selesai }}
                        </span>
                    @else <span class="text-muted">–</span> @endif
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted" style="font-size:.7rem">Waktu Catat</div>
                <div class="fw-bold mono" style="font-size:.88rem">{{ $operasional->dicatat_pada->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- VSWR besar --}}
    @if($operasional->vswr_final)
    @php $v=$operasional->vswr_final; $c=$v<=1.5?'#10ac84':($v<=2?'#ff9f43':'#ee5a24'); @endphp
    <div class="col-12">
        <div class="card" style="border-left:4px solid {{ $c }}">
            <div class="card-body d-flex align-items-center gap-4 py-3 flex-wrap">
                <div class="text-center">
                    <div style="font-size:.68rem;color:#636e72;text-transform:uppercase">VSWR Final</div>
                    <div class="mono fw-bold" style="font-size:2.2rem;color:{{ $c }};line-height:1">
                        {{ number_format($v,3) }}
                    </div>
                    <div>{{ $vswrStatus ? $vswrStatus['icon'].' '.$vswrStatus['label'] : '' }}</div>
                </div>
                @if($operasional->return_loss_final)
                <div class="text-center">
                    <div style="font-size:.68rem;color:#636e72;text-transform:uppercase">Return Loss</div>
                    <div class="mono fw-bold" style="font-size:1.4rem">{{ number_format($operasional->return_loss_final,2) }} dB</div>
                </div>
                @endif
                <div class="ms-auto text-muted" style="font-size:.78rem">
                    Kapasitas Pemancar: <strong class="mono">{{ number_format($operasional->pemancar->kapasitas_output_final,0) }} W</strong>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Output --}}
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-lightning-charge me-2 text-warning"></i>Output Power</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @foreach([
                        ['Output Final PA', $operasional->output_final_pa],
                        ['Output Driver',   $operasional->output_driver],
                        ['Output Exciter',  $operasional->output_exciter],
                    ] as [$lbl,$val])
                    <tr>
                        <td class="ps-3 text-muted">{{ $lbl }}</td>
                        <td class="pe-3 text-end mono fw-bold">{{ $val ? number_format($val,2).' W':'–' }}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>

    {{-- Reflected --}}
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-arrow-return-left me-2 text-danger"></i>Reflected & Reject</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    @foreach([
                        ['Reflect Final', $operasional->reflect_final],
                        ['Reject Final',  $operasional->reject_final],
                    ] as [$lbl,$val])
                    <tr>
                        <td class="ps-3 text-muted">{{ $lbl }}</td>
                        <td class="pe-3 text-end mono fw-bold">{{ $val ? number_format($val,2).' W':'–' }}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>

    {{-- Suhu & Kelembaban --}}
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-thermometer-half me-2 text-info"></i>Suhu & Kelembaban</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="ps-3 text-muted">Suhu Pemancar</td>
                        <td class="pe-3 text-end mono fw-bold">{{ $operasional->suhu_pemancar!==null?$operasional->suhu_pemancar.'°C':'–' }}</td>
                    </tr>
                    <tr>
                        <td class="ps-3 text-muted">Suhu Ruangan</td>
                        <td class="pe-3 text-end mono fw-bold">{{ $operasional->suhu_ruangan!==null?$operasional->suhu_ruangan.'°C':'–' }}</td>
                    </tr>
                    <tr>
                        <td class="ps-3 text-muted">Kelembaban</td>
                        <td class="pe-3 text-end mono fw-bold">{{ $operasional->kelembaban!==null?$operasional->kelembaban.'%':'–' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Keterangan --}}
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-chat-text me-2"></i>Keterangan</span>
                @if(auth()->user()->isAdmin() || auth()->id() === $operasional->user_id)
                <a href="{{ route('operasional.edit',$operasional) }}" class="btn btn-sm btn-outline-warning" style="font-size:.72rem;padding:.15rem .5rem">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                @endif
            </div>
            <div class="card-body" style="font-size:.88rem">
                {{ $operasional->keterangan ?: '–' }}
            </div>
        </div>
    </div>
</div>
@endsection
