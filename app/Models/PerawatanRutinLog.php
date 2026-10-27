<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerawatanRutinLog extends Model
{
    protected $table = 'perawatan_rutin_log';

    protected $fillable = ['perawatan_rutin_id', 'user_id', 'tanggal', 'hm', 'catatan'];

    protected $casts = ['tanggal' => 'date'];

    public function perawatan() { return $this->belongsTo(PerawatanRutin::class, 'perawatan_rutin_id'); }
    public function user()      { return $this->belongsTo(User::class); }
}
