<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudioMaintenance extends Model
{
    protected $table = 'studio_maintenance';

    protected $fillable = [
        'studio_perangkat_id','user_id','tanggal','jenis',
        'uraian_pekerjaan','hasil','biaya',
        'sparepart_terpakai','rencana_maintenance_berikutnya','keterangan',
    ];

    protected $casts = [
        'tanggal'                        => 'date',
        'rencana_maintenance_berikutnya' => 'date',
        'sparepart_terpakai'             => 'array',
    ];

    public const JENIS_LABEL = [
        'preventif' => 'Preventif',
        'korektif'  => 'Korektif',
        'inspeksi'  => 'Inspeksi',
    ];

    public const HASIL_LABEL = [
        'selesai'  => 'Selesai',
        'sebagian' => 'Sebagian',
        'tertunda' => 'Tertunda',
    ];

    public function perangkat()
    {
        return $this->belongsTo(StudioPerangkat::class, 'studio_perangkat_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getJenisLabelAttribute(): string
    {
        return self::JENIS_LABEL[$this->jenis] ?? $this->jenis;
    }

    public function getHasilLabelAttribute(): string
    {
        return self::HASIL_LABEL[$this->hasil] ?? $this->hasil;
    }
}
