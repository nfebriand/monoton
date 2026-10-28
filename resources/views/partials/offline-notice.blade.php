{{-- Pemberitahuan mode offline untuk form ber-atribut data-offline-queue (lihat public/js/offline-forms.js) --}}
<div id="offline-notice-form" class="alert alert-warning align-items-center gap-2 py-2 mb-3" style="display:none">
    <i class="bi bi-cloud-slash fs-5 flex-shrink-0"></i>
    <div>
        <strong>Mode Offline</strong> &#x2014; Data yang disimpan tetap aman di perangkat ini dan
        dikirim otomatis ke server begitu koneksi tersambung. Pantau di menu <em>Sync Manager</em>.
    </div>
</div>
<script>
(function(){
    function t(){ var e=document.getElementById('offline-notice-form'); if(e) e.style.display = navigator.onLine ? 'none' : 'flex'; }
    window.addEventListener('online', t); window.addEventListener('offline', t);
    document.addEventListener('DOMContentLoaded', t);
})();
</script>
