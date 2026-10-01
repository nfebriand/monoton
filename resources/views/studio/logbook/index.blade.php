@extends('layouts.app')
@section('title','Logbook Studio')
@section('page-title','Logbook Harian Studio')

@section('content')

<div class="row g-2 mb-3">
    <div class="col-6 col-md-2">
        <div class="card text-center"><div class="card-body py-2">
            <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Total Bulan Ini</div>
            <div class="mono fw-bold" style="font-size:1.5rem;color:var(--primary)">{{ $ringkasan['total'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center"><div class="card-body py-2">
            <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Ada Gangguan</div>
            <div class="mono fw-bold" style="font-size:1.5rem;color:{{ $ringkasan['gangguan']>0?'var(--danger)':'var(--success)' }}">{{ $ringkasan['gangguan'] }}</div>
        </div></div>
    </div>
    <div class="col-4 col-md-2">
        <div class="card text-center"><div class="card-body py-2">
            <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Shift Pagi</div>
            <div class="mono fw-bold" style="font-size:1.5rem">{{ $ringkasan['pagi'] }}</div>
        </div></div>
    </div>
    <div class="col-4 col-md-2">
        <div class="card text-center"><div class="card-body py-2">
            <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Shift Siang</div>
            <div class="mono fw-bold" style="font-size:1.5rem">{{ $ringkasan['siang'] }}</div>
        </div></div>
    </div>
    <div class="col-4 col-md-2">
        <div class="card text-center"><div class="card-body py-2">
            <div style="font-size:.62rem;color:var(--muted);text-transform:uppercase">Shift Malam</div>
            <div class="mono fw-bold" style="font-size:1.5rem">{{ $ringkasan['malam'] }}</div>
        </div></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Dari Tanggal</label>
                <input type="date" name="tanggal_dari" class="form-control form-control-sm" value="{{ request('tanggal_dari') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Sampai Tanggal</label>
                <input type="date" name="tanggal_sampai" class="form-control form-control-sm" value="{{ request('tanggal_sampai') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Shift</label>
                <select name="shift" class="form-select form-select-sm">
                    <option value="">Semua Shift</option>
                    @foreach(\App\Models\StudioLog::SHIFT as $val=>$label)
                    <option value="{{ $val }}" {{ request('shift')==$val?'selected':'' }}>{{ ucfirst($val) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Kondisi</label>
                <select name="kondisi" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="gangguan" {{ request('kondisi')=='gangguan'?'selected':'' }}>Ada Gangguan</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" style="font-size:.7rem">Operator</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">Semua Operator</option>
                    @foreach($operators as $op)
                    <option value="{{ $op->id }}" {{ request('user_id')==$op->id?'selected':'' }}>{{ $op->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-sm btn-primary-custom flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('studio.logbook.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="mb-2">
<div class="d-flex gap-2 flex-wrap align-items-center mb-2">
    {{-- Cetak Harian --}}
        <a href="{{ route('studio.logbook.cetak-harian', array_filter([
                'tanggal' => request('tanggal_dari', now()->toDateString()),
                'user_id' => request('user_id'),
            ])) }}"
           class="btn btn-sm btn-outline-secondary" target="_blank">
            <i class="bi bi-printer me-1"></i>Cetak Harian
        </a>
        {{-- Rekap Bulanan --}}
        <a href="{{ route('studio.logbook.cetak-bulanan', array_filter([
                'bulan'   => request('tanggal_dari') ? substr(request('tanggal_dari'),0,7) : now()->format('Y-m'),
                'user_id' => request('user_id'),
            ])) }}"
           class="btn btn-sm btn-outline-secondary" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i>Rekap Bulanan
        </a>
        {{-- Badge Operator --}}
        @if(request('user_id'))
        <span class="badge bg-info align-self-center" style="font-size:.72rem">
            <i class="bi bi-person me-1"></i>Filter: {{ $operators->find(request('user_id'))?->name ?? 'Operator' }}
        </span>
        @endif
</div>

{{-- Baris Bulk Cetak + Isi Logbook --}}
<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
    <form action="{{ route('studio.logbook.bulk-cetak') }}" method="GET" target="_blank" id="form-bulk-cetak">
        @if(request('tanggal_dari'))
        <input type="hidden" name="tanggal_dari" value="{{ request('tanggal_dari') }}">
        @endif
        @if(request('tanggal_sampai'))
        <input type="hidden" name="tanggal_sampai" value="{{ request('tanggal_sampai') }}">
        @endif
        @if(request('user_id'))
        <input type="hidden" name="user_id" value="{{ request('user_id') }}">
        @endif
        @if(request('shift'))
        <input type="hidden" name="shift" value="{{ request('shift') }}">
        @endif
        <button type="button" class="btn btn-danger" onclick="konfirmasiBulkCetak()">
            <i class="bi bi-file-earmark-pdf me-1"></i>
            Cetak Semua PDF
            <span class="badge bg-white text-danger ms-1">{{ $logs->total() }}</span>
        </button>
    </form>
    <a href="{{ route('studio.logbook.create') }}" class="btn btn-primary-custom">
        <i class="bi bi-plus-circle me-1"></i>Isi Logbook Shift
    </a>
</div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tanggal</th>
                        <th>Shift</th>
                        <th>Jam Dinas</th>
                        <th>Kondisi Umum</th>
                        <th>Catatan</th>
                        <th>Petugas</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="ps-3 mono fw-bold" style="font-size:.8rem">{{ $log->tanggal->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge" style="font-size:.68rem;background:{{ $log->shift=='pagi'?'#f39c12':($log->shift=='siang'?'#27ae60':'#2c3e50') }}">
                                {{ ucfirst($log->shift) }}
                            </span>
                        </td>
                        <td class="mono" style="font-size:.8rem">
                            {{ substr($log->jam_mulai,0,5) }}{{ $log->jam_selesai?' - '.substr($log->jam_selesai,0,5):'' }}
                        </td>
                        <td>
                            @if($log->ada_gangguan)
                            <span class="badge bg-danger" style="font-size:.68rem"><i class="bi bi-exclamation-triangle me-1"></i>Ada Gangguan</span>
                            @else
                            <span class="badge bg-success" style="font-size:.68rem"><i class="bi bi-check-circle me-1"></i>Normal</span>
                            @endif
                        </td>
                        <td style="font-size:.78rem;max-width:150px">
                            {{ $log->catatan_petugas ? \Illuminate\Support\Str::limit($log->catatan_petugas, 40) : '-' }}
                        </td>
                        <td style="font-size:.8rem">{{ $log->user->name }}</td>
                        <td class="text-end pe-3">
                            <a href="{{ route('studio.logbook.show',$log) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            @if(auth()->user()->isAdmin() || auth()->user()->isAdminDivisi() || auth()->id()===$log->user_id)
                            <a href="{{ route('studio.logbook.edit',$log) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-journal-text d-block fs-2 mb-1"></i>Belum ada logbook studio
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="px-3 py-2 border-top">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@push('scripts')
<script>
function konfirmasiBulkCetak() {
    const total = {{ $logs->total() }};
    if (total === 0) { alert('Tidak ada data logbook untuk dicetak.'); return; }
    let msg = 'Akan mencetak ' + total + ' logbook dalam 1 file PDF.';
    if (total > 50) msg += '\n(Maksimal 50 logbook per cetak, sisanya tidak tercetak)';
    msg += '\n\nProses ini membutuhkan beberapa detik.\nLanjutkan?';
    if (confirm(msg)) document.getElementById('form-bulk-cetak').submit();
}
</script>
@endpush
@endsection