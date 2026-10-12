<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PemancarFoto extends Model
{
    protected $fillable = ['pemancar_id','path','thumb_path','keterangan','urutan'];

    public function pemancar()
    {
        return $this->belongsTo(Pemancar::class);
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
