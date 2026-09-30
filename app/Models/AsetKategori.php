<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AsetKategori extends Model
{
    protected $fillable = ['nama','interval_maintenance_hari'];
    public function asets() { return $this->hasMany(Aset::class); }
}
