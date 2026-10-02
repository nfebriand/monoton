<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperasionalLog extends Model
{
    protected $fillable = [
        'pemancar_id', 'user_id', 'jadwal_shift_id', 'dicatat_pada',
        'output_final_pa', 'output_driver', 'output_exciter',
        'reflect_final', 'reject_final',
        'vswr_final', 'return_loss_final',
        'suhu_pemancar', 'suhu_ruangan', 'kelembaban',
        'keterangan', 'is_backfill',
    ];

    protected $casts = [
        'dicatat_pada'      => 'datetime',
        'output_final_pa'   => 'decimal:2',
        'output_driver'     => 'decimal:2',
        'output_exciter'    => 'decimal:2',
        'reflect_final'     => 'decimal:2',
        'reject_final'      => 'decimal:2',
        'vswr_final'        => 'decimal:4',
        'return_loss_final' => 'decimal:3',
        'suhu_pemancar'     => 'decimal:1',
        'suhu_ruangan'      => 'decimal:1',
        'kelembaban'        => 'decimal:1',
        'is_backfill'       => 'boolean',
    ];

    public function pemancar() { return $this->belongsTo(Pemancar::class); }
    public function user()     { return $this->belongsTo(User::class); }
    public function jadwalShift() { return $this->belongsTo(JadwalShift::class); }

    public function getVswrStatusAttribute(): array
    {
        if (!$this->vswr_final) return ['label'=>'–','color'=>'secondary','icon'=>'–'];
        return \App\Services\VswrCalculator::getStatus((float)$this->vswr_final);
    }
}
