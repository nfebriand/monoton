<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MaintenanceFoto extends Model
{
    protected $fillable = ['maintenance_log_id','path','tipe','keterangan'];
    public function maintenanceLog() { return $this->belongsTo(MaintenanceLog::class); }
    public function getUrlAttribute(): string { return asset('uploads/'.$this->path); }
}
