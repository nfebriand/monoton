<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key','value'];
    public $timestamps = false;

    public static function get(string $key, $default = null)
    {
        $row = self::where('key',$key)->first();
        return $row ? $row->value : $default;
    }

    public static function set(string $key, $value): void
    {
        self::updateOrCreate(['key'=>$key], ['value'=>$value]);
    }

    public static function allKeyed(): array
    {
        return self::pluck('value','key')->toArray();
    }

    /**
     * Base64 data-URI dari file TTD, siap dipakai langsung di <img src="">
     * pada PDF (dompdf tidak boleh fetch remote, jadi tidak bisa pakai ttd_url).
     * Null kalau path kosong atau file tidak ditemukan di storage.
     */
    private static function ttdBase64(?string $path): ?string
    {
        if (!$path) return null;
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        if (!$disk->exists($path)) return null;
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg');
        return 'data:'.$mime.';base64,'.base64_encode($disk->get($path));
    }

    /** Jabatan baku (default) penandatangan. Ubah di sini bila nomenklatur berganti. */
    public const JABATAN_DEFAULT = [
        'transmisi' => 'Pengelola Transmisi dan Distribusi',
        'studio'    => 'Pengelola Teknik Studio & Media Baru',
        'studio_wk' => 'Koordinator Studio Produksi Way Kanan',
        'sarana'    => 'Pengelola Teknik Sarana & Prasarana',
        'kabid'     => 'Ketua Tim Kerja Teknologi & Media Baru',
        'kepala'    => 'Kepala LPP RRI Bandar Lampung',
    ];

    /** Kode pejabat yang punya riwayat: koordinator per divisi, kabid (ketua tim), kepala stasiun. */
    public const KODE_PEJABAT = ['transmisi','studio','studio_wk','sarana','kabid','kepala'];

    /** Nama key app_settings untuk pejabat aktif saat ini. */
    public static function kunciPejabat(string $kode): array
    {
        return match ($kode) {
            'kabid'  => ['nama'=>'kabid_nama', 'nip'=>'kabid_nip', 'jabatan'=>'kabid_jabatan', 'ttd'=>'kabid_ttd', 'mulai'=>'pejabat_kabid_mulai'],
            'kepala' => ['nama'=>'kepala_stasiun', 'nip'=>'kepala_stasiun_nip', 'jabatan'=>'kepala_stasiun_jabatan', 'ttd'=>'kepala_stasiun_ttd', 'mulai'=>'pejabat_kepala_mulai'],
            default  => [
                'nama'=>"koordinator_{$kode}_nama", 'nip'=>"koordinator_{$kode}_nip",
                'jabatan'=>"koordinator_{$kode}_jabatan", 'ttd'=>"koordinator_{$kode}_ttd",
                'mulai'=>"pejabat_{$kode}_mulai",
            ],
        };
    }

    /** Susun array profil standar yang dipakai semua PDF/Excel. */
    private static function profil(?string $nama, ?string $nip, ?string $jabatan, ?string $ttd, string $kode): array
    {
        $ttd = (string) $ttd;
        return [
            'nama'    => (string) $nama,
            'nip'     => (string) $nip,
            'jabatan' => trim((string) $jabatan) !== '' ? $jabatan : self::JABATAN_DEFAULT[$kode],
            'ttd_path'=> $ttd,
            'ttd_url' => $ttd ? \App\Helpers\ImageHelper::url($ttd) : null,
            'ttd_base64' => self::ttdBase64($ttd),
        ];
    }

    /**
     * Tanggal acuan penandatangan: tanggal akhir periode laporan, tetapi tidak melebihi hari ini.
     * (Laporan bulan berjalan memakai pejabat yang menjabat hari ini.) Null = pejabat saat ini.
     */
    public static function tanggalAcuan($akhirPeriode): ?string
    {
        if (empty($akhirPeriode)) return null;
        try {
            $t = \Carbon\Carbon::parse($akhirPeriode)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
        $hariIni = \Carbon\Carbon::today();
        return ($t->gt($hariIni) ? $hariIni : $t)->toDateString();
    }

    private static ?bool $adaRiwayat = null;

    /** Pejabat yang menjabat pada $tanggal menurut arsip riwayat; null bila tidak ada arsip yang mencakup tanggal itu. */
    public static function pejabatRiwayat(string $kode, ?string $tanggal): ?array
    {
        if (empty($tanggal)) return null;
        try {
            if (self::$adaRiwayat === null) {
                self::$adaRiwayat = \Illuminate\Support\Facades\Schema::hasTable('pejabat_riwayat');
            }
            if (!self::$adaRiwayat) return null;
            $tgl = \Carbon\Carbon::parse($tanggal)->toDateString();
            $r = PejabatRiwayat::where('kode', $kode)
                ->whereDate('berlaku_sampai', '>=', $tgl)
                ->where(function ($q) use ($tgl) {
                    $q->whereNull('berlaku_mulai')->orWhereDate('berlaku_mulai', '<=', $tgl);
                })
                ->orderByDesc('berlaku_mulai')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
        return $r ? self::profil($r->nama, $r->nip, $r->jabatan, $r->ttd_path, $kode) : null;
    }

    /**
     * Profil koordinator/pengelola untuk divisi tertentu.
     * $divisi: 'transmisi' | 'studio' | 'sarana' | 'studio_wk'
     * $tanggal: tanggal acuan laporan (lihat tanggalAcuan); null = pejabat saat ini.
     *
     * Return: ['nama','nip','jabatan','ttd_path','ttd_url','ttd_base64']
     */
    public static function getKoordinator(string $divisi, ?string $tanggal = null): array
    {
        $divisi = in_array($divisi, ['transmisi','studio','sarana','studio_wk']) ? $divisi : 'transmisi';
        if ($p = self::pejabatRiwayat($divisi, $tanggal)) return $p;

        $s = self::allKeyed();
        $k = self::kunciPejabat($divisi);
        $nama = $s[$k['nama']] ?? '';
        // Fallback ke key lama 'koordinator' untuk divisi transmisi (backward compat)
        if ($divisi === 'transmisi' && empty($nama)) $nama = $s['koordinator'] ?? '';

        return self::profil($nama, $s[$k['nip']] ?? '', $s[$k['jabatan']] ?? '', $s[$k['ttd']] ?? '', $divisi);
    }

    /**
     * Koordinator penandatangan laporan Studio sesuai lokasi.
     * Lokasi 'Way Kanan' -> Koordinator Studio Produksi Way Kanan (bila terisi),
     * selain itu / bila belum diisi -> Pengelola Teknik Studio (Pahoman).
     */
    public static function getKoordinatorStudio(?string $lokasi = null, ?string $tanggal = null): array
    {
        if ($lokasi !== null && strcasecmp(trim($lokasi), 'Way Kanan') === 0) {
            $wk = self::getKoordinator('studio_wk', $tanggal);
            if (!empty($wk['nama'])) return $wk;
        }
        return self::getKoordinator('studio', $tanggal);
    }

    /** Profil Ketua Tim Kerja (dulu Kepala Bidang Teknik), lintas divisi. */
    public static function getKabid(?string $tanggal = null): array
    {
        if ($p = self::pejabatRiwayat('kabid', $tanggal)) return $p;
        $s = self::allKeyed();
        return self::profil($s['kabid_nama'] ?? ($s['kepala_bidang'] ?? ''), $s['kabid_nip'] ?? '', $s['kabid_jabatan'] ?? '', $s['kabid_ttd'] ?? '', 'kabid');
    }

    /** Profil Kepala LPP RRI (penandatangan tertinggi / "Mengetahui"). Key 'kepala_stasiun' = nama. */
    public static function getKepalaStasiun(?string $tanggal = null): array
    {
        if ($p = self::pejabatRiwayat('kepala', $tanggal)) return $p;
        $s = self::allKeyed();
        return self::profil($s['kepala_stasiun'] ?? '', $s['kepala_stasiun_nip'] ?? '', $s['kepala_stasiun_jabatan'] ?? '', $s['kepala_stasiun_ttd'] ?? '', 'kepala');
    }

    /** Path logo instansi (RRI) untuk dokumen/laporan; kosong bila belum diupload. */
    public static function logoInstansiPath(): string
    {
        return (string) self::get('logo_instansi', '');
    }

    /**
     * Validasi kredit aplikasi tetap utuh
     */
    public static function kreditValid(): bool
    {
        $kPembuat  = 'Nanda Febriandy';
        $kWa       = '082182778608';
        $kTelegram = '@nfebriand';
        $s = self::allKeyed();
        return ($s['kredit_pembuat'] ?? $kPembuat) === $kPembuat
            && ($s['kredit_wa'] ?? $kWa) === $kWa
            && ($s['kredit_telegram'] ?? $kTelegram) === $kTelegram;
    }

    public static function incrementVersion(string $version, string $tipe): string
    {
        $parts = array_map('intval', explode('.', $version));
        while (count($parts) < 3) $parts[] = 0;
        [$major,$minor,$patch] = $parts;

        match($tipe) {
            'major' => [$major++, $minor = 0, $patch = 0],
            'minor' => [$minor++, $patch = 0],
            default => $patch++,
        };

        return "{$major}.{$minor}.{$patch}";
    }

    public static function addUpdateLog(string $catatan, string $tipe = 'patch'): void
    {
        $current = self::get('app_version','1.0.0');
        $new     = self::incrementVersion($current, $tipe);

        $entry = "v{$new} [".now()->format('d/m/Y H:i')."]\n{$catatan}";
        $existing = self::get('update_log','');
        $combined = $existing ? $entry."\n\n".$existing : $entry;

        self::set('app_version', $new);
        self::set('update_log', $combined);
    }
}