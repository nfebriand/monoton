@extends('layouts.app')
@section('title','Sync Manager')
@section('page-title','Manajer Sinkronisasi')

@section('content')

{{-- Status Koneksi --}}
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card" id="koneksi-card">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div id="koneksi-icon" style="width:48px;height:48px;min-width:48px;border-radius:50%;
                     display:flex;align-items:center;justify-content:center;font-size:1.5rem">
                </div>
                <div class="flex-fill">
                    <div class="fw-bold" id="koneksi-label" style="font-size:.95rem"></div>
                    <div class="text-muted" id="koneksi-sub" style="font-size:.77rem"></div>
                </div>
                <button class="btn btn-sm btn-outline-primary" onclick="SyncManager.syncAll()" id="btnSyncNow">
                    <i class="bi bi-arrow-repeat me-1"></i>Sync Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.65rem;color:#636e72">LOG PENDING</div>
                <div class="mono fw-bold" id="countLogs" style="font-size:1.8rem;color:#ff9f43">0</div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.65rem;color:#636e72">EVIDEN PENDING</div>
                <div class="mono fw-bold" id="countEvidens" style="font-size:1.8rem;color:#ff9f43">0</div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center">
            <div class="card-body py-2">
                <div style="font-size:.65rem;color:#636e72">GAGAL</div>
                <div class="mono fw-bold" id="countFailed" style="font-size:1.8rem;color:#ee5a24">0</div>
            </div>
        </div>
    </div>
</div>

{{-- Daftar Data Pending --}}
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-journal-text me-2 text-warning"></i>Log Operasional Pending</span>
        <button class="btn btn-sm btn-outline-danger" onclick="hapusSemua('pending_logs')">
            <i class="bi bi-trash me-1"></i>Hapus Semua
        </button>
    </div>
    <div id="listLogs">
        <div class="text-center text-muted py-4 small">
            <i class="bi bi-check-circle d-block fs-3 mb-1 text-success"></i>
            Tidak ada log pending
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-camera me-2 text-warning"></i>Eviden Pending</span>
        <button class="btn btn-sm btn-outline-danger" onclick="hapusSemua('pending_evidens')">
            <i class="bi bi-trash me-1"></i>Hapus Semua
        </button>
    </div>
    <div id="listEvidens">
        <div class="text-center text-muted py-4 small">
            <i class="bi bi-check-circle d-block fs-3 mb-1 text-success"></i>
            Tidak ada eviden pending
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="/js/offline-db.js"></script>
<script src="/js/offline-sync.js"></script>
<script>
const STATUS_COLOR = { pending:'#ff9f43', synced:'#10ac84', failed:'#ee5a24' };
const STATUS_LABEL = { pending:'Menunggu', synced:'Tersinkron', failed:'Gagal' };
const STATUS_ICON  = { pending:'⏳', synced:'✅', failed:'❌' };

async function renderPage() {
    // Status koneksi
    const online = navigator.onLine;
    const card   = document.getElementById('koneksi-card');
    const icon   = document.getElementById('koneksi-icon');
    const label  = document.getElementById('koneksi-label');
    const sub    = document.getElementById('koneksi-sub');
    const btnSync = document.getElementById('btnSyncNow');

    if (online) {
        card.style.borderColor = '#10ac84';
        icon.style.background  = '#e8f8f3';
        icon.innerHTML = '🌐';
        label.textContent = 'Terhubung ke Internet';
        sub.textContent   = 'Data akan disinkronkan secara otomatis';
        btnSync.disabled  = false;
    } else {
        card.style.borderColor = '#ff9f43';
        icon.style.background  = '#fff5e6';
        icon.innerHTML = '📵';
        label.textContent = 'Mode Offline';
        sub.textContent   = 'Data disimpan lokal, akan sync saat online';
        btnSync.disabled  = true;
    }

    // Load data
    const [logs, evidens] = await Promise.all([
        OfflineDB.getAll('pending_logs'),
        OfflineDB.getAll('pending_evidens'),
    ]);

    const pendingLogs    = logs.filter(l => l.status === 'pending').length;
    const pendingEvidens = evidens.filter(e => e.status === 'pending').length;
    const failed         = [...logs, ...evidens].filter(x => x.status === 'failed').length;

    document.getElementById('countLogs').textContent    = pendingLogs;
    document.getElementById('countEvidens').textContent = pendingEvidens;
    document.getElementById('countFailed').textContent  = failed;

    // Render log list
    renderList('listLogs', logs, 'pending_logs', renderLogItem);
    renderList('listEvidens', evidens, 'pending_evidens', renderEvidenItem);
}

