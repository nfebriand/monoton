<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EvidenFoto extends Model
{
    protected $fillable = ['eviden_id','path','keterangan','urutan'];

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->path, 'http')) return $this->path;
        $path = ltrim($this->path, '/');
        if (!str_starts_with($path, 'uploads/')) {
            $path = 'uploads/' . $path;
        }
        return asset($path);
    }
}
