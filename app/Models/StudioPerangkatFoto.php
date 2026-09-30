<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudioPerangkatFoto extends Model
{
    protected $table = 'studio_perangkat_fotos';

    protected $fillable = ['studio_perangkat_id','path','keterangan','urutan'];

    public function perangkat()
    {
        return $this->belongsTo(StudioPerangkat::class, 'studio_perangkat_id');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}
