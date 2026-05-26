<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PemancarFoto extends Model
{
    protected $fillable = ['pemancar_id', 'path', 'keterangan', 'urutan'];

    public function pemancar()
    {
        return $this->belongsTo(Pemancar::class);
    }

    /**
     * URL foto - handle berbagai format path
     */
    public function getUrlAttribute(): string
    {
        // Jika path sudah full URL
        if (str_starts_with($this->path, 'http')) {
            return $this->path;
        }
        // Jika path sudah include 'storage/'
        if (str_starts_with($this->path, 'storage/')) {
            return asset($this->path);
        }
        // Default: dari disk public
        return asset('storage/' . $this->path);
    }
}
