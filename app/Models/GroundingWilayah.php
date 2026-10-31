<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroundingWilayah extends Model
{
    protected $table = 'grounding_wilayahs';
    protected $fillable = ['nama','lokasi','keterangan','peta_path','is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function titiks() { return $this->hasMany(GroundingTitik::class, 'wilayah_id')->orderBy('kode'); }

    public function getPetaUrlAttribute(): ?string { return \App\Helpers\ImageHelper::url($this->peta_path); }
}
