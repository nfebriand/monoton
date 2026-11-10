<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkemaShift extends Model
{
    protected $table = 'skema_shifts';

    protected $fillable = [
        'nama','divisi','lokasi','kode','is_default','is_active','created_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(SkemaShiftItem::class)->orderBy('nomor');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Konversi items ke format array yang dipakai JadwalShift::getShiftData()
     * Format: [1=>['label'=>'Pagi','mulai'=>'04:45','selesai'=>'12:15'], ...]
     */
    public function toShiftArray(): array
    {
        return $this->items->mapWithKeys(fn($item) => [
            $item->nomor => [
                'label'   => $item->label,
                'mulai'   => substr($item->jam_mulai, 0, 5),
                'selesai' => substr($item->jam_selesai, 0, 5),
            ]
        ])->toArray();
    }

    /**
     * Ambil semua skema aktif, dikelompokkan per divisi,
     * dalam format yang siap dipakai JS jadwal blade.
     * Format: ['transmisi'=>['label'=>'...','shifts'=>[...]], ...]
     */
    public static function getAllForJs(): array
    {
        $skemas = self::with('items')->where('is_active', true)->get();
        $result = [];
        foreach ($skemas as $skema) {
            $result[$skema->kode] = [
                'label'  => $skema->nama,
                'shifts' => $skema->toShiftArray(),
            ];
        }
        // Fallback ke konstanta JadwalShift jika DB kosong
        if (empty($result)) {
            $fallback = [
                'transmisi' => ['label'=>'Transmisi',          'shifts'=>JadwalShift::SKEMA_TRANSMISI],
                'studio'    => ['label'=>'Studio',             'shifts'=>JadwalShift::SKEMA_STUDIO],
                'sarana'    => ['label'=>'Sarana & Prasarana', 'shifts'=>JadwalShift::SKEMA_SARANA],
            ];
            return $fallback;
        }
        return $result;
    }

    /**
     * Ambil shift array untuk satu skema berdasarkan kode.
     */
    public static function getShiftsForKode(string $kode): array
    {
        $skema = self::with('items')->where('kode', $kode)->where('is_active', true)->first();
        if ($skema) return $skema->toShiftArray();
        // Fallback ke konstanta
        return JadwalShift::getShiftsForSkema($kode);
    }

    /** Normalisasi nama lokasi untuk pencocokan: "Gedung Air" -> "gedung_air". */
    public static function slugLokasi(?string $lokasi): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', mb_strtolower(trim((string) $lokasi))), '_');
    }

    /**
     * Tentukan kode skema untuk operator berdasarkan divisi + LOKASI DINAS.
     * Urutan: (1) skema aktif divisi itu dengan kolom lokasi = lokasi operator,
     *         (2) skema aktif divisi itu yang kodenya = nama lokasi (mis. gedung_air, bakauheni),
     *         (3) skema umum divisi (kode = divisi, atau skema tanpa lokasi),
     *         (4) konstanta bawaan divisi.
     */
    public static function kodeUntuk(?string $divisi, ?string $lokasi): string
    {
        $divisi = in_array($divisi, ['transmisi', 'studio', 'sarana'], true) ? $divisi : 'transmisi';
        $aktif = self::where('is_active', true)->where('divisi', $divisi)
            ->get(['kode', 'lokasi', 'is_default']);
        $slug = self::slugLokasi($lokasi);

        if ($slug !== '') {
            $m = $aktif->first(fn($s) => $s->lokasi !== null && self::slugLokasi($s->lokasi) === $slug);
            if ($m) return $m->kode;
            $m = $aktif->first(fn($s) => self::slugLokasi($s->kode) === $slug);
            if ($m) return $m->kode;
        }
        $m = $aktif->first(fn($s) => $s->kode === $divisi)
          ?? $aktif->first(fn($s) => empty($s->lokasi) && $s->is_default)
          ?? $aktif->first(fn($s) => empty($s->lokasi));
        return $m ? $m->kode : JadwalShift::getSkemaForDivisi($divisi);
    }
}
