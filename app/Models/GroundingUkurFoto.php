<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroundingUkurFoto extends Model
{
    protected $table = 'grounding_ukur_fotos';
    protected $fillable = ['ukur_id','path','thumb_path','keterangan','urutan'];

    public function getUrlAttribute(): ?string { return \App\Helpers\ImageHelper::url($this->path); }
    public function getThumbUrlAttribute(): ?string { return \App\Helpers\ImageHelper::thumbnailUrl($this->path) ?? $this->url; }
}
