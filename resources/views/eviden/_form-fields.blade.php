@php
    $isEdit      = isset($eviden);
    $user        = auth()->user();
    $divisiLabel = \App\Models\User::DIVISI_LABEL;
    $lokasiVal   = old('lokasi', $isEdit ? ($eviden->lokasi ?? '') : '');
    // Cek apakah lokasi adalah custom (tidak ada di tabel)
    $lokasiNamaList = $lokasiList->pluck('nama')->toArray();
    $isCustomLokasi = $lokasiVal && !in_array($lokasiVal, $lokasiNamaList);
@endphp

<h6 class="section-title mb-3">📋 Informasi Kegiatan</h6>
<div class="row g-3 mb-4">
    <div class="col-12 col-md-8">
        <label class="form-label">Judul Kegiatan <span class="text-danger">*</span></label>
        <input type="text" name="judul" class="form-control"
               value="{{ old('judul', $isEdit ? $eviden->judul : '') }}" required>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label">Divisi <span class="text-danger">*</span></label>
        @if($user->isAdmin())
        <select name="divisi" class="form-select" required>
            @foreach($divisiLabel as $val => $label)
            <option value="{{ $val }}"
                {{ old('divisi', $isEdit ? ($eviden->divisi ?? $divisiDefault) : $divisiDefault) === $val ? 'selected':'' }}>
                {{ $label }}
            </option>
            @endforeach
        </select>
        @else
        <input type="text" class="form-control" value="{{ $divisiLabel[$divisiDefault] ?? $divisiDefault }}" disabled>
        <input type="hidden" name="divisi" value="{{ $divisiDefault }}">
        @endif
    </div>
    <div class="col-12">
        <label class="form-label">Deskripsi / Uraian Pekerjaan</label>
        <textarea name="deskripsi" class="form-control" rows="4">{{ old('deskripsi', $isEdit ? $eviden->deskripsi : '') }}</textarea>
    </div>
</div>

<h6 class="section-title mb-3">⏰ Waktu & Lokasi</h6>
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
        <input type="date" name="tanggal" class="form-control"
               value="{{ old('tanggal', $isEdit ? $eviden->tanggal->format('Y-m-d') : now()->toDateString()) }}" required>
    </div>
    <div class="col-6 col-md-4">
        <label class="form-label">Jam Mulai <span class="text-danger">*</span></label>
        <input type="time" name="jam_mulai" class="form-control mono"
               value="{{ old('jam_mulai', $isEdit ? substr($eviden->jam_mulai,0,5) : now()->format('H:i')) }}" required>
    </div>
    <div class="col-6 col-md-4">
        <label class="form-label">Jam Selesai <span class="text-danger">*</span></label>
        <input type="time" name="jam_selesai" class="form-control mono"
               value="{{ old('jam_selesai', $isEdit ? substr($eviden->jam_selesai,0,5) : '') }}" required>
    </div>

    {{-- ── LOKASI dari tabel lokasis ── --}}
    <div class="col-12">
        <label class="form-label">Lokasi Kegiatan</label>
        <select name="lokasi" id="selectLokasi" class="form-select" onchange="toggleLokasiCustom(this)">
            <option value="">— Pilih Lokasi —</option>
            @foreach(\App\Models\Lokasi::orderBy('nama')->get() as $lok)
            <option value="{{ $lok->nama }}"
                {{ (!$isCustomLokasi && $lokasiVal===$lok->nama) ? 'selected':'' }}>
                {{ $lok->nama }}
            </option>
            @endforeach
            <option value="__custom__" {{ $isCustomLokasi ? 'selected':'' }}>Lainnya (isi manual)</option>
        </select>
        <input type="text" id="inputLokasiCustom" class="form-control mt-2"
               placeholder="Ketik lokasi..."
               value="{{ $isCustomLokasi ? $lokasiVal : '' }}"
               style="display:{{ $isCustomLokasi ? 'block':'none' }}">
    </div>

    <div class="col-12">
        <label class="form-label">Supervisi / Pengelola yang Hadir</label>
        <input type="text" name="supervisi" class="form-control"
               value="{{ old('supervisi', $isEdit ? $eviden->supervisi : '') }}"
               placeholder="Nama supervisi (opsional)">
    </div>
</div>

<h6 class="section-title mb-3">👥 Personil Terlibat</h6>
<div class="mb-4">
    <label class="form-label">
        Operator Terlibat
        <span class="text-muted" style="font-size:.72rem">— semua lokasi dinas (termasuk tenaga bantuan)</span>
    </label>
    <div class="row g-2">
        @foreach($operators as $op)
        @php
            $checked = $isEdit && $eviden->operators->contains('id', $op->id);
        @endphp
        <div class="col-6 col-md-4 col-lg-3">
            <div class="form-check p-2 rounded" id="opWrap{{ $op->id }}"
                 style="background:var(--bg);border:1px solid {{ $checked?'var(--primary)':'var(--border)' }};transition:border-color .15s">
                <input type="checkbox" name="operator_ids[]" value="{{ $op->id }}"
                       id="op{{ $op->id }}" class="form-check-input"
                       {{ $checked ? 'checked' : '' }}
                       onchange="this.closest('[id^=opWrap]').style.borderColor=this.checked?'var(--primary)':'var(--border)'">
                <label for="op{{ $op->id }}" class="form-check-label" style="font-size:.78rem">
                    <div class="fw-bold">
                        {{ $op->name }}
                        @if($op->isAdminDivisi())
                        <i class="bi bi-patch-check-fill text-warning" style="font-size:.7rem" title="Admin Divisi"></i>
                        @endif
                    </div>
                    @if($op->lokasi_dinas)
                    <small class="text-muted">{{ $op->lokasi_dinas }}</small>
                    @endif
                    <div class="mt-1">
                        @if($op->divisi && $op->divisi !== 'transmisi')
                        <small class="badge" style="background:{{ ['studio'=>'#7b1fa2','sarana'=>'#10ac84'][$op->divisi]??'#888' }};font-size:.55rem">
                            {{ \App\Models\User::DIVISI_LABEL[$op->divisi] ?? $op->divisi }}
                        </small>
                        @endif
                        @if($op->isAdminDivisi())
                        <small class="badge bg-warning text-dark" style="font-size:.55rem">Admin Divisi</small>
                        @endif
                    </div>
                </label>
            </div>
        </div>
        @endforeach
    </div>
</div>
