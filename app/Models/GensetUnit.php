<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GensetUnit extends Model
{
    protected $fillable = [
        'nama_unit','lokasi','merk','tipe',
        'kapasitas_kva','kapasitas_tangki_liter','tahun_pembuatan','is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function logs()
    {
        return $this->hasMany(GensetLog::class);
    }

    /** Log terakhir untuk unit ini */
    public function logTerakhir()
    {
        return $this->hasOne(GensetLog::class)->latestOfMany('tanggal');
    }
}
