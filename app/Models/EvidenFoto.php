<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EvidenFoto extends Model
{
    protected $fillable = ['eviden_id','path','keterangan','urutan'];

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->path,'http')) return $this->path;
        if (str_starts_with($this->path,'storage/')) return asset($this->path);
        return asset('storage/'.$this->path);
    }
}
