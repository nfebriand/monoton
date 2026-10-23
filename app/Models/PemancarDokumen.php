<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Berkas terkait pemancar: skematik koneksi, schematic, manual book, datasheet, dll. (disimpan privat). */
class PemancarDokumen extends Model
{
    protected $table = 'pemancar_dokumen';

    protected $fillable = ['pemancar_id', 'user_id', 'kategori', 'judul', 'path', 'nama_asli', 'mime', 'ukuran'];

    public const KATEGORI = [
        'skematik_koneksi' => 'Skematik Koneksi',
        'schematic'        => 'Schematic Perangkat',
        'manual_book'      => 'Manual Book',
        'datasheet'        => 'Datasheet',
        'sertifikat'       => 'Sertifikat / Izin',
        'lainnya'          => 'Lainnya',
    ];

    public const EXT = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'zip'];

    public function pemancar() { return $this->belongsTo(Pemancar::class); }
    public function user()     { return $this->belongsTo(User::class); }

    public function getKategoriLabelAttribute(): string
    {
        return self::KATEGORI[$this->kategori] ?? ucfirst($this->kategori);
    }

    public function getIsImageAttribute(): bool
    {
        return in_array(strtolower(pathinfo($this->nama_asli, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    public function getIsPdfAttribute(): bool
    {
        return strtolower(pathinfo($this->nama_asli, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function getUkuranLabelAttribute(): string
    {
        $b = (int) $this->ukuran;
        return $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.').' MB' : number_format(max($b, 1) / 1024, 0, ',', '.').' KB';
    }
}
