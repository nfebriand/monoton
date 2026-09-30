<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AsetFoto extends Model
{
    protected $fillable = ['aset_id','path','keterangan','urutan'];
    public function aset() { return $this->belongsTo(Aset::class); }
    public function getUrlAttribute(): string { return asset('uploads/'.$this->path); }
}
