<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MonOTOn') — Monitoring Operasional Transmisi Online</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --primary: #0a3d62;
            --primary-light: #1e5f8a;
            --accent: #00d2d3;
            --accent2: #ff9f43;
            --success: #10ac84;
            --warning: #ff9f43;
            --danger: #ee5a24;
            --sidebar-width: 260px;
            --topbar-height: 60px;
            --bg: #f0f4f8;
            --card-bg: #ffffff;
            --text: #2d3436;
            --text-muted: #636e72;
            --border: #dfe6e9;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Space Grotesk', sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
        }

        /* ── Sidebar ── */
        #sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--primary);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: transform .3s ease;
        }
        .sidebar-brand {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,.1);
            display: flex; align-items: center; gap: .75rem;
        }
        .sidebar-brand .brand-icon {
            width: 36px; height: 36px;
            background: var(--accent);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: var(--primary);
        }
        .sidebar-brand .brand-text {
            font-size: 1.1rem; font-weight: 700;
            color: #fff; letter-spacing: .5px;
        }
        .sidebar-brand .brand-sub {
            font-size: .65rem; color: var(--accent);
            font-weight: 400; display: block;
            letter-spacing: 1px; text-transform: uppercase;
        }

        .sidebar-nav { flex: 1; overflow-y: auto; padding: 1rem 0; }
        .nav-section {
            padding: .4rem 1.5rem .2rem;
            font-size: .65rem; font-weight: 600;
            color: rgba(255,255,255,.35);
            letter-spacing: 1.5px; text-transform: uppercase;
        }
        .sidebar-nav .nav-link {
            display: flex; align-items: center; gap: .75rem;
            padding: .6rem 1.5rem;
            color: rgba(255,255,255,.75);
            text-decoration: none;
            font-size: .875rem; font-weight: 500;
            border-left: 3px solid transparent;
            transition: all .2s;
        }
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,.08);
            border-left-color: var(--accent);
        }
        .sidebar-nav .nav-link i { font-size: 1.1rem; width: 20px; }

        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,.1);
        }
        .sidebar-user {
            display: flex; align-items: center; gap: .75rem;
        }
        .user-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; color: var(--primary); font-size: .875rem;
        }
        .user-info .user-name { font-size: .8rem; font-weight: 600; color: #fff; }
        .user-info .user-role { font-size: .65rem; color: var(--accent); }

        /* ── Main ── */
        #main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }

        /* ── Topbar ── */
        #topbar {
            height: var(--topbar-height);
            background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            padding: 0 1.5rem;
            gap: 1rem;
            position: sticky; top: 0; z-index: 100;
        }
        .topbar-title {
            font-size: 1rem; font-weight: 600;
            flex: 1; color: var(--text);
        }

        /* Alarm badge */
        .alarm-badge {
            display: flex; align-items: center; gap: .4rem;
            background: #fff5e6; color: var(--warning);
            border: 1px solid #ffe0b2;
            border-radius: 20px;
            padding: .3rem .8rem;
            font-size: .78rem; font-weight: 600;
            cursor: pointer;
            animation: pulse-warn 2s infinite;
        }
        @keyframes pulse-warn {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255,159,67,.3); }
            50%       { box-shadow: 0 0 0 6px rgba(255,159,67,0); }
        }

        /* Shift badge */
        .shift-badge {
            background: #e8f5e9; color: var(--success);
            border: 1px solid #c8e6c9;
            border-radius: 20px;
            padding: .3rem .8rem;
            font-size: .75rem; font-weight: 600;
        }

        /* ── Content ── */
        .content { flex: 1; padding: 1.5rem; }

        /* ── Cards ── */
        .card {
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }

        /* Stat cards */
        .stat-card {
            border-radius: 12px;
            padding: 1.25rem;
            display: flex; gap: 1rem;
            align-items: center;
        }
        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; flex-shrink: 0;
        }
        .stat-value { font-size: 1.75rem; font-weight: 700; line-height: 1; }
        .stat-label { font-size: .78rem; color: var(--text-muted); margin-top: .2rem; }

        /* VSWR badge */
        .vswr-baik   { color: var(--success); font-weight: 600; }
        .vswr-sedang { color: var(--warning); font-weight: 600; }
        .vswr-buruk  { color: var(--danger);  font-weight: 600; }

        /* Mono for numbers */
        .mono { font-family: 'JetBrains Mono', monospace; }

        /* Table */
        .table th { font-size: .78rem; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); }
        .table td { vertical-align: middle; font-size: .875rem; }

        /* Forms */
        .form-label { font-size: .8rem; font-weight: 600; color: var(--text); }
        .form-control, .form-select {
            border-radius: 8px; font-size: .875rem;
            border-color: var(--border);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(10,61,98,.1);
        }

        /* Buttons */
        .btn-primary-custom {
            background: var(--primary); color: #fff;
            border: none; border-radius: 8px;
            padding: .5rem 1.2rem; font-weight: 600;
            font-size: .875rem;
        }
        .btn-primary-custom:hover { background: var(--primary-light); color: #fff; }

        /* Alert pencatatan */
        #alert-pencatatan {
            position: fixed; top: 80px; right: 1.5rem;
            z-index: 9999;
            max-width: 360px;
        }

        /* Map */
        #map { height: 350px; border-radius: 8px; overflow: hidden; }

        /* Foto gallery */
        .foto-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .75rem; }
        .foto-item { aspect-ratio: 1; border-radius: 8px; overflow: hidden; position: relative; }
        .foto-item img { width: 100%; height: 100%; object-fit: cover; }
        .foto-overlay {
            position: absolute; inset: 0;
            background: rgba(0,0,0,.4);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity .2s;
        }
        .foto-item:hover .foto-overlay { opacity: 1; }

        /* Responsive */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: none; }
            #main { margin-left: 0; }
        }
    </style>

    @stack('styles')
