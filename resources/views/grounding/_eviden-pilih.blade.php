{{-- Pilihan eviden untuk pengukuran. Variabel: $kandidat, $evidenDipilih, $buatUrl --}}
<div class="card mb-3"><div class="card-header d-flex align-items-center"><h6 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Kaitkan dengan Eviden <small class="text-muted">(opsional)</small></h6>
    @isset($buatUrl)<a href="{{ $buatUrl }}" class="btn btn-sm btn-outline-primary ms-auto" style="font-size:.72rem"><i class="bi bi-camera me-1"></i>Buat Eviden Baru</a>@endisset</div>
<div class="card-body">
    <select name="eviden_id" class="form-select">
        <option value="">&mdash; Tanpa eviden &mdash;</option>
        @foreach($kandidat as $ev)
        <option value="{{ $ev->id }}" {{ (string) old('eviden_id', $evidenDipilih ?? '') === (string) $ev->id ? 'selected' : '' }}>{{ $ev->tanggal->format('d/m/Y') }} &mdash; {{ \Illuminate\Support\Str::limit($ev->judul, 70) }}</option>
        @endforeach
    </select>
    <div class="form-text">Menampilkan eviden Anda dalam 120 hari terakhir. Belum ada? Klik <em>Buat Eviden Baru</em> (isian pengukuran di halaman ini belum tersimpan, jadi buat eviden dulu sebelum mengisi nilai).</div>
</div></div>
