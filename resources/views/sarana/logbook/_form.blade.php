{{-- Form Logbook Sarana. $log = model (edit) / null (create); $butir = daftar butir; --}}
@php
    $log = $log ?? null;
    $shiftList = \App\Models\SaranaLog::shiftList();
    $shiftCur = (int) old('shift', $log->shift ?? ($shiftDefault ?? 1));
    $existing = $log ? $log->items->keyBy('kunci') : collect();
@endphp

@if($errors->any())
<div class="alert alert-danger py-2 mb-3"><ul class="mb-0 ps-3">
    @foreach($errors->all() as $e)<li style="font-size:.82rem">{{ $e }}</li>@endforeach
</ul></div>
@endif
@if(session('warning'))<div class="alert alert-warning py-2">{{ session('warning') }}</div>@endif

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock me-2"></i>Informasi Dinas</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                <input type="date" name="tanggal" class="form-control" required
                       value="{{ old('tanggal', $log ? $log->tanggal->toDateString() : now()->toDateString()) }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Shift <span class="text-danger">*</span></label>
                <select name="shift" id="sl-shift" class="form-select" required>
                    @foreach($shiftList as $no => $s)
                    <option value="{{ $no }}" data-mulai="{{ $s['mulai'] }}" data-selesai="{{ $s['selesai'] }}" {{ $shiftCur === (int) $no ? 'selected' : '' }}>
                        {{ $s['label'] }} ({{ $s['mulai'] }} - {{ $s['selesai'] }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                <input type="time" name="jam_mulai" id="sl-mulai" class="form-control" required
                       value="{{ old('jam_mulai', $log ? substr($log->jam_mulai, 0, 5) : ($shiftList[$shiftCur]['mulai'] ?? '')) }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Jam Selesai</label>
                <input type="time" name="jam_selesai" id="sl-selesai" class="form-control"
                       value="{{ old('jam_selesai', $log ? ($log->jam_selesai ? substr($log->jam_selesai, 0, 5) : '') : ($shiftList[$shiftCur]['selesai'] ?? '')) }}">
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex align-items-center">
        <h6 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Log Harian</h6>
        <span class="ms-auto text-muted" style="font-size:.72rem">Pilih "Ada Kendala" lalu isi keterangan</span>
    </div>
    <div class="card-body p-0">
        @foreach($butir as $b)
        @php
            $k = $b['kunci'];
            $ex = $existing->get($k);
            $kond = old("item.$k.kondisi", $ex->kondisi ?? 'normal');
            $ket  = old("item.$k.ket", $ex->keterangan ?? '');
            $lblOk = !empty($b['lancar']) ? 'Lancar' : 'Normal';
        @endphp
        <div class="sl-butir p-3 {{ !$loop->last ? 'border-bottom' : '' }}" style="{{ $kond === 'kendala' ? 'background:#fff5f5' : '' }}">
            <div class="fw-semibold" style="font-size:.86rem">{{ $b['label'] }}</div>
            @if($b['desc'])<div class="text-muted" style="font-size:.72rem">*{{ $b['desc'] }}</div>@endif
            <div class="d-flex gap-2 flex-wrap mt-2">
                <label class="btn btn-sm" style="border:2px solid #27ae60;cursor:pointer">
                    <input type="radio" name="item[{{ $k }}][kondisi]" value="normal" class="form-check-input me-1 sl-rad" {{ $kond !== 'kendala' ? 'checked' : '' }} required>{{ $lblOk }}
                </label>
                <label class="btn btn-sm" style="border:2px solid #e74c3c;cursor:pointer">
                    <input type="radio" name="item[{{ $k }}][kondisi]" value="kendala" class="form-check-input me-1 sl-rad" {{ $kond === 'kendala' ? 'checked' : '' }}>Ada Kendala
                </label>
            </div>
            <textarea name="item[{{ $k }}][ket]" rows="2" maxlength="1000" class="form-control form-control-sm mt-2 sl-ket"
                      style="{{ $kond === 'kendala' ? '' : 'display:none' }}"
                      placeholder="Jelaskan kendala dan tindakan yang dilakukan (wajib)">{{ $ket }}</textarea>
        </div>
        @endforeach
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Catatan Petugas Dinas</h6>
        <span id="sl-count" class="ms-auto text-muted" style="font-size:.72rem"></span>
    </div>
    <div class="card-body">
        <div class="text-muted mb-2" style="font-size:.74rem">Catatan ini berfungsi untuk menginfokan ke petugas berikutnya.</div>
        <div class="d-flex gap-2 flex-wrap align-items-center mb-2">
            <button type="button" id="sl-mic" class="btn btn-outline-danger btn-sm"><i class="bi bi-mic-fill me-1"></i><span>Mulai Dikte (Suara)</span></button>
            <label class="form-check mb-0" style="font-size:.74rem">
                <input type="checkbox" id="sl-tanda" class="form-check-input" checked>
                <span class="form-check-label">Ucapan &quot;titik / koma / baris baru&quot; jadi tanda baca</span>
            </label>
            <span id="sl-stat" class="text-muted" style="font-size:.74rem"></span>
        </div>
        <textarea name="catatan_petugas" id="sl-catatan" class="form-control" rows="6"
                  style="font-size:.92rem;line-height:1.55;min-height:150px"
                  placeholder="Tulis informasi untuk petugas shift berikutnya...">{{ old('catatan_petugas', $log->catatan_petugas ?? '') }}</textarea>
        <div id="sl-interim" class="mt-1" style="font-size:.8rem;color:#888;font-style:italic;min-height:1.1rem"></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-camera me-2"></i>Foto Dokumentasi <small class="text-muted">(opsional)</small></h6></div>
    <div class="card-body">
        @include('components.foto-multi-ket', [
            'capName'  => $log ? 'foto_keterangan_baru' : 'foto_keterangan',
            'existing' => $log ? $log->fotos : null,
        ])
    </div>
</div>

@push('scripts')
<script>
(function(){
    const sel = document.getElementById('sl-shift');
    sel.addEventListener('change', function(){
        const o = sel.options[sel.selectedIndex];
        document.getElementById('sl-mulai').value = o.dataset.mulai || '';
        document.getElementById('sl-selesai').value = o.dataset.selesai || '';
    });
    document.querySelectorAll('.sl-butir').forEach(function(box){
        const ket = box.querySelector('.sl-ket');
        box.querySelectorAll('.sl-rad').forEach(function(r){
            r.addEventListener('change', function(){
                const kendala = box.querySelector('.sl-rad[value="kendala"]').checked;
                ket.style.display = kendala ? '' : 'none';
                ket.required = kendala;
                box.style.background = kendala ? '#fff5f5' : '';
                if (kendala) ket.focus();
            });
        });
        ket.required = box.querySelector('.sl-rad[value="kendala"]').checked;
    });
})();
</script>
@endpush
@include('sarana.logbook._dikte-js')
