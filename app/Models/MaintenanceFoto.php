<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MaintenanceFoto extends Model
{
    protected $fillable = ['maintenance_log_id','path','thumb_path','tipe','keterangan'];
    public function maintenanceLog() { return $this->belongsTo(MaintenanceLog::class); }

    public function getUrlAttribute(): string
    {
        return \App\Helpers\ImageHelper::url($this->path);
    }

    public function getThumbUrlAttribute(): string
    {
        return \App\Helpers\ImageHelper::thumbnailUrl($this->path) ?? $this->url;
    }
}
