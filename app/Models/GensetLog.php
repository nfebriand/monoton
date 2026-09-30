<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class GensetLog extends Model
{
    protected $fillable = [
        'genset_unit_id','user_id','tanggal','jam_mulai','jam_selesai','alasan',
        'hm_awal','hm_akhir','bbm_awal','bbm_isi','bbm_akhir','kondisi','keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public const ALASAN_LABEL = [
        'pln_mati'    => 'PLN Padam',
        'maintenance' => 'Maintenance / Servis',
        'test_rutin'  => 'Test Rutin',
        'lainnya'     => 'Lainnya',
    ];

    public function gensetUnit()
    {
        return $this->belongsTo(GensetUnit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAlasanLabelAttribute(): string
    {
        return self::ALASAN_LABEL[$this->alasan] ?? $this->alasan;
    }

    /** Durasi operasi dari jam_mulai - jam_selesai */
    public function getDurasiAttribute(): ?string
    {
        if (!$this->jam_selesai) return null;
        $mulai   = Carbon::parse($this->jam_mulai);
        $selesai = Carbon::parse($this->jam_selesai);
        if ($selesai->lt($mulai)) $selesai->addDay(); // lewat tengah malam
        $menit = $mulai->diffInMinutes($selesai);
        $jam = intdiv($menit, 60);
        $sisa = $menit % 60;
        return $jam > 0 ? "{$jam} jam {$sisa} menit" : "{$sisa} menit";
    }

    /** Durasi operasi dalam jam (desimal), untuk hitung pemakaian BBM/jam */
    public function getDurasiJamAttribute(): ?float
    {
        if (!$this->jam_selesai) return null;
        $mulai   = Carbon::parse($this->jam_mulai);
        $selesai = Carbon::parse($this->jam_selesai);
        if ($selesai->lt($mulai)) $selesai->addDay();
        return round($mulai->diffInMinutes($selesai) / 60, 2);
    }

    /** Pemakaian BBM = (awal + isi) - akhir */
    public function getPemakaianBbmAttribute(): ?float
    {
        if ($this->bbm_awal === null || $this->bbm_akhir === null) return null;
        $isi = $this->bbm_isi ?? 0;
        return round(($this->bbm_awal + $isi) - $this->bbm_akhir, 2);
    }

    /** Jam operasi dari HM (hour meter) */
    public function getJamOperasiHmAttribute(): ?float
    {
        if ($this->hm_awal === null || $this->hm_akhir === null) return null;
        return round($this->hm_akhir - $this->hm_awal, 1);
    }

    /** Rasio konsumsi BBM per jam operasi */
    public function getRasioBbmPerJamAttribute(): ?float
    {
        $pakai = $this->pemakaian_bbm;
        $jam   = $this->jam_operasi_hm ?? $this->durasi_jam;
        if ($pakai === null || !$jam) return null;
        return round($pakai / $jam, 2);
    }
}
