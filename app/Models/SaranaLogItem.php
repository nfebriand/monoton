<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaranaLogItem extends Model
{
    protected $table = 'sarana_log_items';
    public $timestamps = false;

    protected $fillable = ['sarana_log_id','kunci','genset_unit_id','label','deskripsi','kondisi','keterangan','urutan'];

    public function log() { return $this->belongsTo(SaranaLog::class, 'sarana_log_id'); }

    /** Teks pilihan: butir alih tugas memakai Lancar / Ada Kendala. */
    public function getTeksKondisiAttribute(): string
    {
        if ($this->kondisi === 'kendala') return 'Ada Kendala';
        return $this->kunci === 'alih_tugas' ? 'Lancar' : 'Normal';
    }
}
