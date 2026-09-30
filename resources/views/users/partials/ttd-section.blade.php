{{--
    Partial: Section upload TTD di halaman profil user
    Include: @include('users.partials.ttd-section')
--}}
<div class="card mt-3 mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-pen me-2"></i>Tanda Tangan Digital</h6>
    </div>
    <div class="card-body">

        @if(session('success') && request()->routeIs('users.profile'))
        <div class="alert alert-success py-2 mb-3" style="font-size:.82rem">{{ session('success') }}</div>
        @endif
        @if($errors->has('ttd'))
        <div class="alert alert-danger py-2 mb-2" style="font-size:.78rem">{{ $errors->first('ttd') }}</div>
        @endif

        <div class="row g-3">
            {{-- Preview TTD saat ini --}}
            <div class="col-12 col-md-5">
                <div style="font-size:.82rem;font-weight:600;margin-bottom:6px">Tanda Tangan Saat Ini</div>
                @if(auth()->user()->ttd_path)
                    @php
                        $ttdStoragePath = storage_path('app/public/' . auth()->user()->ttd_path);
                        $ttdExists = file_exists($ttdStoragePath);
                    @endphp
                    @if($ttdExists)
                    <div style="border:1px solid #ddd;border-radius:8px;padding:12px;background:#fff;
                                display:flex;align-items:center;justify-content:center;min-height:100px">
                        <img src="{{ auth()->user()->ttd_url }}" alt="TTD {{ auth()->user()->name }}"
                             style="max-height:90px;max-width:220px;object-fit:contain"
                             onerror="this.parentElement.innerHTML='<span style=\'color:#e74c3c;font-size:.78rem\'>Gagal memuat gambar</span>'">
                    </div>
                    <form action="{{ route('users.hapus-ttd-self') }}" method="POST" class="mt-2"
                          onsubmit="return confirm('Hapus tanda tangan Anda?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger w-100">
                            <i class="bi bi-trash me-1"></i>Hapus Tanda Tangan
                        </button>
                    </form>
                    @else
                    {{-- File tidak ada di disk meski path tersimpan di DB --}}
                    <div style="border:2px dashed #ffc107;border-radius:8px;padding:16px;text-align:center;
                                color:#856404;min-height:80px;display:flex;align-items:center;justify-content:center">
                        <div>
                            <i class="bi bi-exclamation-triangle d-block fs-4 mb-1"></i>
                            <span style="font-size:.78rem">File TTD tidak ditemukan di server.<br>Silakan upload ulang.</span>
                        </div>
                    </div>
                    @endif
                @else
                <div style="border:2px dashed #ddd;border-radius:8px;padding:20px;text-align:center;
                            color:#aaa;min-height:100px;display:flex;align-items:center;justify-content:center">
                    <div>
                        <i class="bi bi-pen d-block fs-3 mb-1"></i>
                        <span style="font-size:.78rem">Belum ada tanda tangan</span>
                    </div>
                </div>
                @endif
            </div>

            {{-- Upload TTD baru --}}
            <div class="col-12 col-md-7">
                <div style="font-size:.82rem;font-weight:600;margin-bottom:6px">
                    {{ auth()->user()->ttd_path ? 'Ganti' : 'Upload' }} Tanda Tangan
                </div>
                <form action="{{ route('users.update-ttd-self') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Drop area --}}
                    <div id="ttd-drop-area"
                         style="border:2px dashed #ccc;border-radius:8px;padding:16px;
                                background:#fafafa;text-align:center;cursor:pointer;transition:border-color .2s"
                         onclick="document.getElementById('ttd-file-input').click()">
                        <input type="file" name="ttd" id="ttd-file-input"
                               accept="image/png,image/jpeg,image/jpg"
                               class="d-none" onchange="previewTtdProfile(this)">
                        <div id="ttd-drop-placeholder">
                            <i class="bi bi-camera-fill fs-3 text-muted d-block mb-1"></i>
                            <div style="font-size:.8rem;color:#666">Tap untuk pilih / foto tanda tangan</div>
                            <div style="font-size:.72rem;color:#aaa">PNG atau JPG • Max 1MB</div>
                        </div>
                        <div id="ttd-preview-wrap-profile" style="display:none">
                            <img id="ttd-preview-img-profile" src="" alt="Preview"
                                 style="max-height:80px;max-width:200px;object-fit:contain">
                            <div style="font-size:.72rem;color:#888;margin-top:4px">Tap untuk ganti</div>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-2 mb-2 py-2" style="font-size:.75rem">
                        <i class="bi bi-info-circle text-primary me-1"></i>
                        TTD akan tampil di kolom <strong>"Tanda Tangan"</strong> pada laporan PDF eviden.<br>
                        Gunakan <strong>PNG transparan</strong> untuk hasil terbaik di PDF.
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100"
                            id="btn-upload-ttd-profile" disabled>
                        <i class="bi bi-cloud-upload me-1"></i>Simpan Tanda Tangan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function previewTtdProfile(input) {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 1024 * 1024) {
        alert('Ukuran file terlalu besar. Maksimal 1MB.');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('ttd-drop-placeholder').style.display = 'none';
        document.getElementById('ttd-preview-img-profile').src = e.target.result;
        document.getElementById('ttd-preview-wrap-profile').style.display = 'block';
        document.getElementById('btn-upload-ttd-profile').disabled = false;
    };
    reader.readAsDataURL(file);
}

// Drag & drop
(function(){
    const da = document.getElementById('ttd-drop-area');
    if (!da) return;
    ['dragenter','dragover'].forEach(e => da.addEventListener(e, function(ev) {
        ev.preventDefault();
        da.style.borderColor = '#0a3d62';
        da.style.background  = '#f0f4f8';
    }));
    ['dragleave','drop'].forEach(e => da.addEventListener(e, function(ev) {
        ev.preventDefault();
        da.style.borderColor = '#ccc';
        da.style.background  = '#fafafa';
    }));
    da.addEventListener('drop', function(ev) {
        const files = ev.dataTransfer.files;
        if (files && files.length) {
            const input = document.getElementById('ttd-file-input');
            const dt = new DataTransfer();
            dt.items.add(files[0]);
            input.files = dt.files;
            previewTtdProfile(input);
        }
    });
})();
</script>
@endpush