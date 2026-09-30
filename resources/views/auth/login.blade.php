<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MonOTOn — Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @php
        $settings   = \App\Models\AppSetting::allKeyed();
        $tema       = $settings['tema_warna'] ?? '#0a3d62';
        $logoPath   = !empty($settings['logo_path']) ? asset('uploads/'.$settings['logo_path']) : null;
        $satkerName = $settings['satuan_kerja'] ?? 'Monitoring Operasional Transmisi Online';
    @endphp
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="{{ $tema }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="MonOTOn">
    <link rel="apple-touch-icon" href="/pwa/icon-192.png">
    <link rel="shortcut icon" href="/pwa/icon-96.png">
    <style>
        :root{ --primary:{{ $tema }}; --accent:#00d2d3; --accent2:#00ff88; --sat:#f59e0b; }
        *{box-sizing:border-box;margin:0;padding:0;}
        html,body{height:100%;overflow:hidden;}
        body{font-family:'Space Grotesk',sans-serif;background:#030d1a;display:flex;}

        /* ── KIRI: Peta ── */
        .bg-map{
            flex:1;position:relative;overflow:hidden;
            display:flex;flex-direction:column;
            justify-content:space-between;padding:1.5rem;
        }
        /* Grid overlay */
        .grid-overlay{
            position:absolute;inset:0;
            background-image:
                linear-gradient(rgba(0,210,211,.035) 1px,transparent 1px),
                linear-gradient(90deg,rgba(0,210,211,.035) 1px,transparent 1px);
            background-size:50px 50px;
            pointer-events:none;z-index:1;
        }

        /* ── SVG PETA ── */
        #peta-wrap{
            position:absolute;
            inset:0;
            display:flex;align-items:center;justify-content:center;
            padding:1rem;
            z-index:2;
        }
        #peta-wrap svg{
            width:100%;height:100%;
            max-width:900px;
        }

        /* Pulau Lampung path */
        .lampung-shape{
            fill:rgba(10,61,98,.35);
            stroke:rgba(0,210,211,.4);
            stroke-width:1.2;
        }

        /* Animasi sinyal */
        @keyframes flow-stl {
            0%  { stroke-dashoffset:0; }
            100%{ stroke-dashoffset:-40; }
        }
        @keyframes flow-sat {
            0%  { stroke-dashoffset:0; }
            100%{ stroke-dashoffset:-30; }
        }
        @keyframes flow-relay {
            0%  { stroke-dashoffset:0; }
            100%{ stroke-dashoffset:-24; }
        }
        @keyframes pulse-main {
            0%,100%{r:8;opacity:1;}
            50%{r:11;opacity:.8;}
        }
        @keyframes pulse-relay-node {
            0%,100%{r:5;opacity:1;}
            50%{r:7;opacity:.8;}
        }
        @keyframes ring-out {
            0%{r:12;opacity:.6;stroke-width:2;}
            100%{r:28;opacity:0;stroke-width:1;}
        }

        .stl-line   { stroke:#00ff88; stroke-width:2; stroke-dasharray:10,6; animation:flow-stl 1s linear infinite; fill:none; }
        .sat-line   { stroke:#f59e0b; stroke-width:1.5; stroke-dasharray:6,5; animation:flow-sat 1.2s linear infinite; fill:none; }
        .relay-line { stroke:#4fc3f7; stroke-width:1; stroke-dasharray:5,7; animation:flow-relay 1.8s linear infinite; fill:none; }

        /* Nodes */
        .n-main   { fill:#00d2d3; }
        .n-studio { fill:#00ff88; }
        .n-relay  { fill:#4fc3f7; }
        .n-sat    { fill:#f59e0b; }
        .ring     { fill:none; }
        .ring-main  { stroke:#00d2d3; animation:ring-out 2s ease-out infinite; }
        .ring-studio{ stroke:#00ff88; animation:ring-out 2.5s ease-out infinite; }

        /* Label nodes */
        .lbl{
            font-family:'Space Grotesk',sans-serif;
            font-size:7.5px;
            fill:#cce;
            text-anchor:middle;
        }
        .lbl-bold{ font-weight:700; fill:#fff; }
        .lbl-freq{ font-size:6px; fill:#00d2d3; }
        .lbl-sat-freq{ font-size:6px; fill:#f59e0b; }

        /* Kompas */
        .compass{font-size:9px;fill:rgba(0,210,211,.6);font-family:'Space Grotesk',sans-serif;}

        /* ── BRAND TOP LEFT ── */
        .brand-top{
            position:relative;z-index:10;
            display:flex;align-items:center;gap:.75rem;
        }
        .brand-logo{
            width:46px;height:46px;
            background:rgba(0,210,211,.15);border:2px solid var(--accent);
            border-radius:12px;display:flex;align-items:center;justify-content:center;
            font-size:1.3rem;color:var(--accent);
            box-shadow:0 0 20px rgba(0,210,211,.3);overflow:hidden;
        }
        .brand-logo img{width:100%;height:100%;object-fit:cover;}
        .brand-name{font-size:1.6rem;font-weight:700;color:#fff;letter-spacing:1px;}
        .brand-name span{color:var(--accent);}
        .brand-sub{font-size:.7rem;color:rgba(0,210,211,.8);}
        .satker-name{font-size:.7rem;color:#4fc3f7;font-weight:600;}

        /* ── LEGEND ── */
        .map-legend{
            position:absolute;bottom:1.5rem;left:1.5rem;z-index:10;
            background:rgba(3,13,26,.88);border:1px solid rgba(0,210,211,.2);
            border-radius:10px;padding:.7rem 1rem;
        }
        .leg-title{font-size:.58rem;font-weight:700;color:var(--accent);
            letter-spacing:1.5px;text-transform:uppercase;margin-bottom:.5rem;}
        .leg-row{display:flex;align-items:center;gap:.5rem;font-size:.65rem;
            color:#aac;margin-bottom:.3rem;}
        .leg-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;}
        .leg-line{width:20px;height:2px;flex-shrink:0;border-radius:1px;}
        .leg-dash{width:20px;height:0;border-top:2px dashed;flex-shrink:0;}

        /* ── QUOTE BOTTOM RIGHT ── */
        .quote-bottom{
            position:relative;z-index:10;text-align:right;
            color:rgba(170,200,220,.6);font-size:.68rem;
            font-style:italic;line-height:1.6;
        }

        /* ── PANEL LOGIN KANAN ── */
        .login-panel{
            width:400px;min-width:360px;
            background:rgba(255,255,255,.97);
            display:flex;flex-direction:column;justify-content:center;
            padding:2rem 1.75rem;
            box-shadow:-20px 0 60px rgba(0,0,0,.5);
        }
        .login-card-inner{
            background:#fff;border-radius:16px;
            padding:1.75rem 1.5rem;
            box-shadow:0 4px 24px rgba(0,0,0,.08);
        }
        .panel-brand{display:flex;align-items:center;gap:.7rem;margin-bottom:1.5rem;}
        .panel-brand-icon{
            width:50px;height:50px;min-width:50px;
            background:var(--primary);border-radius:13px;
            display:flex;align-items:center;justify-content:center;
            font-size:1.4rem;color:var(--accent);
            box-shadow:0 6px 18px rgba(10,61,98,.3);overflow:hidden;
        }
        .panel-brand-icon img{width:100%;height:100%;object-fit:cover;}
        .panel-brand-name{font-size:1.25rem;font-weight:700;color:#0a3d62;margin:0;}
        .panel-brand-sub{font-size:.65rem;color:#636e72;margin:0;}



        .form-lbl{font-size:.76rem;font-weight:600;color:#2d3436;margin-bottom:.28rem;}
        .inp-wrap{position:relative;display:flex;align-items:center;}
        .inp-wrap .ii{position:absolute;left:.8rem;color:#bbb;font-size:.9rem;pointer-events:none;}
        .inp-wrap input{
            width:100%;padding:.6rem .9rem .6rem 2.4rem;
            border:1.5px solid #e0e0e0;border-radius:10px;
            font-size:.85rem;font-family:'Space Grotesk',sans-serif;
            background:#fafafa;transition:border-color .2s;
        }
        .inp-wrap input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(10,61,98,.1);background:#fff;}
        .btn-login{
            width:100%;padding:.7rem;background:var(--primary);color:#fff;
            border:none;border-radius:10px;font-size:.9rem;font-weight:700;
            font-family:'Space Grotesk',sans-serif;cursor:pointer;
            display:flex;align-items:center;justify-content:center;gap:.5rem;
            transition:all .25s;margin-top:.7rem;
        }
        .btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(10,61,98,.35);}
        .alert-box{background:#fff0f0;border:1px solid #ffcccc;border-radius:8px;padding:.6rem .85rem;font-size:.76rem;color:#c0392b;margin-bottom:.9rem;}

        @media(max-width:860px){.bg-map{display:none;}.login-panel{width:100%;min-width:100%;box-shadow:none;}body{background:var(--primary);}}
    </style>
</head>
<body>

{{-- ── KIRI: PETA LAMPUNG ── --}}

<div class="bg-map">
    <div id="map"></div>
    <div class="grid-overlay"></div>

    <div class="brand-top">
        <div class="brand-logo">
            @if($logoPath)
            <img src="{{ $logoPath }}" alt="Logo">
            @else
            <i class="bi bi-broadcast"></i>
            @endif
        </div>
        <div>
            <div class="brand-name">Mon<span>OTO</span>n</div>
            <div class="brand-sub">Monitoring Operasional Transmisi Online</div>
        </div>
    </div>

    <div class="map-legend">
        <div class="leg-title">Keterangan Jaringan</div>
        <div class="leg-row"><div class="leg-dot" style="background:#00ff88"></div><span>Studio</span></div>
        <div class="leg-row"><div class="leg-dot" style="background:#00d2d3"></div><span>Pemancar Utama</span></div>
        <div class="leg-row"><div class="leg-dot" style="background:#4fc3f7"></div><span>Relay</span></div>
        <div class="leg-row"><div class="leg-dash" style="border-color:#00ff88"></div><span>STL Link</span></div>
    </div>

    <div class="quote-bottom">
        <div>"Menghubungkan Informasi,</div>
        <div>Menjaga Siaran Tetap Mengudara"</div>
    </div>
</div>
<div class="login-panel">
    <div class="login-card-inner">
        <div class="panel-brand">
            <div class="panel-brand-icon">
                @if($logoPath)
                <img src="{{ $logoPath }}" alt="Logo"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <i class="bi bi-broadcast" style="display:none;font-size:1.4rem;color:var(--accent)"></i>
                @else
                <i class="bi bi-broadcast" style="font-size:1.4rem"></i>
                @endif
            </div>
            <div>
                <p class="panel-brand-name">MonOTOn</p>
                <p class="panel-brand-sub">Monitoring Operasional Transmisi Online</p>
            </div>
        </div>

        @if($errors->any())
        <div class="alert-box">
            <i class="bi bi-exclamation-triangle me-1"></i>
            @foreach($errors->all() as $e){{ $e }}<br>@endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
        @csrf
        <div class="mb-3">
            <div class="form-lbl">Email</div>
            <div class="inp-wrap">
                <i class="bi bi-envelope ii"></i>
                <input type="email" name="email" value="{{ old('email') }}"
                       placeholder="operator@mono.id" required autocomplete="email" autofocus>
            </div>
        </div>
        <div class="mb-1">
            <div class="form-lbl">Password</div>
            <div class="inp-wrap">
                <i class="bi bi-lock ii"></i>
                <input type="password" name="password" placeholder=""
                       required autocomplete="current-password">
            </div>
        </div>
        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right"></i> Masuk ke Sistem
        </button>
        </form>
    </div>
    <div class="text-center mt-2" style="font-size:.65rem;color:#aaa">
        MonOTOn v{{ $settings['app_version'] ?? '1.0.0' }}
        @if($satkerName && $satkerName !== 'Monitoring Operasional Transmisi Online')
        &nbsp;|&nbsp; {{ $satkerName }}
        @endif
    </div>
</div>

<script>
    if('serviceWorker' in navigator){
    window.addEventListener('load',()=>{
        navigator.serviceWorker.register('/sw.js')
            .then(r=>console.log('[MonOTOn] SW:',r.scope))
            .catch(e=>console.warn('[MonOTOn] SW:',e));
    });
}
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>

<script>
document.addEventListener("DOMContentLoaded", function(){

// FAST MAP INIT (Canvas renderer)
const map = L.map('map',{
    zoomControl:false,
    attributionControl:false,
    preferCanvas:true
}).setView([-5.45,105.27],9);

// faster tile (lighter)
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png',{
    maxZoom:18
}).addTo(map);

// REMOVE heavy geojson (speed fix)
const sites = [
{n:"Studio RRI",t:"studio",lat:-5.4246,lon:105.2736},
{n:"Pemancar Gedung Air",t:"main",lat:-5.4011,lon:105.2388},
{n:"Sukarame",t:"relay",lat:-5.3811,lon:105.2976},
{n:"Ketapang",t:"relay",lat:-4.7164,lon:104.7862},
{n:"Bakauheni",t:"relay",lat:-5.8556,lon:105.7394}
];

// CLUSTER GROUP (FAST RENDER)
const cluster = L.markerClusterGroup({
    chunkedLoading:true,
    disableClusteringAtZoom:11
});

function icon(t){
let c="#4fc3f7";
if(t==="studio")c="#00ff88";
if(t==="main")c="#00d2d3";

return L.divIcon({
className:"",
html:`<div style="
width:8px;height:8px;
background:${c};
border-radius:50%;
box-shadow:none;
border:1px solid #fff;
"></div>`,
iconSize:[8,8]
});
}

// add markers (lightweight)
sites.forEach(s=>{
    const m = L.marker([s.lat,s.lon],{icon:icon(s.t)});
    m.bindPopup(s.n,{maxWidth:120,autoPan:false});
    cluster.addLayer(m);
});

map.addLayer(cluster);

// SIMPLE LINKS (NO ANIMATION)
const links=[
[[-5.4246,105.2736],[-5.4011,105.2388]],
[[-5.4011,105.2388],[-5.3811,105.2976]],
[[-5.4011,105.2388],[-5.8556,105.7394]]
];

L.polyline(links,{
color:"#00ff88",
weight:1,
opacity:0.6
}).addTo(map);

});
</script>
</body>

</body>

</html>
