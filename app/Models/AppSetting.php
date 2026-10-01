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

    /**
     * Ambil profil koordinator untuk divisi tertentu.
     * $divisi: 'transmisi' | 'studio' | 'sarana'
     *
     * Return: ['nama','nip','jabatan','ttd_path','ttd_url']
     */
    public static function getKoordinator(string $divisi): array
    {
        $divisi = in_array($divisi, ['transmisi','studio','sarana']) ? $divisi : 'transmisi';
        $s = self::allKeyed();

        $nama    = $s["koordinator_{$divisi}_nama"]    ?? '';
        $nip     = $s["koordinator_{$divisi}_nip"]     ?? '';
        $jabatan = $s["koordinator_{$divisi}_jabatan"] ?? 'Koordinator '.ucfirst($divisi);
        $ttd     = $s["koordinator_{$divisi}_ttd"]     ?? '';

        // Fallback ke key lama 'koordinator' untuk divisi transmisi (backward compat)
        if ($divisi === 'transmisi' && empty($nama)) {
            $nama = $s['koordinator'] ?? '';
        }

        return [
            'nama'    => $nama,
            'nip'     => $nip,
            'jabatan' => $jabatan,
            'ttd_path'=> $ttd,
            'ttd_url' => $ttd ? \App\Helpers\ImageHelper::url($ttd) : null,
            'ttd_base64' => self::ttdBase64($ttd),
        ];
    }

    /**
     * Profil Kepala Bidang Teknik (lintas divisi)
     */
    public static function getKabid(): array
    {
        $s = self::allKeyed();
        $nama    = $s['kabid_nama']    ?? ($s['kepala_bidang'] ?? '');
        $nip     = $s['kabid_nip']     ?? '';
        $jabatan = $s['kabid_jabatan'] ?? 'Kepala Bidang Teknik';
        $ttd     = $s['kabid_ttd']     ?? '';

        return [
            'nama'    => $nama,
            'nip'     => $nip,
            'jabatan' => $jabatan,
            'ttd_path'=> $ttd,
            'ttd_url' => $ttd ? \App\Helpers\ImageHelper::url($ttd) : null,
            'ttd_base64' => self::ttdBase64($ttd),
        ];
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
