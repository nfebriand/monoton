<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StudioPerangkat extends Model
{
    protected $table = 'studio_perangkat';

    protected $fillable = [
        'nama','lokasi','kode_inventaris','kategori','merk','tipe',
        'no_seri','tahun_pengadaan','kondisi','status',
        'maintenance_terakhir','interval_maintenance_hari','keterangan',
    ];

    protected $casts = ['maintenance_terakhir' => 'date'];

    // Lokasi studio yang tersedia
    public const LOKASI = [
        'Pahoman'   => 'Studio Pahoman',
        'Way Kanan' => 'Studio Way Kanan',
    ];

    public const KONDISI_LABEL = [
        'baik'         => 'Baik',
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_berat'  => 'Rusak Berat',
    ];

    public const KONDISI_COLOR = [
        'baik'         => '#10ac84',
        'rusak_ringan' => '#ff9f43',
        'rusak_berat'  => '#ee5a24',
    ];

    public const STATUS_LABEL = [
        'aktif'       => 'Aktif',
        'tidak_aktif' => 'Tidak Aktif',
        'disposal'    => 'Disposal',
    ];

    public const KATEGORI_LABEL = [
        'audio_mixer' => 'Audio Mixer',
        'mikrofon'    => 'Mikrofon',
        'komputer'    => 'Komputer',
        'perekam'     => 'Perekam / Recorder',
        'monitor'     => 'Monitor / Speaker',
        'headphone'   => 'Headphone',
        'kabel'       => 'Kabel & Konektor',
        'penyiaran'   => 'Perangkat Penyiaran',
        'lainnya'     => 'Lainnya',
    ];

    public function maintenances()
    {
        return $this->hasMany(StudioMaintenance::class, 'studio_perangkat_id')->orderByDesc('tanggal');
    }

    public function fotos()
    {
        return $this->hasMany(StudioPerangkatFoto::class, 'studio_perangkat_id')->orderBy('urutan');
    }

    public function getKondisiLabelAttribute(): string
    {
        return self::KONDISI_LABEL[$this->kondisi] ?? $this->kondisi;
    }

    public function getLokasiLabelAttribute(): string
    {
        return self::LOKASI[$this->lokasi] ?? ($this->lokasi ?? '-');
    }

    public function getJatuhTempoMaintenanceAttribute(): ?Carbon
    {
        if (!$this->maintenance_terakhir || !$this->interval_maintenance_hari) return null;
        return $this->maintenance_terakhir->copy()->addDays($this->interval_maintenance_hari);
    }

    public function getStatusMaintenanceAttribute(): string
    {
        $jt = $this->jatuh_tempo_maintenance;
        if (!$jt) return 'belum_jadwal';
        $hari = now()->startOfDay()->diffInDays($jt, false);
        if ($hari < 0)  return 'terlambat';
        if ($hari <= 7) return 'jatuh_tempo';
        return 'aman';
    }

    public function getStatusMaintenanceLabelAttribute(): string
    {
        return [
            'belum_jadwal' => 'Belum Terjadwal',
            'aman'         => 'Aman',
            'jatuh_tempo'  => 'Jatuh Tempo',
            'terlambat'    => 'Terlambat',
        ][$this->status_maintenance] ?? '-';
    }

    public function getStatusMaintenanceColorAttribute(): string
    {
        return [
            'belum_jadwal' => '#888',
            'aman'         => '#10ac84',
            'jatuh_tempo'  => '#e67e22',
            'terlambat'    => '#ee5a24',
        ][$this->status_maintenance] ?? '#888';
    }
}
