# Pejabat Penandatangan & Riwayat Pergantian

Semua laporan (PDF/Excel) memuat kolom "Mengetahui": Pengelola/Koordinator divisi,
Ketua Tim Kerja, dan Kepala LPP RRI Bandar Lampung.

## Prinsip
- Pejabat yang **sedang menjabat** disimpan di Pengaturan (`app_settings`).
- Pejabat yang **sudah berganti** diarsipkan di tabel `pejabat_riwayat` beserta masa jabatannya
  (`berlaku_mulai` s.d. `berlaku_sampai`) dan file tanda tangannya.
- Saat mencetak, aplikasi memilih pejabat yang menjabat pada **tanggal acuan laporan**:

| Laporan | Tanggal acuan |
|---|---|
| Operasional (rentang) | tanggal akhir rentang |
| Suhu bulanan | akhir bulan |
| Eviden (satuan) / bulk | tanggal eviden masing-masing |
| Rekap Eviden | tanggal akhir rentang (kosong: eviden terbaru) |
| Logbook studio harian | tanggal logbook |
| Logbook studio rentang | tanggal akhir rentang (kosong: log terbaru) |
| Rekap bulanan studio | akhir bulan |
| Maintenance studio | tanggal akhir rentang |
| Jadwal shift (Excel) | akhir bulan jadwal |
| Inventaris studio | hari ini (kondisi terkini) |

Tanggal acuan tidak pernah melebihi hari ini (`AppSetting::tanggalAcuan`), jadi laporan bulan berjalan
memakai pejabat yang menjabat hari ini. Bila tidak ada arsip yang mencakup tanggal acuan, dipakai pejabat saat ini.

## Prosedur saat ada pergantian
1. Super admin buka **Pengaturan -> Pergantian Pejabat**.
2. Pada jabatan terkait klik **Ganti Pejabat**, isi tanggal mulai berlaku, nama, NIP, (jabatan), TTD baru.
3. Pejabat lama otomatis diarsipkan (berakhir sehari sebelum tanggal berlaku). TTD lama tidak dihapus.
4. Salah ketik pada arsip dapat dikoreksi/dihapus dari halaman yang sama.

Jangan menimpa nama pejabat lama langsung di form Pengaturan saat terjadi pergantian: laporan periode lama ikut berubah.
Form Pengaturan hanya untuk membetulkan data pejabat yang sedang menjabat.

## Catatan
- Pergantian pertama: pejabat saat ini diarsipkan dengan `berlaku_mulai` kosong (sejak awal), karena tanggal
  mulainya belum tercatat. Isi tanggal mulai pejabat sekarang dengan mengganti pejabat lewat halaman ini, atau
  koreksi masa jabatan arsip setelah pergantian.
- Jabatan baku ada di `AppSetting::JABATAN_DEFAULT`.
