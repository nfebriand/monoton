<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    protected $fillable = [
        'aset_id','user_id','tanggal','jenis','uraian_pekerjaan','hasil',
        'biaya','sparepart_terpakai','rencana_maintenance_berikutnya','keterangan',
    ];
    protected $casts = [
        'tanggal'                        => 'date',
        'rencana_maintenance_berikutnya' => 'date',
        'biaya'                          => 'decimal:2',
        'sparepart_terpakai'             => 'array',
    ];

    public const JENIS_LABEL = [
        'preventif' => 'Preventif (Terjadwal)',
        'korektif'  => 'Korektif (Perbaikan)',
        'inspeksi'  => 'Inspeksi',
    ];
    public const HASIL_LABEL = [
        'selesai'  => 'Selesai',
        'sebagian' => 'Selesai Sebagian',
        'tertunda' => 'Tertunda',
    ];

    public function aset()   { return $this->belongsTo(Aset::class); }
    public function user()   { return $this->belongsTo(User::class); }
    public function fotos()  { return $this->hasMany(MaintenanceFoto::class,'maintenance_log_id'); }

    public function getJenisLabelAttribute(): string
    { return self::JENIS_LABEL[$this->jenis] ?? $this->jenis; }
    public function getHasilLabelAttribute(): string
    { return self::HASIL_LABEL[$this->hasil] ?? $this->hasil; }
}
