<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroundingUkur extends Model
{
    protected $table = 'grounding_ukurs';

    protected $fillable = [
        'titik_id','user_id','tanggal','jam','nilai_ohm','batas_ohm','status',
        'kondisi_tanah','alat','metode','keterangan',
    ];

    protected $casts = ['tanggal' => 'date'];

    /** Ambang "waspada" = 80% dari batas. */
    public const AMBANG_WASPADA = 0.8;

    public const KONDISI = ['kering' => 'Kering', 'lembab' => 'Lembab', 'basah' => 'Basah / habis hujan'];
    public const METODE  = ['3 titik (fall of potential)', '2 titik', 'Klem (clamp-on)', 'Lainnya'];
    public const STATUS_LABEL = ['baik' => 'Baik', 'waspada' => 'Waspada', 'melebihi' => 'Melebihi batas', 'belum' => 'Belum diukur'];

    public function titik() { return $this->belongsTo(GroundingTitik::class, 'titik_id'); }
    public function user()  { return $this->belongsTo(User::class); }
    public function fotos() { return $this->hasMany(GroundingUkurFoto::class, 'ukur_id')->orderBy('urutan')->orderBy('id'); }

    public static function hitungStatus(float $nilai, float $batas): string
    {
        if ($nilai > $batas) return 'melebihi';
        if ($batas > 0 && $nilai >= $batas * self::AMBANG_WASPADA) return 'waspada';
        return 'baik';
    }
}
