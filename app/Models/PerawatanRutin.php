<?php

namespace App\Models;

use App\Support\Akses;
use App\Support\PerawatanHitung;
use Illuminate\Database\Eloquent\Model;

class PerawatanRutin extends Model
{
    protected $table = 'perawatan_rutin';

    protected $fillable = [
        'objek_tipe', 'pemancar_id', 'genset_unit_id', 'nama_tugas', 'basis',
        'interval_hari', 'interval_hm', 'terakhir_tanggal', 'terakhir_hm',
        'aktif', 'catatan', 'dibuat_oleh',
    ];

    protected $casts = [
        'terakhir_tanggal' => 'date',
        'aktif'            => 'boolean',
    ];

    public const BASIS_LABEL = ['tanggal' => 'Berdasarkan tanggal', 'hm' => 'Berdasarkan jam kerja (HM)', 'keduanya' => 'Tanggal ATAU HM (mana lebih dulu)'];

    /** Contoh tugas siap pakai (mengisi form otomatis). */
    public const PRESET = [
        'pemancar' => [
            ['nama' => 'Pembersihan filter udara / blower',        'basis' => 'tanggal', 'hari' => 90],
            ['nama' => 'Cek & kalibrasi power output / VSWR',      'basis' => 'tanggal', 'hari' => 180],
            ['nama' => 'Pemeriksaan feeder, konektor & grounding', 'basis' => 'tanggal', 'hari' => 365],
            ['nama' => 'Pengukuran frekuensi & modulasi',          'basis' => 'tanggal', 'hari' => 180],
        ],
        'genset' => [
            ['nama' => 'Ganti oli & filter oli',      'basis' => 'keduanya', 'hari' => 180, 'hm' => 250],
            ['nama' => 'Ganti filter solar / udara',  'basis' => 'hm',       'hm' => 500],
            ['nama' => 'Cek aki / baterai & air radiator', 'basis' => 'tanggal', 'hari' => 90],
            ['nama' => 'Test beban berkala',          'basis' => 'tanggal', 'hari' => 30],
            ['nama' => 'Servis besar (overhaul ringan)', 'basis' => 'hm',   'hm' => 1000],
        ],
    ];

    public function pemancar()   { return $this->belongsTo(Pemancar::class); }
    public function gensetUnit() { return $this->belongsTo(GensetUnit::class); }
    public function logs()       { return $this->hasMany(PerawatanRutinLog::class)->orderByDesc('tanggal')->orderByDesc('id'); }

    public function getNamaObjekAttribute(): string
    {
        return $this->objek_tipe === 'pemancar'
            ? ($this->pemancar->nama_stasiun ?? '(pemancar dihapus)')
            : ($this->gensetUnit->nama_unit ?? '(unit dihapus)');
    }

    public function getLokasiObjekAttribute(): ?string
    {
        return $this->objek_tipe === 'pemancar' ? ($this->pemancar->lokasi ?? null) : ($this->gensetUnit->lokasi ?? null);
    }

    /** HM terbesar per unit genset dari log operasional genset. */
    public static function hmMap(): array
    {
        return GensetLog::selectRaw('genset_unit_id, MAX(hm_akhir) AS hm')
            ->whereNotNull('hm_akhir')->groupBy('genset_unit_id')->pluck('hm', 'genset_unit_id')
            ->map(fn($v) => (float) $v)->all();
    }

    public function hmSekarang(?array $map = null): ?float
    {
        if ($this->objek_tipe !== 'genset' || !$this->genset_unit_id) return null;
        $map ??= self::hmMap();
        return $map[$this->genset_unit_id] ?? null;
    }

    /** @return array hasil PerawatanHitung::hitung */
    public function hitung(?array $hmMap = null): array
    {
        return PerawatanHitung::hitung([
            'basis'            => $this->basis,
            'interval_hari'    => $this->interval_hari,
            'interval_hm'      => $this->interval_hm,
            'terakhir_tanggal' => $this->terakhir_tanggal ? $this->terakhir_tanggal->format('Y-m-d') : null,
            'terakhir_hm'      => $this->terakhir_hm !== null ? (float) $this->terakhir_hm : null,
        ], $this->hmSekarang($hmMap), now()->format('Y-m-d'));
    }

    /** Hanya item yang boleh dilihat user (per modul & lokasi dinas untuk operator pemancar). */
    public function scopeBolehDilihat($q, $u)
    {
        return $q->where(function ($w) use ($u) {
            if (Akses::menuTransmisi($u)) {
                $w->orWhere(function ($p) use ($u) {
                    $p->where('objek_tipe', 'pemancar');
                    if ($u->isOperator() && $u->lokasi_dinas) {
                        $p->whereHas('pemancar', fn($x) => $x->where('lokasi', $u->lokasi_dinas));
                    }
                });
            }
            if (Akses::gensetMasuk($u)) {
                $w->orWhere('objek_tipe', 'genset');
            }
            if (!Akses::menuTransmisi($u) && !Akses::gensetMasuk($u)) {
                $w->whereRaw('1 = 0');
            }
        });
    }

    /**
     * Item yang perlu perhatian (terlambat / segera / belum ada acuan) untuk widget dashboard.
     * @return \Illuminate\Support\Collection koleksi [item, hasil]
     */
    public static function perhatian($u, int $limit = 6)
    {
        try {
            $items = self::with(['pemancar', 'gensetUnit'])->where('aktif', true)->bolehDilihat($u)->get();
        } catch (\Throwable $e) {
            return collect();   // tabel belum dimigrasi
        }
        $map = self::hmMap();
        return $items->map(fn($i) => ['item' => $i, 'hasil' => $i->hitung($map)])
            ->filter(fn($x) => in_array($x['hasil']['status'], ['terlambat', 'segera'], true))
            ->sortByDesc(fn($x) => PerawatanHitung::RANK[$x['hasil']['status']] * 100000 - ($x['hasil']['sisa_hari'] ?? ($x['hasil']['sisa_hm'] ?? 0)))
            ->take($limit)->values();
    }
}
