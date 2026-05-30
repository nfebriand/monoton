<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','MonOTOn') — Monitoring Operasional Transmisi Online</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @php
        $settings    = \App\Models\AppSetting::allKeyed();
        $tema        = $settings['tema_warna'] ?? '#0a3d62';
        $logoPath    = !empty($settings['logo_path']) ? asset('storage/'.$settings['logo_path']) : null;
        $satuanKerja = $settings['satuan_kerja'] ?? 'MonOTOn';
        $kreditValid = \App\Models\AppSetting::kreditValid();
        // Kredit — tersimpan di script, tidak ditampilkan ke user
        $kPembuat  = 'Nanda Febriandy';
        $kWa       = '082182778608';
        $kTelegram = '@nfebriand';
    @endphp
    <style>
        :root{
            --primary:{{ $tema }};
            --primary-light:{{ $tema }}bb;
            --accent:#00d2d3;
            --success:#10ac84;--warning:#ff9f43;--danger:#ee5a24;
            --sidebar-width:252px;--topbar-height:56px;--footer-height:40px;
            --bg:#f0f4f8;--border:#dfe6e9;--text:#2d3436;--muted:#636e72;
        }
        *{box-sizing:border-box;}
        html,body{height:100%;}
        body{font-family:'Space Grotesk',sans-serif;background:var(--bg);color:var(--text);margin:0;overflow-x:hidden;}

        @if(!$kreditValid)
        #main-content::after{
            content:'⚠ Lisensi tidak valid. Kredit aplikasi telah diubah.';
            position:fixed;inset:0;background:rgba(10,10,10,.95);color:#fff;
            display:flex;align-items:center;justify-content:center;
            font-size:1.1rem;font-weight:700;z-index:99999;
            text-align:center;padding:2rem;pointer-events:none;
        }
        #main-content > *:not(footer){filter:blur(6px);pointer-events:none;}
        @endif

        /* ── SIDEBAR ── */
        #sidebar{position:fixed;top:0;left:0;width:var(--sidebar-width);height:100vh;
            background:var(--primary);display:flex;flex-direction:column;
            z-index:1050;transition:transform .28s ease;overflow-y:auto;}
        #sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1040;}
        .brand-wrap{padding:.82rem 1.1rem;border-bottom:1px solid rgba(255,255,255,.1);
            display:flex;align-items:center;gap:.6rem;flex-shrink:0;}
        .brand-icon{width:36px;height:36px;min-width:36px;border-radius:8px;
            background:var(--accent);display:flex;align-items:center;justify-content:center;
            font-size:1.05rem;color:var(--primary);overflow:hidden;}
        .brand-icon img{width:100%;height:100%;object-fit:cover;}
        .brand-name{font-size:.88rem;font-weight:700;color:#fff;line-height:1.2;}
        .brand-sub{font-size:.54rem;color:var(--accent);letter-spacing:.4px;text-transform:uppercase;line-height:1.3;}
        .nav-section{padding:.28rem 1.1rem .1rem;font-size:.58rem;font-weight:600;
            color:rgba(255,255,255,.32);letter-spacing:1.5px;text-transform:uppercase;margin-top:.2rem;}
        .sidebar-nav .nav-link{display:flex;align-items:center;gap:.55rem;padding:.48rem 1.1rem;
            color:rgba(255,255,255,.75);text-decoration:none;font-size:.8rem;font-weight:500;
            border-left:3px solid transparent;transition:all .18s;}
        .sidebar-nav .nav-link:hover,.sidebar-nav .nav-link.active{
            color:#fff;background:rgba(255,255,255,.09);border-left-color:var(--accent);}
        .sidebar-nav .nav-link i{font-size:.9rem;width:16px;}
        .sidebar-footer{padding:.7rem 1.1rem;border-top:1px solid rgba(255,255,255,.1);flex-shrink:0;}
        .user-ava{width:30px;height:30px;min-width:30px;border-radius:50%;background:var(--accent);
            display:flex;align-items:center;justify-content:center;font-weight:700;
            color:var(--primary);font-size:.7rem;}

        /* ── MAIN ── */
        #main{margin-left:var(--sidebar-width);min-height:100vh;display:flex;flex-direction:column;}

        /* ── TOPBAR ── */
        #topbar{height:var(--topbar-height);background:#fff;border-bottom:1px solid var(--border);
            display:flex;align-items:center;padding:0 1rem;gap:.7rem;
            position:sticky;top:0;z-index:100;flex-shrink:0;}
        #btnToggle{display:none;background:none;border:none;font-size:1.3rem;
            color:var(--text);padding:.2rem .4rem;cursor:pointer;}
        .topbar-title{font-size:.86rem;font-weight:600;flex:1;
            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .shift-badge{background:#e8f5e9;color:var(--success);border:1px solid #c8e6c9;
            border-radius:20px;padding:.2rem .65rem;font-size:.68rem;font-weight:600;white-space:nowrap;}
        .alarm-badge{display:flex;align-items:center;gap:.3rem;background:#fff5e6;
            color:var(--warning);border:1px solid #ffe0b2;border-radius:20px;
            padding:.2rem .65rem;font-size:.68rem;font-weight:600;
            cursor:pointer;white-space:nowrap;animation:pw 2s infinite;}
        @keyframes pw{0%,100%{box-shadow:0 0 0 0 rgba(255,159,67,.3)}50%{box-shadow:0 0 0 5px rgba(255,159,67,0)}}

        /* ── CONTENT ── */
        #main-content{flex:1;display:flex;flex-direction:column;}
        .content{flex:1;padding:1rem 1.1rem;padding-bottom:calc(var(--footer-height) + .5rem);}

        /* ── FOOTER FIXED ── */
        #app-footer{
            position:fixed;bottom:0;left:var(--sidebar-width);right:0;
            height:var(--footer-height);background:#fff;
            border-top:1px solid var(--border);
            display:flex;align-items:center;
            padding:0 1.1rem;z-index:99;
            font-size:.67rem;color:var(--muted);
        }

        /* ── CARDS ── */
        .card{border:1px solid var(--border);border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.05);}
        .card-header{background:transparent;border-bottom:1px solid var(--border);
            padding:.72rem 1rem;font-weight:600;font-size:.84rem;}
        .card-body{padding:.95rem;}

        /* ── TABLE ── */
        .table th{font-size:.71rem;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);}
        .table td{vertical-align:middle;font-size:.82rem;}

        /* ── PAGINATION FIX ── */
        .pagination{margin-bottom:0;}
        .pagination .page-link{
            font-size:.78rem;padding:.28rem .55rem;
            color:var(--primary);border-color:var(--border);
            line-height:1.4;
        }
        .pagination .page-item.active .page-link{
            background:var(--primary);border-color:var(--primary);color:#fff;
        }
        .pagination .page-item.disabled .page-link{color:#aaa;}
        /* Sembunyikan icon Bootstrap yang terlalu besar, ganti teks */
        .pagination .page-link[aria-label="« Previous"]::before{content:'‹';}
        .pagination .page-link[aria-label="Next »"]::before{content:'›';}
        .pagination .page-link[aria-label="« Previous"],
        .pagination .page-link[aria-label="Next »"]{font-size:.85rem;}

        /* ── FORMS ── */
        .form-label{font-size:.75rem;font-weight:600;margin-bottom:.22rem;}
        .form-control,.form-select{border-radius:7px;font-size:.82rem;border-color:var(--border);}
        .form-control:focus,.form-select:focus{
            border-color:var(--primary);box-shadow:0 0 0 3px rgba(10,61,98,.1);}
        .input-group-text{font-size:.78rem;}

        /* ── BUTTONS ── */
        .btn-primary-custom{background:var(--primary);color:#fff;border:none;
            border-radius:7px;padding:.42rem 1.05rem;font-weight:600;font-size:.82rem;}
        .btn-primary-custom:hover{opacity:.88;color:#fff;}

        /* ── MISC ── */
        .mono{font-family:'JetBrains Mono',monospace;}
        .vswr-baik{color:var(--success);font-weight:600;}
        .vswr-sedang{color:var(--warning);font-weight:600;}
        .vswr-buruk{color:var(--danger);font-weight:600;}
        .section-title{font-weight:700;color:var(--primary);
            border-left:4px solid var(--primary);padding-left:.65rem;font-size:.84rem;}
        #alert-pencatatan{position:fixed;top:65px;right:1rem;z-index:9999;max-width:320px;width:90%;}

        /* ── LIGHTBOX ── */
        .foto-lightbox-trigger{cursor:zoom-in;}
        .foto-lightbox-trigger:hover{opacity:.92;}

        /* ── RESPONSIVE ── */
        @media(max-width:991.98px){
            #sidebar{transform:translateX(-100%);}
            #sidebar.open{transform:none;}
            #sidebar-overlay.open{display:block;}
            #main{margin-left:0;}
            #app-footer{left:0;}
            #btnToggle{display:block;}
            .content{padding:.75rem .85rem;padding-bottom:calc(var(--footer-height) + .5rem);}
        }
        @media(max-width:575.98px){
            .content{padding:.6rem .7rem;padding-bottom:calc(var(--footer-height) + .5rem);}
            .card-header{padding:.6rem .85rem;}
            .card-body{padding:.8rem;}
        }
    </style>
    @stack('styles')
</head>
<body>
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

{{-- SIDEBAR --}}
<nav id="sidebar">
    <div class="brand-wrap">
        <div class="brand-icon">
            @if($logoPath)
                <img src="{{ $logoPath }}" alt="Logo">
            @else
                <i class="bi bi-broadcast"></i>
            @endif
        </div>
        <div style="min-width:0">
            <div class="brand-name">MonOTOn</div>
            <div class="brand-sub">{{ Str::limit($satuanKerja,24) }}</div>
        </div>
    </div>
    <div class="sidebar-nav flex-fill py-1">
        <div class="nav-section">Utama</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard')?'active':'' }}" onclick="closeSidebar()">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('pemancar.index') }}" class="nav-link {{ request()->routeIs('pemancar.*')?'active':'' }}" onclick="closeSidebar()">
            <i class="bi bi-broadcast-pin"></i> Data Pemancar
        </a>
        <div class="nav-section">Operasional</div>
        <a href="{{ route('operasional.create') }}" class="nav-link {{ request()->routeIs('operasional.create')?'active':'' }}" onclick="closeSidebar()">
            <i class="bi bi-pencil-square"></i> Catat Log
        </a>
        <a href="{{ route('operasional.index') }}" class="nav-link {{ request()->routeIs('operasional.index')?'active':'' }}" onclick="closeSidebar()">
            <i class="bi bi-journal-text"></i> Riwayat Log
        </a>
        <a href="{{ route('eviden.index') }}" class="nav-link {{ request()->routeIs('eviden.*')?'active':'' }}" onclick="closeSidebar()">
            <i class="bi bi-camera"></i> Catatan Eviden
        </a>
        <a href="{{ route('laporan.suhu') }}" class="nav-link {{ request()->routeIs('laporan.suhu*')?'active':'' }}" onclick="closeSidebar()">
            <i class="bi bi-thermometer-half"></i> Grafik Suhu
        </a>
        <div class="nav-section">Laporan</div>
        <a href="{{ route('laporan.index') }}" class="nav-link {{ request()->routeIs('laporan.index')?'active':'' }}" onclick="closeSidebar()">
            <i class="bi bi-file-earmark-pdf"></i> Cetak Laporan
        </a>
        @if(auth()->user()->isAdmin())
        <div class="nav-section">Admin</div>
        <a href="{{ route('jadwal.index') }}" class="nav-link {{ request()->routeIs('jadwal.*')?'active':'' }}" onclick="closeSidebar()">
            <i class="bi bi-calendar3"></i> Jadwal Shift
        </a>
        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*')?'active':'' }}" onclick="closeSidebar()">
            <i class="bi bi-people"></i> Manajemen User
        </a>
        <a href="{{ route('setting.index') }}" class="nav-link {{ request()->routeIs('setting.*')?'active':'' }}" onclick="closeSidebar()">
            <i class="bi bi-gear"></i> Pengaturan App
        </a>
        @endif
    </div>
    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2">
            <div class="user-ava">{{ strtoupper(substr(auth()->user()->name,0,2)) }}</div>
            <div style="min-width:0;flex:1">
                <div style="font-size:.75rem;font-weight:600;color:#fff;
                     white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ auth()->user()->name }}</div>
                <div style="font-size:.58rem;color:var(--accent)">
                    {{ auth()->user()->isAdmin()?'Administrator':'Operator' }}
                    @if(auth()->user()->lokasi_dinas) — {{ auth()->user()->lokasi_dinas }}@endif
                </div>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-sm w-100"
                    style="background:rgba(255,255,255,.1);color:rgba(255,255,255,.7);font-size:.7rem;">
                <i class="bi bi-box-arrow-right me-1"></i>Keluar
            </button>
        </form>
    </div>