function renderList(elId, items, store, renderFn) {
    const el = document.getElementById(elId);
    if (!items.length) {
        el.innerHTML = `<div class="text-center text-muted py-4 small">
            <i class="bi bi-check-circle d-block fs-3 mb-1 text-success"></i>Tidak ada data pending</div>`;
        return;
    }
    el.innerHTML = items.map(item => renderFn(item, store)).join('');
}

function renderLogItem(item, store) {
    const d    = item.data || {};
    const dt   = new Date(item.createdAt).toLocaleString('id-ID');
    const clr  = STATUS_COLOR[item.status] || '#888';
    const pemc = d.pemancar ? `${d.pemancar.length} pemancar` : '–';
    return `<div class="d-flex align-items-center gap-3 px-3 py-3 border-bottom">
        <div style="font-size:1.5rem">${STATUS_ICON[item.status] || '⏳'}</div>
        <div class="flex-fill" style="min-width:0">
            <div class="fw-bold" style="font-size:.85rem">Log Operasional — ${pemc}</div>
            <div class="text-muted" style="font-size:.73rem">
                📅 ${d.dicatat_pada || '–'} &nbsp;|&nbsp; Disimpan: ${dt}
                ${item.retries > 0 ? `&nbsp;|&nbsp; <span class="text-danger">Retry: ${item.retries}x</span>` : ''}
                ${item.lastError ? `<br><span class="text-danger">Error: ${item.lastError}</span>` : ''}
            </div>
        </div>
        <span class="badge" style="background:${clr};font-size:.65rem">${STATUS_LABEL[item.status]||item.status}</span>
        <button class="btn btn-sm btn-outline-danger" style="padding:.1rem .35rem"
                onclick="hapusItem('${store}',${item.localId})">
            <i class="bi bi-trash"></i>
        </button>
    </div>`;
}

function renderEvidenItem(item, store) {
    const d   = item.data || {};
    const dt  = new Date(item.createdAt).toLocaleString('id-ID');
    const clr = STATUS_COLOR[item.status] || '#888';
    const fotoCount = (item.fotos || []).length;
    return `<div class="d-flex align-items-center gap-3 px-3 py-3 border-bottom">
        <div style="font-size:1.5rem">${STATUS_ICON[item.status] || '⏳'}</div>
        <div class="flex-fill" style="min-width:0">
            <div class="fw-bold" style="font-size:.85rem">${d.judul || 'Eviden'}</div>
            <div class="text-muted" style="font-size:.73rem">
                📅 ${d.tanggal || '–'} &nbsp;|&nbsp; 📷 ${fotoCount} foto &nbsp;|&nbsp; Disimpan: ${dt}
                ${item.retries > 0 ? `&nbsp;|&nbsp; <span class="text-danger">Retry: ${item.retries}x</span>` : ''}
                ${item.lastError ? `<br><span class="text-danger">Error: ${item.lastError}</span>` : ''}
            </div>
        </div>
        <span class="badge" style="background:${clr};font-size:.65rem">${STATUS_LABEL[item.status]||item.status}</span>
        <button class="btn btn-sm btn-outline-danger" style="padding:.1rem .35rem"
                onclick="hapusItem('${store}',${item.localId})">
            <i class="bi bi-trash"></i>
        </button>
    </div>`;
}

async function hapusItem(store, localId) {
    if (!confirm('Hapus data ini dari antrian?')) return;
    await OfflineDB.delete(store, localId);
    await SyncManager.updateBadge();
    renderPage();
}

async function hapusSemua(store) {
    const items = await OfflineDB.getAll(store);
    if (!items.length) return;
    if (!confirm(`Hapus semua ${items.length} item dari antrian?`)) return;
    await OfflineDB.clear(store);
    await SyncManager.updateBadge();
    renderPage();
}

// Update tiap 5 detik
document.addEventListener('DOMContentLoaded', () => {
    SyncManager.init();
    renderPage();
    setInterval(renderPage, 5000);
});
window.addEventListener('online',  renderPage);
window.addEventListener('offline', renderPage);
</script>
@endpush
