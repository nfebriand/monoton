<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Aset extends Model
{
    protected $fillable = [
        'kode_aset','nama','aset_kategori_id','lokasi','merk','tipe_model','no_seri',
        'tanggal_perolehan','harga_perolehan','kondisi','status','keterangan',
        'interval_maintenance_hari','maintenance_terakhir',
    ];
    protected $casts = [
        'tanggal_perolehan'    => 'date',
        'maintenance_terakhir' => 'date',
        'harga_perolehan'      => 'decimal:2',
    ];

    public const KONDISI_LABEL = [
        'baik'         => 'Baik',
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_berat'  => 'Rusak Berat',
        'hilang'       => 'Hilang',
    ];
    public const KONDISI_COLOR = [
        'baik'         => '#10ac84',
        'rusak_ringan' => '#ff9f43',
        'rusak_berat'  => '#ee5a24',
        'hilang'       => '#636e72',
    ];

    public function kategori() { return $this->belongsTo(AsetKategori::class,'aset_kategori_id'); }
    public function fotos() { return $this->hasMany(AsetFoto::class)->orderBy('urutan'); }
    public function maintenanceLogs() { return $this->hasMany(MaintenanceLog::class)->orderByDesc('tanggal'); }

    public function getKondisiLabelAttribute(): string
    { return self::KONDISI_LABEL[$this->kondisi] ?? $this->kondisi; }

    public function getIntervalEfektifAttribute(): ?int
    { return $this->interval_maintenance_hari ?? $this->kategori?->interval_maintenance_hari; }

    public function getJatuhTempoMaintenanceAttribute(): ?Carbon
    {
        $interval = $this->interval_efektif;
        if (!$interval || !$this->maintenance_terakhir) return null;
        return $this->maintenance_terakhir->copy()->addDays($interval);
    }

    public function getStatusMaintenanceAttribute(): string
    {
        $jt = $this->jatuh_tempo_maintenance;
        if (!$jt) return 'belum_jadwal';
        $hari = now()->startOfDay()->diffInDays($jt, false);
        if ($hari < 0)   return 'terlambat';
        if ($hari <= 7)  return 'jatuh_tempo';
        if ($hari <= 30) return 'mendekati';
        return 'aman';
    }

    public function getStatusMaintenanceLabelAttribute(): string
    {
        return [
            'belum_jadwal' => 'Belum Terjadwal',
            'aman'         => 'Aman',
            'mendekati'    => 'Mendekati Jadwal',
            'jatuh_tempo'  => 'Jatuh Tempo',
            'terlambat'    => 'Terlambat',
        ][$this->status_maintenance] ?? '-';
    }

    public function getStatusMaintenanceColorAttribute(): string
    {
        return [
            'belum_jadwal' => '#888',
            'aman'         => '#10ac84',
            'mendekati'    => '#ff9f43',
            'jatuh_tempo'  => '#e67e22',
            'terlambat'    => '#ee5a24',
        ][$this->status_maintenance] ?? '#888';
    }

    public static function generateKode(): string
    {
        $last = self::orderByDesc('id')->first();
        $next = $last ? ((int)substr($last->kode_aset, 4)) + 1 : 1;
        return 'AST-'.str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
