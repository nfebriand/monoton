<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroundingTitik extends Model
{
    protected $table = 'grounding_titiks';

    protected $fillable = [
        'wilayah_id','kode','nama','peruntukan','pos_x','pos_y','latitude','longitude',
        'tipe','kedalaman_m','bahan','diameter_mm','panjang_kabel_m','ukuran_kabel_mm2',
        'jumlah_elektroda','tahun_pasang','batas_ohm','status','catatan',
    ];

    public const TIPE = ['Rod (batang)','Plat','Mesh (grid)','Ring','Kombinasi','Lainnya'];
    public const BAHAN = ['Tembaga','BC (bare copper)','Galvanis','Stainless','Lainnya'];
    public const PERUNTUKAN = ['Pemancar','Tower','Panel listrik','Genset','Penangkal petir','Studio','Gedung','Lainnya'];
    public const STATUS = ['aktif' => 'Aktif', 'rusak' => 'Rusak', 'dibongkar' => 'Dibongkar'];

    public function wilayah() { return $this->belongsTo(GroundingWilayah::class, 'wilayah_id'); }
    public function fotos()   { return $this->hasMany(GroundingTitikFoto::class, 'titik_id')->orderBy('urutan')->orderBy('id'); }
    public function ukurs()   { return $this->hasMany(GroundingUkur::class, 'titik_id')->orderByDesc('tanggal')->orderByDesc('id'); }
    public function ukurTerakhir() { return $this->hasOne(GroundingUkur::class, 'titik_id')->ofMany(['tanggal' => 'max', 'id' => 'max']); }

    /** baik | waspada | melebihi | belum (belum pernah diukur) */
    public function getStatusUkurAttribute(): string
    {
        $u = $this->relationLoaded('ukurTerakhir') ? $this->ukurTerakhir : $this->ukurTerakhir()->first();
        return $u ? GroundingUkur::hitungStatus((float) $u->nilai_ohm, (float) $this->batas_ohm) : 'belum';
    }

    public static function warna(string $s): string
    {
        return ['baik' => '#27ae60', 'waspada' => '#f39c12', 'melebihi' => '#e74c3c', 'belum' => '#95a5a6'][$s] ?? '#95a5a6';
    }
}