</head>
<body>

<!-- Sidebar -->
<nav id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-broadcast"></i></div>
        <div>
            <div class="brand-text">MonOTOn</div>
            <span class="brand-sub">Monitoring Operasional Transmisi Online</span>
        </div>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section">Utama</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('pemancar.index') }}" class="nav-link {{ request()->routeIs('pemancar.*') ? 'active' : '' }}">
            <i class="bi bi-broadcast-pin"></i> Data Pemancar
        </a>

        <div class="nav-section" style="margin-top:.5rem">Operasional</div>
        <a href="{{ route('operasional.create') }}" class="nav-link {{ request()->routeIs('operasional.create') ? 'active' : '' }}">
            <i class="bi bi-pencil-square"></i> Catat Log
        </a>
        <a href="{{ route('operasional.index') }}" class="nav-link {{ request()->routeIs('operasional.index') ? 'active' : '' }}">
            <i class="bi bi-journal-text"></i> Riwayat Log
        </a>
        <a href="{{ route('laporan.suhu') }}" class="nav-link {{ request()->routeIs('laporan.suhu*') ? 'active' : '' }}">
            <i class="bi bi-thermometer-half"></i> Grafik Suhu
        </a>

        <div class="nav-section" style="margin-top:.5rem">Laporan</div>
        <a href="{{ route('laporan.index') }}" class="nav-link {{ request()->routeIs('laporan.index') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-pdf"></i> Cetak Laporan
        </a>

        @if(auth()->user()->isAdmin())
        <div class="nav-section" style="margin-top:.5rem">Admin</div>
        <a href="{{ route('jadwal.index') }}" class="nav-link {{ request()->routeIs('jadwal.*') ? 'active' : '' }}">
            <i class="bi bi-calendar3"></i> Jadwal Shift
        </a>
        @endif
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
            <div class="user-info">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Operator' }}</div>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-sm w-100" style="background:rgba(255,255,255,.1);color:rgba(255,255,255,.7);font-size:.75rem;">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </button>
        </form>
    </div>
</nav>

<!-- Main Content -->
<div id="main">
    <!-- Topbar -->
    <div id="topbar">
        <button class="btn btn-sm d-md-none me-1" onclick="toggleSidebar()">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="topbar-title">@yield('page-title', 'Dashboard')</div>

        @if(session('shift_aktif_no'))
        <div class="shift-badge">
            <i class="bi bi-clock"></i>
            Shift {{ session('shift_aktif_no') }}
        </div>
        @endif

        @if(isset($peringatanPencatatan) && $peringatanPencatatan)
        <div class="alarm-badge" onclick="document.getElementById('alert-pencatatan').style.display='block'">
            <i class="bi bi-bell-fill"></i> Saatnya Mencatat!
        </div>
        @endif

        <div class="text-muted small mono">{{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <!-- Alert Pencatatan -->
    @if(isset($peringatanPencatatan) && $peringatanPencatatan)
    <div id="alert-pencatatan" class="alert alert-warning alert-dismissible shadow-sm" role="alert" style="display:none">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-alarm fs-4"></i>
            <div>
                <strong>Pengingat Pencatatan!</strong><br>
                <small>Sudah lebih dari 3 jam sejak pencatatan terakhir. Harap lakukan pencatatan operasional segera.</small>
            </div>
        </div>
        <a href="{{ route('operasional.create') }}" class="btn btn-sm btn-warning mt-2 w-100">
            <i class="bi bi-pencil-square"></i> Catat Sekarang
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Flash Messages -->
    <div style="padding:.75rem 1.5rem 0;">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 py-2" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible py-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    </div>

    <!-- Page Content -->
    <div class="content">
        @yield('content')
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

// Auto-show alarm jika ada peringatan
@if(isset($peringatanPencatatan) && $peringatanPencatatan)
setTimeout(() => {
    const el = document.getElementById('alert-pencatatan');
    if (el) el.style.display = 'block';
}, 1500);
@endif
</script>

@stack('scripts')
</body>
</html>
