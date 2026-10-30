<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Logbook Laporan Harian Teknik Sarana & Prasarana. */
class SaranaLog extends Model
{
    protected $table = 'sarana_logs';

    protected $fillable = ['user_id','tanggal','shift','jam_mulai','jam_selesai','catatan_petugas'];

    protected $casts = ['tanggal' => 'date'];

    /** 7 butir tetap (genset mengikuti master data unit, ditambahkan dinamis). 'lancar' = pilihan Lancar/Ada Kendala. */
    public const ITEM_TETAP = [
        'alih_tugas'    => ['label' => 'Proses Alih Tugas Operator Dinas',            'desc' => null, 'lancar' => true],
        'listrik_pln'   => ['label' => 'Kondisi Kelistrikan PLN',                     'desc' => 'Padam Listrik, Konsleting, Perbaikan, dll'],
        'listrik_kantor'=> ['label' => 'Kondisi Kelistrikan Kantor',                  'desc' => 'Kelistrikan, Lampu, Panel dan Peralatan Penunjang'],
        'telepon_hybrid'=> ['label' => 'Kondisi Telepon Hybrid RRI Bandarlampung',    'desc' => 'Kantor, Ruang Studio'],
        'internet'      => ['label' => 'Kondisi Internet RRI Bandarlampung',          'desc' => 'Kantor, Ruang Studio'],
        'ups'           => ['label' => 'Kondisi UPS',                                 'desc' => 'Ruang TMB, Pro 4 & Pemberitaan'],
        'ac'            => ['label' => 'Kondisi AC Ruang Studio dan Kantor',          'desc' => 'Kantor LPP RRI Bandarlampung'],
    ];

    public function user()  { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(SaranaLogItem::class)->orderBy('urutan')->orderBy('id'); }
    public function fotos() { return $this->hasMany(SaranaLogFoto::class)->orderBy('urutan')->orderBy('id'); }

    public static function shiftList(): array
    {
        return JadwalShift::SKEMA_SARANA;
    }

    public function getShiftLabelAttribute(): string
    {
        $s = JadwalShift::SKEMA_SARANA[(int) $this->shift] ?? null;
        return $s ? $s['label'] : 'Shift '.$this->shift;
    }

    public function getAdaKendalaAttribute(): bool
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        return $items->contains(fn($i) => $i->kondisi === 'kendala');
    }

    /** Shift yang sedang berjalan menurut jam (fallback 1). */
    public static function shiftSekarang(): int
    {
        $now = now()->format('H:i');
        foreach (JadwalShift::SKEMA_SARANA as $no => $s) {
            if ($now >= $s['mulai'] && $now < $s['selesai']) return (int) $no;
        }
        return 1;
    }

    /**
     * Daftar butir untuk form: 7 tetap + SEMUA unit genset aktif di master (jumlahnya mengikuti master).
     * Saat edit, unit yang sudah tercatat tetapi kini nonaktif/terhapus tetap ditampilkan.
     * Return: list [kunci, label, desc, lancar, genset_unit_id]
     */
    public static function daftarButir(?SaranaLog $log = null): array
    {
        $out = [];
        foreach (self::ITEM_TETAP as $k => $it) {
            $out[] = ['kunci' => $k, 'label' => $it['label'], 'desc' => $it['desc'],
                      'lancar' => !empty($it['lancar']), 'genset_unit_id' => null];
        }
        $units = GensetUnit::where('is_active', true)->orderBy('nama_unit')->get()->keyBy('id');
        $adaDiLog = $log ? $log->items->whereNotNull('genset_unit_id')->keyBy('genset_unit_id') : collect();

        foreach ($units as $u) {
            $out[] = ['kunci' => 'genset_'.$u->id, 'label' => self::labelGenset($u), 'desc' => 'Warming up, maintenance, trouble, operasional, dll',
                      'lancar' => false, 'genset_unit_id' => $u->id];
        }
        foreach ($adaDiLog as $uid => $it) {
            if ($units->has($uid)) continue;
            $out[] = ['kunci' => 'genset_'.$uid, 'label' => $it->label, 'desc' => $it->deskripsi,
                      'lancar' => false, 'genset_unit_id' => $uid];
        }
        return $out;
    }

    public static function labelGenset(GensetUnit $u): string
    {
        $kva = $u->kapasitas_kva ? ' ('.rtrim(rtrim(number_format((float) $u->kapasitas_kva, 1, '.', ''), '0'), '.').' kVA)' : '';
        $lok = $u->lokasi ? ' - '.$u->lokasi : '';
        return 'Kondisi Diesel Generator '.$u->nama_unit.$kva.$lok;
    }
}
