<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Akses Ditolak</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: 'Space Grotesk', sans-serif;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: #f0f4f8; margin: 0;
        }
        .box {
            background: #fff; border-radius: 16px;
            padding: 3rem 2.5rem; text-align: center;
            max-width: 440px; width: 100%;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
        }
        .icon-wrap {
            width: 80px; height: 80px; border-radius: 50%;
            background: #fff5f5; margin: 0 auto 1.5rem;
            display: flex; align-items: center; justify-content: center;
        }
        h1 { font-size: 1.4rem; font-weight: 700; color: #2d3436; margin-bottom: .5rem; }
        p  { color: #636e72; font-size: .9rem; line-height: 1.6; }
        .btn-back {
            display: inline-block; margin-top: 1.5rem;
            background: #0a3d62; color: #fff;
            padding: .6rem 1.5rem; border-radius: 8px;
            text-decoration: none; font-weight: 600;
            font-size: .875rem;
        }
        .btn-back:hover { background: #1e5f8a; color: #fff; }
        .code { font-size: 3rem; font-weight: 700; color: #ee5a24; line-height: 1; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="box">
    <div class="icon-wrap">
        <i class="bi bi-shield-lock-fill" style="font-size:2rem;color:#ee5a24"></i>
    </div>
    <div class="code">403</div>
    <h1>Akses Ditolak</h1>
    <p>
        Anda tidak memiliki izin untuk mengakses halaman ini.<br>
        Halaman ini hanya dapat diakses oleh <strong>Mas Admin Ganteng dan yang punya akses saja</strong>.
    </p>
    @if(isset($exception) && $exception->getMessage())
    <p style="font-size:.8rem;color:#b2bec3;margin-top:.5rem">{{ $exception->getMessage() }}</p>
    @endif
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="btn-back">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>
</body>
</html>
