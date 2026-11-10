@extends('layouts.app')
@section('title','Autentikasi 2 Langkah')
@section('page-title','Autentikasi 2 Langkah (2FA)')

@section('content')
<div class="row justify-content-center"><div class="col-12 col-md-7 col-lg-5">
<div class="card">
    <div class="card-header"><i class="bi bi-shield-lock me-2 text-success"></i>2FA untuk pemulihan password</div>
    <div class="card-body">
    @if($user->hasTwoFactor())
        <div class="alert alert-success" style="font-size:.85rem"><i class="bi bi-check-circle me-1"></i>2FA <b>aktif</b>. Bila lupa password, gunakan tautan <b>Lupa password?</b> di halaman login.</div>
        <form method="POST" action="{{ route('two-factor.nonaktifkan') }}" onsubmit="return confirm('Nonaktifkan 2FA?')">
            @csrf
            <label class="form-label">Password untuk menonaktifkan</label>
            <input type="password" name="password" class="form-control mb-2 @error('password') is-invalid @enderror" required>
            @error('password')<div class="text-danger mb-2" style="font-size:.8rem">{{ $message }}</div>@enderror
            <button class="btn btn-outline-danger btn-sm">Nonaktifkan 2FA</button>
        </form>
    @else
        <p style="font-size:.85rem">Aktifkan 2FA agar Anda dapat mengganti password sendiri bila lupa. Langkah:</p>
        <ol style="font-size:.85rem">
            <li>Pasang aplikasi authenticator (Google Authenticator / Microsoft Authenticator / Authy).</li>
            <li>Pindai kode QR di bawah, atau masukkan kunci secara manual.</li>
            <li>Ketik kode 6 digit yang muncul, lalu tekan <b>Aktifkan</b>.</li>
        </ol>
        <div class="text-center mb-2"><div id="qr" style="display:inline-block"></div></div>
        <div class="mb-3" style="font-size:.78rem">Kunci manual:
            <code style="user-select:all;word-break:break-all">{{ $secret }}</code>
        </div>
        <form method="POST" action="{{ route('two-factor.aktifkan') }}">
            @csrf
            <label class="form-label">Kode 6 digit</label>
            <input type="text" name="kode" class="form-control mb-1 @error('kode') is-invalid @enderror" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required>
            @error('kode')<div class="text-danger mb-2" style="font-size:.8rem">{{ $message }}</div>@enderror
            <button class="btn btn-primary-custom mt-2 w-100"><i class="bi bi-shield-check me-1"></i>Aktifkan 2FA</button>
        </form>
    @endif
    </div>
</div>
</div></div>
@endsection

@if(!$user->hasTwoFactor())
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function(){
    try { new QRCode(document.getElementById('qr'), { text: @json($uri), width: 180, height: 180 }); }
    catch(e){ document.getElementById('qr').textContent = 'QR tidak dapat dimuat, gunakan kunci manual.'; }
})();
</script>
@endpush
@endif
