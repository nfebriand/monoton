<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatatanDinasFoto extends Model
{
    protected $table = 'catatan_dinas_fotos';

    protected $fillable = ['catatan_dinas_id','path','thumb_path','keterangan','urutan'];

    public function catatan() { return $this->belongsTo(CatatanDinas::class, 'catatan_dinas_id'); }

    public function getUrlAttribute(): ?string
    {
        return \App\Helpers\ImageHelper::url($this->path);
    }

    public function getThumbUrlAttribute(): ?string
    {
        return \App\Helpers\ImageHelper::thumbnailUrl($this->path) ?? $this->url;
    }
}