</nav>

{{-- MAIN --}}
<div id="main">
    <div id="topbar">
        <button id="btnToggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <div class="topbar-title">@yield('page-title','Dashboard')</div>
        @if(session('shift_aktif_no'))
        <div class="shift-badge"><i class="bi bi-clock me-1"></i>Shift {{ session('shift_aktif_no') }}</div>
        @endif
        @if(isset($peringatanPencatatan) && $peringatanPencatatan)
        <div class="alarm-badge" onclick="document.getElementById('alert-pencatatan').style.display='block'">
            <i class="bi bi-bell-fill"></i><span class="d-none d-sm-inline ms-1">Catat!</span>
        </div>
        @endif
        <span class="mono text-muted d-none d-lg-block" style="font-size:.7rem;white-space:nowrap">{{ now()->format('d/m/Y H:i') }}</span>
    </div>

    @if(isset($peringatanPencatatan) && $peringatanPencatatan)
    <div id="alert-pencatatan" class="alert alert-warning alert-dismissible shadow" style="display:none">
        <i class="bi bi-alarm me-2"></i><strong>Pengingat!</strong> Sudah 3 jam sejak pencatatan terakhir.
        <a href="{{ route('operasional.create') }}" class="btn btn-sm btn-warning d-block mt-2 w-100">
            <i class="bi bi-pencil-square me-1"></i>Catat Sekarang
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div style="padding:.5rem 1.1rem 0">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 py-2 mb-2">
            <i class="bi bi-check-circle-fill flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible py-2 mb-2">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    </div>

    <div id="main-content">
        <div class="content">@yield('content')</div>
    </div>

    {{-- FOOTER FIXED --}}
    <footer id="app-footer">
        <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-1">
            <div>
                <strong style="color:var(--primary)">MonOTOn</strong>
                &nbsp;v{{ $settings['app_version'] ?? '1.0.0' }}
                &nbsp;|&nbsp; {{ $settings['satuan_kerja'] ?? '' }}
            </div>
            {{-- Kredit tersimpan di script, tidak tampil ke user --}}
            <div class="text-muted d-none d-md-block">
                Monitoring Operasional Transmisi Online
            </div>
        </div>
        {{-- kredit:pembuat={{ $kPembuat }},wa={{ $kWa }},tg={{ $kTelegram }} --}}
    </footer>
