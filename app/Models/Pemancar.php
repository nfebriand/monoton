<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pemancar extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_stasiun', 'merk', 'tipe_unit', 'tipe_komponen', 'modulasi',
        'lokasi', 'latitude', 'longitude', 'alamat_lokasi',
        'kapasitas_output_final', 'tipe_exciter', 'tipe_driver',
        'frekuensi', 'nomor_izin', 'tanggal_instalasi', 'keterangan', 'is_active',
    ];

    protected $casts = [
        'is_active'              => 'boolean',
        'tanggal_instalasi'      => 'date',
        'kapasitas_output_final' => 'decimal:2',
        'latitude'               => 'decimal:7',
        'longitude'              => 'decimal:7',
        'frekuensi'              => 'decimal:3',
    ];

    public function fotos()          { return $this->hasMany(PemancarFoto::class)->orderBy('urutan'); }
    public function jadwalShifts()   { return $this->hasMany(JadwalShift::class); }
    public function operasionalLogs(){ return $this->hasMany(OperasionalLog::class)->orderByDesc('dicatat_pada'); }

    public function getTipeKomponenLabelAttribute(): string
    {
        return $this->tipe_komponen === 'tabung' ? 'Tabung' : 'Solid State';
    }
}
