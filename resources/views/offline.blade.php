<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MonOTOn — Offline</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{
            font-family:'Segoe UI',Arial,sans-serif;
            background:#030d1a;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            color:#fff;
            text-align:center;
            padding:2rem;
        }
        .wrap{max-width:360px;}
        .icon{
            font-size:5rem;
            margin-bottom:1.5rem;
            opacity:.7;
            animation:pulse 2s infinite;
        }
        @keyframes pulse{0%,100%{opacity:.7}50%{opacity:1}}
        h1{font-size:1.5rem;font-weight:700;margin-bottom:.75rem;color:#00d2d3;}
        p{font-size:.9rem;color:rgba(200,220,240,.7);line-height:1.6;margin-bottom:2rem;}
        .btn-retry{
            background:#0a3d62;color:#00d2d3;
            border:2px solid #00d2d3;
            border-radius:10px;
            padding:.75rem 2rem;
            font-size:.9rem;font-weight:700;
            cursor:pointer;text-decoration:none;
            display:inline-flex;align-items:center;gap:.5rem;
            transition:all .2s;
        }
        .btn-retry:hover{background:#00d2d3;color:#0a3d62;}
        .ver{margin-top:2rem;font-size:.65rem;color:rgba(255,255,255,.25);}
    </style>
</head>
<body>
<div class="wrap">
    <div class="icon">📡</div>
    <h1>Tidak Ada Koneksi</h1>
    <p>MonOTOn memerlukan koneksi internet untuk bekerja. Pastikan perangkat Anda terhubung ke internet dan coba lagi.</p>
    <a href="/" class="btn-retry" onclick="window.location.reload()">
        🔄 Coba Lagi
    </a>
    <div class="ver">MonOTOn — Monitoring Operasional Transmisi Online</div>
</div>
</body>
</html>
