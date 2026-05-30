<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key','value'];

    public static function get(string $key, $default = null)
    {
        $s = static::where('key',$key)->first();
        return $s ? $s->value : $default;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key'=>$key],['value'=>$value]);
    }

    public static function allKeyed(): array
    {
        return static::all()->pluck('value','key')->toArray();
    }

    /**
     * Tambah entri update log baru dengan nomor versi auto-increment
     * Format: v1.0.0, v1.0.1, v1.1.0 dst
     */
    public static function addUpdateLog(string $catatan, string $tipeIncrement = 'patch'): string
    {
        $currentVersion = static::get('app_version','1.0.0');
        $newVersion     = static::incrementVersion($currentVersion, $tipeIncrement);

        // Simpan versi baru
        static::set('app_version', $newVersion);

        // Tambahkan entri baru ke riwayat
        $existingLog = static::get('update_log','');
        $tanggal     = now()->format('d/m/Y H:i');
        $entry       = "v{$newVersion} [{$tanggal}]\n{$catatan}";
        $newLog      = $entry . ($existingLog ? "\n\n" . $existingLog : '');
        static::set('update_log', $newLog);

        return $newVersion;
    }

    /**
     * Increment versi: patch = x.x.+1, minor = x.+1.0, major = +1.0.0
     */
    public static function incrementVersion(string $version, string $type = 'patch'): string
    {
        $parts = explode('.', ltrim($version,'v'));
        while (count($parts) < 3) $parts[] = '0';
        [$major, $minor, $patch] = array_map('intval', $parts);

        switch ($type) {
            case 'major': $major++; $minor=0; $patch=0; break;
            case 'minor': $minor++; $patch=0; break;
            default:      $patch++; break;
        }
        return "{$major}.{$minor}.{$patch}";
    }

    /**
     * Validasi kredit
     */
    public static function kreditValid(): bool
    {
        $pembuat  = static::get('kredit_pembuat','Nanda Febriandy');
        $wa       = static::get('kredit_wa','082182778608');
        $telegram = static::get('kredit_telegram','@nfebriand');
        return $pembuat === 'Nanda Febriandy'
            && $wa       === '082182778608'
            && $telegram === '@nfebriand';
    }
}
