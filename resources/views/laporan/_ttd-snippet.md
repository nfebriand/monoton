# Cara pakai snippet TTD di template PDF

## 1. Di Controller, siapkan data koordinator:

```php
// LaporanController::generate() & suhuPdf() — divisi Transmisi
$koordinator = \App\Models\AppSetting::getKoordinator('transmisi');

// EvidenController::cetak() — sesuai divisi eviden
$koordinator = \App\Models\AppSetting::getKoordinator($eviden->divisi ?? 'transmisi');
```

Kirim variabel `$koordinator` (array: nama, nip, jabatan, ttd_url) ke view.

## 2. Di Blade (ttd-tbl), ganti cell "Mengetahui" dengan:

```blade
<td class="ttd-cell">
    <div>Mengetahui,</div>
    <div>{{ $koordinator['jabatan'] ?: 'Koordinator Teknik' }}</div>

    @if(!empty($koordinator['ttd_url']) && file_exists(public_path('uploads/'.$koordinator['ttd_path'])))
        <div style="height:55px;display:flex;align-items:center;justify-content:center">
            <img src="{{ $koordinator['ttd_url'] }}" style="max-height:50px;max-width:140px;object-fit:contain">
        </div>
    @else
        <div class="ttd-space"></div>
    @endif

    <div class="ttd-line">
        {{ $koordinator['nama'] ?: '( _________________________ )' }}
    </div>
    @if(!empty($koordinator['nip']))
        <div class="ttd-sub">NIP. {{ $koordinator['nip'] }}</div>
    @endif
</td>
```
