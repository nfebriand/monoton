<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','MonOTOn') — Monitoring Operasional Terintegrasi Online</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @if(file_exists(public_path('js/foto-compress.js')))
    <script src="/js/foto-compress.js"></script>
    @endif
    @if(file_exists(public_path('js/offline-db.js')))
    <script src="/js/offline-db.js"></script>
    <script src="/js/offline-sync.js"></script>
    @endif
    @if(file_exists(public_path('js/precache-pages.js')))
    <script src="/js/precache-pages.js"></script>
    @endif
    @php
        $settings    = \App\Models\AppSetting::allKeyed();
        $tema        = $settings['tema_warna'] ?? '#0a3d62';
        $logoPath    = !empty($settings['logo_path']) ? asset('uploads/'.$settings['logo_path']) : null;
        $satuanKerja = $settings['satuan_kerja'] ?? 'MonOTOn';
        $kreditValid = \App\Models\AppSetting::kreditValid();
        $kPembuat    = 'Nanda Febriandy';
        $kWa         = '082182778608';
        $kTelegram   = '@nfebriand';
        $kreditTampilValid =
            ($settings['kredit_pembuat'] ?? '') === $kPembuat &&
            ($settings['kredit_wa']       ?? '') === $kWa &&
            ($settings['kredit_telegram'] ?? '') === $kTelegram;
        $updateLog   = $settings['update_log']   ?? '';
        $appVersion  = $settings['app_version']  ?? '1.0.0';
        $logEntries  = array_filter(array_map('trim', preg_split('/\n\n+/', $updateLog)));
    @endphp
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="{{ $tema }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MonOTOn">
    <link rel="apple-touch-icon" href="/pwa/icon-192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/pwa/icon-96.png">

    {{-- Set dark/light mode SEBELUM render untuk hindari flicker --}}
    <script>
        (function(){
            const saved = localStorage.getItem('monoton-theme');
            const theme = saved || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <style>
        :root{
            --primary:{{ $tema }};--primary-light:{{ $tema }}bb;--accent:#00d2d3;
            --success:#10ac84;--warning:#ff9f43;--danger:#ee5a24;
            --sidebar-width:252px;--topbar-height:56px;--footer-height:42px;

            /* LIGHT MODE (default) */
            --bg:#f0f4f8;--border:#dfe6e9;--text:#2d3436;--muted:#636e72;
            --card-bg:#ffffff;--input-bg:#fafafa;--topbar-bg:#ffffff;
            --footer-bg:#ffffff;--table-stripe:#f7fafc;
        }
        [data-theme="dark"]{
            --bg:#0f1620;--border:#26323f;--text:#e6edf3;--muted:#9aa7b3;
            --card-bg:#171f2b;--input-bg:#1c2533;--topbar-bg:#171f2b;
            --footer-bg:#171f2b;--table-stripe:#1b2430;
            --primary-light:{{ $tema }}55;
        }
        *{box-sizing:border-box;}html,body{height:100%;}
        body{font-family:'Space Grotesk',sans-serif;background:var(--bg);color:var(--text);margin:0;overflow-x:hidden;transition:background .25s,color .25s;}

        @if(!$kreditValid || !$kreditTampilValid)
        body::after{
            content:'⚠ Lisensi aplikasi tidak valid. Hubungi pembuat aplikasi.';
            position:fixed;inset:0;background:rgba(5,5,5,.97);color:#fff;
            display:flex;align-items:center;justify-content:center;
            font-size:1.1rem;font-weight:700;z-index:999999;
            text-align:center;padding:2rem;letter-spacing:.5px;
        }
        body > *{filter:blur(8px);pointer-events:none;user-select:none;}
        @endif

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
        .brand-sub{font-size:.54rem;color:var(--accent);letter-spacing:.4px;text-transform:uppercase;}
        .nav-section{padding:.28rem 1.1rem .1rem;font-size:.58rem;font-weight:600;
            color:rgba(255,255,255,.32);letter-spacing:1.5px;text-transform:uppercase;margin-top:.2rem;}
        .sidebar-nav .nav-link{display:flex;align-items:center;gap:.55rem;padding:.48rem 1.1rem;
            color:rgba(255,255,255,.75);text-decoration:none;font-size:.8rem;font-weight:500;
            border-left:3px solid transparent;transition:all .18s;}
        .sidebar-nav .nav-link:hover,.sidebar-nav .nav-link.active{
            color:#fff;background:rgba(255,255,255,.09);border-left-color:var(--accent);}
        .sidebar-nav .nav-link i{font-size:.9rem;width:16px;}
        .sidebar-footer{padding:.6rem 1.1rem .8rem;border-top:1px solid rgba(255,255,255,.1);flex-shrink:0;}
        .sidebar-footer .app-ver{font-size:.6rem;color:rgba(255,255,255,.35);text-align:center;}

        #main{margin-left:var(--sidebar-width);min-height:100vh;display:flex;flex-direction:column;}
        #topbar{height:var(--topbar-height);background:var(--topbar-bg);border-bottom:1px solid var(--border);
            display:flex;align-items:center;padding:0 1rem;gap:.7rem;position:sticky;top:0;z-index:100;flex-shrink:0;}
        #btnToggle{display:none;background:none;border:none;font-size:1.3rem;color:var(--text);padding:.2rem .4rem;cursor:pointer;}
        .topbar-title{font-size:.86rem;font-weight:600;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .shift-badge{background:#e8f5e9;color:var(--success);border:1px solid #c8e6c9;border-radius:20px;padding:.2rem .65rem;font-size:.68rem;font-weight:600;white-space:nowrap;}
        [data-theme="dark"] .shift-badge{background:rgba(16,172,132,.15);border-color:rgba(16,172,132,.3);}
        .alarm-badge{display:flex;align-items:center;gap:.3rem;background:#fff5e6;color:var(--warning);border:1px solid #ffe0b2;border-radius:20px;padding:.2rem .65rem;font-size:.68rem;font-weight:600;cursor:pointer;white-space:nowrap;animation:pw 2s infinite;}
        [data-theme="dark"] .alarm-badge{background:rgba(255,159,67,.12);border-color:rgba(255,159,67,.3);}
        @keyframes pw{0%,100%{box-shadow:0 0 0 0 rgba(255,159,67,.3)}50%{box-shadow:0 0 0 5px rgba(255,159,67,0)}}

        #offline-bar{display:none;background:#ee5a24;color:#fff;text-align:center;padding:.28rem;font-size:.74rem;font-weight:600;}
        #offline-bar.show{display:block;}

        #main-content{flex:1;display:flex;flex-direction:column;}
        .content{flex:1;padding:1rem 1.1rem;padding-bottom:calc(var(--footer-height) + .5rem);}

        /* ── USER DROPDOWN (POJOK KANAN ATAS) ── */
        .user-menu{position:relative;flex-shrink:0;}
        .user-menu-btn{
            display:flex;align-items:center;gap:.5rem;
            background:none;border:1px solid var(--border);border-radius:24px;
            padding:.25rem .7rem .25rem .35rem;cursor:pointer;
            color:var(--text);transition:background .15s;
        }
        .user-menu-btn:hover{background:var(--bg);}
        .user-ava{width:28px;height:28px;min-width:28px;border-radius:50%;background:var(--accent);
            display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);font-size:.68rem;}
        .user-menu-name{font-size:.76rem;font-weight:600;line-height:1.1;text-align:left;}
        .user-menu-role{font-size:.6rem;color:var(--muted);line-height:1.1;}
        .user-menu-caret{font-size:.7rem;color:var(--muted);transition:transform .2s;}
        .user-menu.open .user-menu-caret{transform:rotate(180deg);}

        .user-menu-dropdown{
            display:none;position:absolute;top:calc(100% + 8px);right:0;
            width:250px;background:var(--card-bg);border:1px solid var(--border);
            border-radius:12px;box-shadow:0 8px 28px rgba(0,0,0,.15);
            z-index:500;overflow:hidden;
        }
        .user-menu.open .user-menu-dropdown{display:block;}
        .umd-header{padding:.85rem 1rem;border-bottom:1px solid var(--border);
            display:flex;align-items:center;gap:.6rem;background:var(--primary);color:#fff;}
        .umd-header .user-ava{background:var(--accent);color:var(--primary);width:38px;height:38px;font-size:.85rem;}
        .umd-header-name{font-size:.85rem;font-weight:700;}
        .umd-header-sub{font-size:.65rem;opacity:.8;}
        .umd-body{padding:.4rem;}
        .umd-item{
            display:flex;align-items:center;gap:.6rem;width:100%;
            padding:.55rem .7rem;border-radius:8px;border:none;background:none;
            color:var(--text);text-decoration:none;font-size:.8rem;
            cursor:pointer;transition:background .15s;text-align:left;
        }
        .umd-item:hover{background:var(--bg);}
        .umd-item i{width:18px;font-size:.95rem;color:var(--muted);}
        .umd-divider{height:1px;background:var(--border);margin:.3rem 0;}
        .umd-item.danger{color:var(--danger);}
        .umd-item.danger i{color:var(--danger);}

        /* Theme toggle switch */
        .theme-toggle-row{
            display:flex;align-items:center;justify-content:space-between;
            padding:.55rem .7rem;font-size:.8rem;
        }
        .theme-switch{
            position:relative;width:42px;height:22px;
            background:var(--border);border-radius:20px;cursor:pointer;
            transition:background .25s;flex-shrink:0;
        }
        .theme-switch::after{
            content:'';position:absolute;top:2px;left:2px;
            width:18px;height:18px;border-radius:50%;background:#fff;
            transition:transform .25s;box-shadow:0 1px 3px rgba(0,0,0,.2);
        }
        [data-theme="dark"] .theme-switch{background:var(--primary);}
        [data-theme="dark"] .theme-switch::after{transform:translateX(20px);}

        /* ── CARDS, TABLE, FORM ── */
        .card{border:1px solid var(--border);border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.05);background:var(--card-bg);}
        .card-header{background:transparent;border-bottom:1px solid var(--border);padding:.72rem 1rem;font-weight:600;font-size:.84rem;color:var(--text);}
        .card-body{padding:.95rem;}
        .table{color:var(--text);}
        .table th{font-size:.71rem;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);}
        .table td{vertical-align:middle;font-size:.82rem;border-color:var(--border);}
        [data-theme="dark"] .table-hover>tbody>tr:hover>*{background:var(--table-stripe);color:var(--text);}
        [data-theme="dark"] .table>:not(caption)>*>*{background-color:transparent;}
        .pagination{margin-bottom:0;}
        .pagination .page-link{font-size:.78rem;padding:.28rem .55rem;color:var(--primary);border-color:var(--border);line-height:1.4;background:var(--card-bg);}
        .pagination .page-item.active .page-link{background:var(--primary);border-color:var(--primary);color:#fff;}
        .pagination .page-item.disabled .page-link{color:#aaa;background:var(--card-bg);}
        .form-label{font-size:.75rem;font-weight:600;margin-bottom:.22rem;color:var(--text);}
        .form-control,.form-select{border-radius:7px;font-size:.82rem;border-color:var(--border);background:var(--input-bg);color:var(--text);}
        .form-control:focus,.form-select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(10,61,98,.1);background:var(--input-bg);color:var(--text);}
        [data-theme="dark"] .form-control::placeholder{color:#5a6776;}
        .input-group-text{font-size:.78rem;background:var(--input-bg);color:var(--text);border-color:var(--border);}
        .btn-primary-custom{background:var(--primary);color:#fff;border:none;border-radius:7px;padding:.42rem 1.05rem;font-weight:600;font-size:.82rem;}
        .btn-primary-custom:hover{opacity:.88;color:#fff;}
        .mono{font-family:'JetBrains Mono',monospace;}
        .vswr-baik{color:var(--success);font-weight:600;}
        .vswr-sedang{color:var(--warning);font-weight:600;}
        .vswr-buruk{color:var(--danger);font-weight:600;}
        .section-title{font-weight:700;color:var(--primary);border-left:4px solid var(--primary);padding-left:.65rem;font-size:.84rem;}
        #alert-pencatatan{position:fixed;top:65px;right:1rem;z-index:9999;max-width:320px;width:90%;}

        /* ── FOOTER ── */
        #app-footer{
            position:fixed;bottom:0;left:var(--sidebar-width);right:0;
            height:var(--footer-height);background:var(--footer-bg);
            border-top:1px solid var(--border);
            display:flex;align-items:center;
            padding:0 1.1rem;z-index:99;
            font-size:.66rem;color:var(--muted);gap:.75rem;
        }
        .footer-appname{font-weight:700;color:var(--primary);cursor:pointer;text-decoration:none;white-space:nowrap;}
        .footer-appname:hover{text-decoration:underline dotted;}
        .footer-kredit{display:flex;align-items:center;gap:.6rem;margin-left:auto;flex-shrink:0;}
        .footer-kredit a{color:var(--muted);text-decoration:none;display:flex;align-items:center;gap:.25rem;transition:color .15s;font-size:.65rem;}
        .footer-kredit a:hover{color:var(--primary);}
        .footer-kredit .wa-link:hover{color:#25d366;}
        .footer-kredit .tg-link:hover{color:#2ca5e0;}

        #update-log-popup{
            display:none;position:fixed;bottom:calc(var(--footer-height) + 8px);
            left:var(--sidebar-width);width:340px;max-width:90vw;
            background:var(--card-bg);border:1px solid var(--border);border-radius:12px;
            box-shadow:0 8px 32px rgba(0,0,0,.18);z-index:200;overflow:hidden;
        }
        #update-log-popup.show{display:block;}
        .ulp-header{background:var(--primary);color:#fff;padding:.6rem 1rem;display:flex;align-items:center;justify-content:space-between;font-size:.8rem;font-weight:700;}
        .ulp-body{max-height:320px;overflow-y:auto;padding:.75rem 1rem;}
        .ulp-entry{margin-bottom:.85rem;padding-bottom:.85rem;border-bottom:1px solid var(--border);}
        .ulp-entry:last-child{border-bottom:none;margin-bottom:0;}
        .ulp-version{display:inline-flex;align-items:center;gap:.35rem;margin-bottom:.3rem;}
        .ulp-ver-badge{background:var(--primary);color:#fff;border-radius:4px;padding:.05rem .4rem;font-size:.68rem;font-weight:700;font-family:'JetBrains Mono',monospace;}
        .ulp-date{font-size:.65rem;color:var(--muted);}
        .ulp-notes{font-size:.75rem;color:var(--text);line-height:1.55;white-space:pre-line;}
        .ulp-latest{background:#e8f5e9;border:1px solid #c8e6c9;border-radius:3px;font-size:.58rem;color:#10ac84;padding:.05rem .3rem;font-weight:700;}

        /* PWA Install — selalu bisa muncul online/offline */
        #pwa-install-bar{
            display:none;position:fixed;bottom:var(--footer-height);left:var(--sidebar-width);right:0;
            background:var(--primary);color:#fff;padding:.6rem 1.1rem;z-index:98;
            align-items:center;justify-content:space-between;gap:.75rem;
            border-top:2px solid var(--accent);flex-wrap:wrap;
        }
        #pwa-install-bar.show{display:flex;}
        .pwa-install-btn{background:var(--accent);color:var(--primary);border:none;border-radius:6px;padding:.35rem .9rem;font-size:.75rem;font-weight:700;cursor:pointer;}
        .pwa-dismiss{background:none;border:none;color:rgba(255,255,255,.6);cursor:pointer;font-size:.8rem;}

        @media(max-width:991.98px){
            #sidebar{transform:translateX(-100%);}#sidebar.open{transform:none;}
            #sidebar-overlay.open{display:block;}
            #main{margin-left:0;}
            #app-footer,#pwa-install-bar{left:0;}
            #update-log-popup{left:1rem;}
            #btnToggle{display:block;}
            .content{padding:.75rem .85rem;padding-bottom:calc(var(--footer-height) + .5rem);}
            .footer-kredit{display:none;}
            .user-menu-name,.user-menu-role{display:none;}
            .user-menu-btn{padding:.25rem;border-radius:50%;border:none;}
        }
        @media(max-width:575.98px){.content{padding:.6rem .7rem;padding-bottom:calc(var(--footer-height) + .5rem);}.card-header{padding:.6rem .85rem;}.card-body{padding:.8rem;}}
    </style>
    @stack('styles')
</head>
<body data-role="{{ auth()->user()->isAdmin() ? 'admin' : 'operator' }}">

<div id="offline-bar">📵 Mode Offline — data akan disimpan lokal dan disinkronkan saat online</div>
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

<nav id="sidebar">
    <div class="brand-wrap">
        <div class="brand-icon">
            @if($logoPath)<img src="{{ $logoPath }}" alt="Logo" onerror="this.style.display='none'">
            @else<i class="bi bi-broadcast"></i>@endif
        </div>
        <div style="min-width:0">
            <div class="brand-name">MonOTOn</div>
            <div class="brand-sub">{{ Str::limit($satuanKerja,24) }}</div>
        </div>
    </div>
    <div class="sidebar-nav flex-fill py-1">
        <div class="nav-section">Utama</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-speedometer2"></i> Dashboard</a>
        @if(auth()->user()->isAdmin() || auth()->user()->isDivisi('transmisi'))
        <a href="{{ route('pemancar.index') }}" class="nav-link {{ request()->routeIs('pemancar.*')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-broadcast-pin"></i> Data Pemancar</a>
        @endif
        <div class="nav-section">Operasional</div>
        @if(auth()->user()->isAdmin() || auth()->user()->isDivisi('transmisi'))
        <a href="{{ route('operasional.create') }}" class="nav-link {{ request()->routeIs('operasional.create')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-pencil-square"></i> Catat Log</a>
        <a href="{{ route('operasional.index') }}" class="nav-link {{ request()->routeIs('operasional.index')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-journal-text"></i> Riwayat Log</a>
        @endif
        <a href="{{ route('eviden.index') }}" class="nav-link {{ request()->routeIs('eviden.*')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-camera"></i> Catatan Eviden</a>
        <div class="nav-section">Laporan</div>
        @if(auth()->user()->isAdmin() || auth()->user()->isDivisi('transmisi'))
        <a href="{{ route('laporan.index') }}" class="nav-link {{ request()->routeIs('laporan.index')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-file-earmark-pdf"></i> Cetak Laporan</a>
        <a href="{{ route('laporan.suhu') }}" class="nav-link {{ request()->routeIs('laporan.suhu*')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-thermometer-half"></i> Grafik Suhu</a>
        @endif
        <a href="{{ route('laporan.eviden-rekap') }}" class="nav-link {{ request()->routeIs('laporan.eviden-rekap*')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-journal-richtext"></i> Rekap Eviden</a>

        @if(auth()->user()->canAccessStudio())
        <div class="nav-section">Studio</div>
        <a href="{{ route('studio.logbook.index') }}" class="nav-link {{ request()->routeIs('studio.logbook.*')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-broadcast"></i> Logbook Siaran</a>
        <a href="{{ route('studio.perangkat.index') }}" class="nav-link {{ request()->routeIs('studio.perangkat.*')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-speaker"></i> Master Perangkat</a>
        <a href="{{ route('studio.maintenance.index') }}" class="nav-link {{ request()->routeIs('studio.maintenance.*')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-tools"></i> Maintenance Studio</a>
        @endif
        
        @if(auth()->user()->isAdmin() || auth()->user()->isDivisi('sarana') || auth()->user()->isDivisi('transmisi'))
        <div class="nav-section">Sarana & Prasarana</div>
        {{-- Maintenance: Admin + Sarana saja (bukan Transmisi) --}}
        @if(auth()->user()->isAdmin() || auth()->user()->isDivisi('sarana'))
        <a href="{{ route('maintenance.index') }}" class="nav-link {{ request()->routeIs('maintenance.*')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-tools"></i> Maintenance</a>
        @endif
        {{-- Operasional Genset: Admin + Sarana + Transmisi --}}
        <a href="{{ route('genset.index') }}" class="nav-link {{ request()->routeIs('genset.index')||request()->routeIs('genset.show')||request()->routeIs('genset.create')||request()->routeIs('genset.edit')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-battery-charging"></i> Operasional Genset</a>
        {{-- Aset & Inventaris: Admin + Sarana saja (bukan Transmisi) --}}
        @if(auth()->user()->isAdmin() || auth()->user()->isDivisi('sarana'))
        <a href="{{ route('aset.index') }}" class="nav-link {{ request()->routeIs('aset.*')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-box-seam"></i> Aset & Inventaris</a>
        @endif
        {{-- Kelola Unit Genset: Admin super + Admin Divisi Sarana --}}
        @if(auth()->user()->isAdmin() || (auth()->user()->isAdminDivisi() && auth()->user()->isDivisi('sarana')))
        <a href="{{ route('genset.units') }}" class="nav-link {{ request()->routeIs('genset-unit.*')||request()->routeIs('genset.units')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-gear-wide-connected"></i> Kelola Unit Genset</a>
        @endif
        @endif

        @if(auth()->user()->hasAdminAccess())
        <div class="nav-section">{{ auth()->user()->isAdminDivisi() ? 'Admin '.auth()->user()->divisi_label : 'Admin' }}</div>
        <a href="{{ route('jadwal.index') }}" class="nav-link {{ request()->routeIs('jadwal.*')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-calendar3"></i> Jadwal Shift</a>
        @if(auth()->user()->hasAdminAccess())
        <a href="{{ route('skema-shift.index') }}" class="nav-link {{ request()->routeIs('skema-shift.*')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-diagram-3"></i> Kelola Skema Shift</a>
        @endif
        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-people"></i> Manajemen User</a>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('setting.index') }}" class="nav-link {{ request()->routeIs('setting.*')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-gear"></i> Pengaturan App</a>
        <a href="{{ route('lokasi.index') }}" class="nav-link {{ request()->routeIs('lokasi.*')?'active':'' }}" onclick="closeSidebar()"><i class="bi bi-geo-alt"></i> Master Lokasi</a>
        @endif
        @endif
        @if(file_exists(public_path('js/offline-db.js')))
        <div class="nav-section">Sinkronisasi</div>
        <a href="{{ route('sync.index') }}" class="nav-link {{ request()->routeIs('sync.*')?'active':'' }}" onclick="closeSidebar()">
            <i class="bi bi-arrow-repeat"></i> Sync Manager
            <span id="sync-badge-nav" class="badge bg-warning ms-auto" style="display:none"></span>
        </a>
        @endif
    </div>
    <div class="sidebar-footer">
        <div class="app-ver">MonOTOn v{{ $appVersion }}</div>
    </div>
</nav>

<div id="main">
    <div id="topbar">
        <button id="btnToggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <div class="topbar-title">@yield('page-title','Dashboard')</div>
        @if(session('shift_aktif_no'))<div class="shift-badge"><i class="bi bi-clock me-1"></i>Shift {{ session('shift_aktif_no') }}</div>@endif
        @if(file_exists(public_path('js/offline-db.js')))
        <a href="{{ route('sync.index') }}" id="sync-badge-wrap" class="d-none text-decoration-none" title="Data offline pending">
            <span style="background:#ff9f43;color:#fff;border-radius:20px;padding:.2rem .65rem;font-size:.68rem;font-weight:600;display:flex;align-items:center;gap:.3rem;white-space:nowrap;animation:pw 2s infinite">
                <i class="bi bi-arrow-repeat"></i><span id="sync-badge">0</span><span class="d-none d-sm-inline">Pending</span>
            </span>
        </a>
        @endif
        @if(isset($peringatanPencatatan) && $peringatanPencatatan)
        <div class="alarm-badge" onclick="document.getElementById('alert-pencatatan').style.display='block'"><i class="bi bi-bell-fill"></i><span class="d-none d-sm-inline ms-1">Catat!</span></div>
        @endif
        <span class="mono text-muted d-none d-lg-block" style="font-size:.7rem;white-space:nowrap">{{ now()->format('d/m/Y H:i') }}</span>

        {{-- ── USER DROPDOWN POJOK KANAN ATAS ── --}}
        <div class="user-menu" id="userMenu">
            <button class="user-menu-btn" onclick="toggleUserMenu()">
                <div class="user-ava">{{ strtoupper(substr(auth()->user()->name,0,2)) }}</div>
                <div class="d-none d-md-block">
                    <div class="user-menu-name">{{ Str::limit(auth()->user()->name,16) }}</div>
                    <div class="user-menu-role">{{ auth()->user()->isAdmin()?'Administrator':(auth()->user()->isAdminDivisi()?'Admin '.(auth()->user()->divisi_label??'Divisi'):('Operator')) }}</div>
                </div>
                <i class="bi bi-chevron-down user-menu-caret d-none d-md-inline"></i>
            </button>
            <div class="user-menu-dropdown">
                <div class="umd-header">
                    <div class="user-ava">{{ strtoupper(substr(auth()->user()->name,0,2)) }}</div>
                    <div>
                        <div class="umd-header-name">{{ auth()->user()->name }}</div>
                        <div class="umd-header-sub">
                            {{ auth()->user()->isAdmin()?'Administrator':(auth()->user()->isAdminDivisi()?'Admin Divisi':'Operator') }}
                            @if(auth()->user()->divisi) &mdash; {{ auth()->user()->divisi_label }}@endif
                            @if(auth()->user()->lokasi_dinas) ({{ auth()->user()->lokasi_dinas }})@endif
                        </div>
                    </div>
                </div>
                <div class="umd-body">
                    {{-- Dark/Light mode toggle --}}
                    <div class="theme-toggle-row">
                        <span><i class="bi bi-moon-stars me-2"></i>Mode Gelap</span>
                        <div class="theme-switch" id="themeSwitch" onclick="toggleTheme()"></div>
                    </div>
                    <div class="umd-divider"></div>
                    <a href="{{ route('users.profile') }}" class="umd-item">
                        <i class="bi bi-person-vcard"></i> Profil Saya
                    </a>
                    <a href="{{ route('users.change-password') }}" class="umd-item">
                        <i class="bi bi-key"></i> Ganti Password
                    </a>
                    <button type="button" class="umd-item" onclick="installPWA()" id="umdInstallBtn" style="display:none">
                        <i class="bi bi-download"></i> Pasang Aplikasi
                    </button>
                    <div class="umd-divider"></div>
                   

<form id="logout-form"
      action="{{ route('logout') }}"
      method="POST"
      style="display:none;">
    @csrf
</form><form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="btn btn-danger">
        Logout
    </button>
</form>
                        
                </div>
            </div>
        </div>
    </div>

    @if(isset($peringatanPencatatan) && $peringatanPencatatan)
    <div id="alert-pencatatan" class="alert alert-warning alert-dismissible shadow" style="display:none">
        <i class="bi bi-alarm me-2"></i><strong>Pengingat!</strong> Sudah 3 jam sejak pencatatan terakhir.
        <a href="{{ route('operasional.create') }}" class="btn btn-sm btn-warning d-block mt-2 w-100"><i class="bi bi-pencil-square me-1"></i>Catat Sekarang</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div style="padding:.5rem 1.1rem 0">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 py-2 mb-2">
            <i class="bi bi-check-circle-fill flex-shrink-0"></i><span>{{ session('success') }}</span>
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

    <div id="main-content"><div class="content">@yield('content')</div></div>

    <footer id="app-footer">
        <span class="footer-appname" onclick="toggleUpdateLog()" title="Klik untuk melihat riwayat update">
            <strong>MonOTOn</strong> v{{ $appVersion }}
        </span>
        <span class="text-muted">|</span>
        <span class="d-none d-sm-inline" style="white-space:nowrap">{{ Str::limit($satuanKerja,30) }}</span>
        <div class="footer-kredit">
            <span style="color:#aaa;font-size:.62rem">by</span>
            <span style="font-weight:600;color:var(--muted)">{{ $settings['kredit_pembuat'] ?? $kPembuat }}</span>
            <a href="https://wa.me/{{ preg_replace('/\D/','',$settings['kredit_wa']??$kWa) }}" target="_blank" class="wa-link" title="WhatsApp">
                <i class="bi bi-whatsapp" style="color:#25d366"></i><span>{{ $settings['kredit_wa'] ?? $kWa }}</span>
            </a>
            <a href="https://t.me/{{ ltrim($settings['kredit_telegram']??$kTelegram,'@') }}" target="_blank" class="tg-link" title="Telegram">
                <i class="bi bi-telegram" style="color:#2ca5e0"></i><span>{{ $settings['kredit_telegram'] ?? $kTelegram }}</span>
            </a>
        </div>
        {{-- kredit:pembuat={{ $kPembuat }},wa={{ $kWa }},tg={{ $kTelegram }} --}}
    </footer>
</div>

{{-- POPUP UPDATE LOG --}}
<div id="update-log-popup">
    <div class="ulp-header">
        <span><i class="bi bi-clock-history me-2"></i>Riwayat Update — MonOTOn</span>
        <button onclick="closeUpdateLog()" style="background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;font-size:1rem">✕</button>
    </div>
    <div class="ulp-body">
        @forelse($logEntries as $i => $entry)
        @php
            $lines  = explode("\n", trim($entry), 2);
            $header = trim($lines[0] ?? '');
            $body   = trim($lines[1] ?? '');
            preg_match('/v(\d+\.\d+\.\d+)/', $header, $vm);
            $ver    = $vm[1] ?? null;
            preg_match('/\[([^\]]+)\]/', $header, $dm);
            $tgl    = $dm[1] ?? '';
        @endphp
        <div class="ulp-entry">
            <div class="ulp-version">
                @if($ver)<span class="ulp-ver-badge">v{{ $ver }}</span>@endif
                @if($i===0)<span class="ulp-latest">TERBARU</span>@endif
                @if($tgl)<span class="ulp-date">{{ $tgl }}</span>@endif
            </div>
            @if($body)<div class="ulp-notes">{{ $body }}</div>@endif
        </div>
        @empty
        <div class="text-center text-muted py-3" style="font-size:.8rem">
            <i class="bi bi-clock-history d-block fs-3 mb-1"></i>Belum ada riwayat update
        </div>
        @endforelse
    </div>
</div>

{{-- PWA Install Banner --}}
<div id="pwa-install-bar">
    <div style="font-size:.78rem;display:flex;align-items:center;gap:.5rem"><span>📱</span><span>Pasang MonOTOn di perangkat Anda untuk akses lebih cepat — bisa dipakai online maupun offline!</span></div>
    <div class="d-flex gap-2 align-items-center">
        <button class="pwa-install-btn" onclick="installPWA()"><i class="bi bi-download me-1"></i>Pasang</button>
        <button class="pwa-dismiss" onclick="dismissPWA()">✕</button>
    </div>
</div>

{{-- Lightbox Global --}}
<div class="modal fade" id="globalLightbox" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-body p-1 text-center">
                <img id="lightboxSrc" src="" style="max-height:88vh;max-width:100%;border-radius:8px;display:block;margin:auto">
                <div id="lightboxCaption" style="color:#ccc;font-size:.82rem;margin-top:.5rem;text-align:center"></div>
            </div>
            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2" data-bs-dismiss="modal"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebar-overlay').classList.toggle('open');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebar-overlay').classList.remove('open');}

// User dropdown menu
function toggleUserMenu(){document.getElementById('userMenu').classList.toggle('open');}
document.addEventListener('click', e=>{
    const um = document.getElementById('userMenu');
    if(um && !um.contains(e.target)) um.classList.remove('open');
});

// ── Dark / Light Mode ──
function applyThemeSwitchUI(){
    const theme = document.documentElement.getAttribute('data-theme') || 'light';
    const sw = document.getElementById('themeSwitch');
    if(sw) sw.classList.toggle('on', theme==='dark');
}
function toggleTheme(){
    const html = document.documentElement;
    const current = html.getAttribute('data-theme') || 'light';
    const next = current==='dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('monoton-theme', next);
    applyThemeSwitchUI();
}
document.addEventListener('DOMContentLoaded', applyThemeSwitchUI);

// Lightbox
function bukaLightbox(src,caption=''){document.getElementById('lightboxSrc').src=src;document.getElementById('lightboxCaption').textContent=caption;new bootstrap.Modal(document.getElementById('globalLightbox')).show();}
document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('.foto-lightbox-trigger').forEach(el=>{
        el.addEventListener('click',function(){bukaLightbox(this.dataset.src||this.src,this.dataset.caption||'');});
    });
});

// Update Log Popup
let ulpOpen=false;
function toggleUpdateLog(){ulpOpen=!ulpOpen;document.getElementById('update-log-popup').classList.toggle('show',ulpOpen);}
function closeUpdateLog(){ulpOpen=false;document.getElementById('update-log-popup').classList.remove('show');}
document.addEventListener('click', e=>{
    const popup=document.getElementById('update-log-popup');
    const trigger=document.querySelector('.footer-appname');
    if(ulpOpen && !popup.contains(e.target) && !trigger.contains(e.target)) closeUpdateLog();
});

// Offline indicator
document.addEventListener('DOMContentLoaded',()=>{
    const bar=document.getElementById('offline-bar');
    const updateBar=()=>bar?.classList.toggle('show',!navigator.onLine);
    updateBar();
    window.addEventListener('online',updateBar);
    window.addEventListener('offline',updateBar);
    if(typeof SyncManager!=='undefined') SyncManager.init();
});

// ── PWA Install: tampil baik online maupun offline ──
let deferredPrompt=null;
window.addEventListener('beforeinstallprompt', e=>{
    e.preventDefault();
    deferredPrompt=e;
    document.getElementById('umdInstallBtn').style.display='flex';
    // Tampilkan banner setelah 2 detik — TIDAK peduli online/offline
    if(!localStorage.getItem('pwa-dismissed')){
        setTimeout(()=>document.getElementById('pwa-install-bar').classList.add('show'), 2000);
    }
});
function installPWA(){
    if(!deferredPrompt){
        // Browser tidak mendukung beforeinstallprompt (misal Safari) — beri instruksi manual
        alert('Untuk memasang aplikasi:\n\n📱 Android (Chrome): Menu ⋮ → "Tambahkan ke layar utama"\n🍎 iPhone (Safari): Tombol Share → "Tambah ke Layar Utama"');
        return;
    }
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then(()=>{
        deferredPrompt=null;
        document.getElementById('pwa-install-bar').classList.remove('show');
        document.getElementById('umdInstallBtn').style.display='none';
    });
}
function dismissPWA(){document.getElementById('pwa-install-bar').classList.remove('show');localStorage.setItem('pwa-dismissed','1');}
window.addEventListener('appinstalled',()=>{
    document.getElementById('pwa-install-bar').classList.remove('show');
    document.getElementById('umdInstallBtn').style.display='none';
    localStorage.setItem('pwa-dismissed','1');
});
if('serviceWorker' in navigator){window.addEventListener('load',()=>{navigator.serviceWorker.register('/sw.js').then(()=>{ if(typeof PrecacheManager!=='undefined') PrecacheManager.run(); }).catch(e=>console.warn(e));});}

@if(isset($peringatanPencatatan) && $peringatanPencatatan)
setTimeout(()=>{const el=document.getElementById('alert-pencatatan');if(el)el.style.display='block';},1500);
@endif
</script>
@stack('scripts')
</body>
</html>