<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    protected $table = 'lokasis';

    protected $fillable = [
        'nama', 'divisi', 'alamat', 'keterangan', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public const DIVISI_LABEL = [
        'transmisi' => 'Transmisi',
        'studio'    => 'Studio',
        'sarana'    => 'Sarana & Prasarana',
        'umum'      => 'Umum (Lintas Divisi)',
    ];

    /** Ambil semua nama lokasi aktif (untuk dropdown) */
    public static function listAktif(?string $divisi = null): \Illuminate\Support\Collection
    {
        $q = self::where('is_active', true)->orderBy('divisi')->orderBy('nama');
        if ($divisi) $q->whereIn('divisi', [$divisi, 'umum']);
        return $q->get();
    }

    /** Hanya nama (untuk backward compat dengan kode lama yang butuh array/collection string) */
    public static function namaAktif(?string $divisi = null): array
    {
        return self::listAktif($divisi)->pluck('nama')->toArray();
    }

    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }
}
