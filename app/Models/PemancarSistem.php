<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Detail sistem pemancar: antena, kabel, konektor, sumber audio, audio processor (1:1 dengan Pemancar). */
class PemancarSistem extends Model
{
    protected $table = 'pemancar_sistem';

    protected $fillable = [
        'pemancar_id',
        'antena_merk', 'antena_jenis', 'antena_bay', 'antena_max_power', 'antena_gain', 'antena_tinggi',
        'kabel_jenis', 'kabel_panjang',
        'konektor_exciter_driver', 'konektor_driver_final', 'konektor_final_antena',
        'ap_terpasang', 'ap_merk', 'ap_tipe', 'ap_spek',
        'catatan',
    ];

    protected $casts = [
        'ap_terpasang'     => 'boolean',
        'antena_max_power' => 'decimal:2',
        'antena_gain'      => 'decimal:2',
        'antena_tinggi'    => 'decimal:2',
        'kabel_panjang'    => 'decimal:2',
    ];

    public const AUDIO_SUMBER = ['STL' => 'STL (Studio Transmitter Link)', 'DVB' => 'DVB / Satelit', 'Streaming' => 'Streaming / IP', 'Lainnya' => 'Lainnya'];

    /** Field yang diisi dari form (tanpa pemancar_id). */
    public static function formFields(): array
    {
        return array_values(array_diff((new self)->getFillable(), ['pemancar_id']));
    }

    public function pemancar()
    {
        return $this->belongsTo(Pemancar::class);
    }

    /** Angka tanpa nol di belakang koma: 12.50 -> 12,5 ; 3.000 -> 3 */
    public static function num($v, int $dec = 2): string
    {
        if ($v === null || $v === '') return '';
        return rtrim(rtrim(number_format((float) $v, $dec, ',', '.'), '0'), ',');
    }
}
