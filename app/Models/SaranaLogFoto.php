<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaranaLogFoto extends Model
{
    protected $table = 'sarana_log_fotos';
    protected $fillable = ['sarana_log_id','path','thumb_path','keterangan','urutan'];

    public function log() { return $this->belongsTo(SaranaLog::class, 'sarana_log_id'); }

    public function getUrlAttribute(): ?string { return \App\Helpers\ImageHelper::url($this->path); }
    public function getThumbUrlAttribute(): ?string { return \App\Helpers\ImageHelper::thumbnailUrl($this->path) ?? $this->url; }
}
