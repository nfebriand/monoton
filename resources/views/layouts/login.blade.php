<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — RadioOps</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: 'Space Grotesk', sans-serif;
            min-height: 100vh;
            background: #0a3d62;
            display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        /* Animated background waves */
        body::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse at 20% 50%, rgba(0,210,211,.15) 0%, transparent 50%),
                        radial-gradient(ellipse at 80% 20%, rgba(30,95,138,.3) 0%, transparent 50%);
        }
        .wave {
            position: absolute;
            bottom: -50px; left: 50%;
            transform: translateX(-50%);
            width: 200%; height: 300px;
            background: rgba(255,255,255,.03);
            border-radius: 50%;
            animation: wave 8s ease-in-out infinite;
        }
        .wave:nth-child(2) { animation-delay: -4s; background: rgba(0,210,211,.04); }
        @keyframes wave { 0%,100%{transform:translateX(-50%) translateY(0)}50%{transform:translateX(-50%) translateY(-20px)} }

        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%; max-width: 420px;
            box-shadow: 0 25px 60px rgba(0,0,0,.4);
            position: relative; z-index: 10;
        }
        .login-logo {
            display: flex; align-items: center; gap: .75rem;
            margin-bottom: 2rem;
        }
        .logo-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, #0a3d62, #1e5f8a);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            color: #00d2d3; font-size: 1.6rem;
            box-shadow: 0 4px 15px rgba(10,61,98,.3);
        }
        .logo-text h1 { font-size: 1.5rem; font-weight: 700; color: #0a3d62; margin: 0; }
        .logo-text p  { font-size: .72rem; color: #636e72; margin: 0; letter-spacing: .5px; }

        .form-label { font-size: .8rem; font-weight: 600; color: #2d3436; }
        .form-control {
            border-radius: 10px; padding: .65rem 1rem;
            border-color: #dfe6e9; font-size: .9rem;
        }
        .form-control:focus { border-color: #1e5f8a; box-shadow: 0 0 0 3px rgba(10,61,98,.1); }

        .input-group-text {
            background: transparent; border-color: #dfe6e9;
            border-radius: 10px 0 0 10px;
        }
        .input-group .form-control { border-radius: 0 10px 10px 0; }

        .btn-login {
            background: linear-gradient(135deg, #0a3d62, #1e5f8a);
            color: #fff; border: none;
            border-radius: 10px; padding: .75rem;
            font-weight: 700; font-size: .95rem;
            width: 100%; letter-spacing: .5px;
            transition: all .3s;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(10,61,98,.4);
            color: #fff;
        }

        .shift-info {
            background: #e8f4fd; border-radius: 10px;
            padding: .75rem 1rem; margin-bottom: 1.5rem;
            font-size: .78rem; color: #0a3d62;
        }
        .shift-item {
            display: flex; justify-content: space-between;
            align-items: center; padding: .15rem 0;
        }
        .shift-now {
            background: #0a3d62; color: #00d2d3;
            padding: .1rem .4rem; border-radius: 4px;
            font-size: .65rem; font-weight: 700;
        }

        .alert-danger { border-radius: 10px; font-size: .85rem; }
    </style>
</head>
<body>
<div class="wave"></div>
<div class="wave"></div>

<div class="login-card">
    <div class="login-logo">
        <div class="logo-icon"><i class="bi bi-broadcast"></i></div>
        <div class="logo-text">
            <h1>RadioOps</h1>
            <p>SISTEM MANAJEMEN PEMANCAR</p>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger py-2">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    <!-- Info Shift Aktif -->
    <div class="shift-info">
        <div class="mb-1" style="font-weight:600;font-size:.75rem;">🕐 Jadwal Shift</div>
        @php
            $nowTime = now()->format('H:i');
            $shifts = [
                1 => ['label'=>'Shift 1','mulai'=>'00:15','selesai'=>'07:45'],
                2 => ['label'=>'Shift 2','mulai'=>'07:45','selesai'=>'15:45'],
                3 => ['label'=>'Shift 3','mulai'=>'15:45','selesai'=>'23:45'],
            ];
        @endphp
        @foreach($shifts as $no => $sh)
        <div class="shift-item">
            <span>{{ $sh['label'] }}: {{ $sh['mulai'] }} – {{ $sh['selesai'] }}</span>
            @if($nowTime >= $sh['mulai'] && $nowTime < $sh['selesai'])
            <span class="shift-now">AKTIF</span>
            @endif
        </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('login.post') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope text-muted"></i></span>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                       required autofocus>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock text-muted"></i></span>
                <input type="password" name="password" class="form-control"  required>
            </div>
        </div>
        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Sistem
        </button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
