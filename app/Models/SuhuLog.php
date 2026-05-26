<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuhuLog extends Model
{
    protected $fillable = [
        'pemancar_id', 'user_id', 'suhu_ruangan',
        'suhu_pemancar', 'dicatat_pada', 'keterangan',
    ];

    protected $casts = [
        'dicatat_pada'  => 'datetime',
        'suhu_ruangan'  => 'decimal:1',
        'suhu_pemancar' => 'decimal:1',
    ];

    public function pemancar()
    {
        return $this->belongsTo(Pemancar::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusSuhuAttribute(): string
    {
        if ($this->suhu_ruangan > 30) return 'panas';
        if ($this->suhu_ruangan > 27) return 'hangat';
        return 'normal';
    }
}
