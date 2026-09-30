<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AsetFoto extends Model
{
    protected $fillable = ['aset_id','path','thumb_path','keterangan','urutan'];
    public function aset() { return $this->belongsTo(Aset::class); }

    public function getUrlAttribute(): string
    {
        return \App\Helpers\ImageHelper::url($this->path);
    }

    public function getThumbUrlAttribute(): string
    {
        return \App\Helpers\ImageHelper::thumbnailUrl($this->path) ?? $this->url;
    }
}
