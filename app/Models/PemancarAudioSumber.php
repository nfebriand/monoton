<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemancarAudioSumber extends Model
{
    protected $table = 'pemancar_audio_sumber';

    protected $fillable = ['pemancar_id', 'urutan', 'peran', 'sumber', 'konektor', 'merk', 'tipe', 'stl_frekuensi', 'catatan'];

    protected $casts = ['stl_frekuensi' => 'decimal:3'];

    public const PERAN = ['Utama', 'Cadangan'];

    public function pemancar() { return $this->belongsTo(Pemancar::class); }
}
