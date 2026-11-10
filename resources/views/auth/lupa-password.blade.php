<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MonOTOn — Lupa Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @php $tema = \App\Models\AppSetting::allKeyed()['tema_warna'] ?? '#0a3d62'; @endphp
    <style>
        body{background:#030d1a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;font-family:system-ui,sans-serif}
        .box{background:#fff;border-radius:14px;max-width:420px;width:100%;padding:1.6rem}
        .btn-p{background:{{ $tema }};color:#fff;font-weight:600}
        .btn-p:hover{color:#fff;opacity:.9}
    </style>
</head>
<body>
<div class="box">
    <h5 class="mb-1" style="color:{{ $tema }}"><i class="bi bi-shield-lock me-2"></i>Lupa Password</h5>
    @if($langkah==='email')
        <p class="text-muted" style="font-size:.82rem">Masukkan email akun Anda. Pemulihan memakai kode 6 digit dari aplikasi authenticator (2FA) yang sudah Anda aktifkan.</p>
    @elseif($langkah==='kode')
        <p class="text-muted" style="font-size:.82rem">Buka aplikasi authenticator (Google Authenticator, Microsoft Authenticator, dll) lalu masukkan kode 6 digit untuk <b>MonOTOn</b>.</p>
    @else
        <p class="text-muted" style="font-size:.82rem">Verifikasi berhasil. Buat password baru (minimal 8 karakter, bukan password standar).</p>
    @endif

    @if($errors->any())
    <div class="alert alert-danger py-2" style="font-size:.8rem">{{ $errors->first() }}</div>
    @endif

    @if($langkah==='email')
    <form method="POST" action="{{ route('password.forgot.post') }}">@csrf
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control mb-3" value="{{ old('email') }}" required autofocus>
        <button class="btn btn-p w-100">Lanjut</button>
    </form>
    @elseif($langkah==='kode')
    <form method="POST" action="{{ route('password.forgot.kode.post') }}">@csrf
        <label class="form-label">Kode 2FA</label>
        <input type="text" name="kode" class="form-control mb-3" inputmode="numeric" autocomplete="one-time-code" maxlength="7" placeholder="123456" required autofocus style="letter-spacing:.3rem;font-size:1.2rem;text-align:center">
        <button class="btn btn-p w-100">Verifikasi</button>
    </form>
    @else
    <form method="POST" action="{{ route('password.forgot.baru.post') }}">@csrf
        <label class="form-label">Password Baru</label>
        <input type="password" name="password" class="form-control mb-2" minlength="8" required autocomplete="new-password" autofocus>
        <label class="form-label">Konfirmasi Password Baru</label>
        <input type="password" name="password_confirmation" class="form-control mb-3" minlength="8" required autocomplete="new-password">
        <button class="btn btn-p w-100">Simpan Password</button>
    </form>
    @endif
    <div class="text-center mt-3"><a href="{{ route('login') }}" style="font-size:.8rem">&larr; Kembali ke login</a></div>
    <div class="text-muted mt-3" style="font-size:.7rem">Belum mengaktifkan 2FA atau HP hilang? Hubungi administrator untuk reset password.</div>
</div>
</body>
</html>