</div>

{{-- LIGHTBOX GLOBAL --}}
<div class="modal fade" id="globalLightbox" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-body p-1 text-center position-relative">
                <img id="lightboxSrc" src="" style="max-height:88vh;max-width:100%;border-radius:8px;display:block;margin:auto">
                <div id="lightboxCaption" style="color:#ccc;font-size:.82rem;margin-top:.5rem;text-align:center"></div>
            </div>
            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2"
                    data-bs-dismiss="modal"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebar-overlay').classList.toggle('open');
}
function closeSidebar(){
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebar-overlay').classList.remove('open');
}
// Global lightbox
function bukaLightbox(src,caption=''){
    document.getElementById('lightboxSrc').src=src;
    document.getElementById('lightboxCaption').textContent=caption;
    new bootstrap.Modal(document.getElementById('globalLightbox')).show();
}
// Auto-attach lightbox ke semua .foto-lightbox-trigger
document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('.foto-lightbox-trigger').forEach(el=>{
        el.addEventListener('click',function(){
            bukaLightbox(this.dataset.src||this.src, this.dataset.caption||'');
        });
    });
});
@if(isset($peringatanPencatatan) && $peringatanPencatatan)
setTimeout(()=>{const el=document.getElementById('alert-pencatatan');if(el)el.style.display='block';},1500);
@endif
</script>
@stack('scripts')
</body>
</html>
