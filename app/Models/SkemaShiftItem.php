<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkemaShiftItem extends Model
{
    protected $table = 'skema_shift_items';

    protected $fillable = [
        'skema_shift_id','nomor','label','jam_mulai','jam_selesai',
    ];

    public function skema()
    {
        return $this->belongsTo(SkemaShift::class, 'skema_shift_id');
    }

    public function getJamMulaiShortAttribute(): string
    {
        return substr($this->jam_mulai, 0, 5);
    }

    public function getJamSelesaiShortAttribute(): string
    {
        return substr($this->jam_selesai, 0, 5);
    }
}
