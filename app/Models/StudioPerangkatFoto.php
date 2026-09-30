<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudioPerangkatFoto extends Model
{
    protected $table = 'studio_perangkat_fotos';

    protected $fillable = ['studio_perangkat_id','path','thumb_path','keterangan','urutan'];

    public function perangkat()
    {
        return $this->belongsTo(StudioPerangkat::class, 'studio_perangkat_id');
    }

    public function getUrlAttribute(): string
    {
        return \App\Helpers\ImageHelper::url($this->path);
    }

    public function getThumbUrlAttribute(): string
    {
        return \App\Helpers\ImageHelper::thumbnailUrl($this->path) ?? $this->url;
    }
}
