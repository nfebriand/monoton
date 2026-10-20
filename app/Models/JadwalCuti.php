<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalCuti extends Model
{
    protected $table = 'jadwal_cuti';

    protected $fillable = ['user_id', 'tanggal', 'jenis', 'keterangan'];

    protected $casts = ['tanggal' => 'date'];

    public const JENIS = [
        'cuti'       => 'Cuti',
        'izin'       => 'Izin',
        'sakit'      => 'Sakit',
        'dinas_luar' => 'Dinas Luar',
    ];

    public function getJenisLabelAttribute(): string
    {
        return self::JENIS[$this->jenis] ?? ucfirst((string) $this->jenis);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
