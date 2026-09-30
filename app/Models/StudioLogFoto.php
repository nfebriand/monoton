<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudioLogFoto extends Model
{
    protected $table = 'studio_log_fotos';

    protected $fillable = [
        'studio_log_id','path','thumb_path','keterangan','urutan',
    ];

    public function log()
    {
        return $this->belongsTo(StudioLog::class, 'studio_log_id');
    }

    /**
     * URL foto utama — multi-path fallback untuk shared hosting.
     */
    public function getUrlAttribute(): string
    {
        // Cek via symlink storage (hasil php artisan storage:link)
        if (file_exists(public_path('storage/' . $this->path))) {
            return asset('storage/' . $this->path);
        }
        // Fallback: public/uploads (shared hosting tanpa symlink)
        if (file_exists(public_path('uploads/' . $this->path))) {
            return asset('uploads/' . $this->path);
        }
        // Default: storage (akan 404 jika symlink belum ada)
        return asset('storage/' . $this->path);
    }

    /**
     * URL thumbnail — fallback ke foto utama jika thumb tidak ada.
     */
    public function getThumbUrlAttribute(): string
    {
        if ($this->thumb_path) {
            if (file_exists(public_path('storage/' . $this->thumb_path))) {
                return asset('storage/' . $this->thumb_path);
            }
            if (file_exists(public_path('uploads/' . $this->thumb_path))) {
                return asset('uploads/' . $this->thumb_path);
            }
        }
        return $this->url;
    }
}
