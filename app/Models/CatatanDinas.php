<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catatan Dinas: narasi bebas operator tentang kejadian / kondisi / situasi selama dinasnya,
 * dilengkapi banyak foto berketerangan.
 */
class CatatanDinas extends Model
{
    protected $table = 'catatan_dinas';

    protected $fillable = [
        'user_id','divisi','lokasi_dinas','lokasi','tanggal','jam_mulai','jam_selesai','kategori','judul','narasi',
    ];

    protected $casts = ['tanggal' => 'date'];

    public const KATEGORI = [
        'biasa'    => 'Biasa',
        'gangguan' => 'Gangguan',
    ];

    public const KATEGORI_WARNA = [
        'biasa'    => '#2980b9',
        'gangguan' => '#c0392b',
    ];

    public function user()  { return $this->belongsTo(User::class); }
    public function fotos() { return $this->hasMany(CatatanDinasFoto::class, 'catatan_dinas_id')->orderBy('urutan')->orderBy('id'); }

    public function getKategoriLabelAttribute(): string
    {
        return self::KATEGORI[$this->kategori] ?? ucfirst((string) $this->kategori);
    }

    public function getKategoriWarnaAttribute(): string
    {
        return self::KATEGORI_WARNA[$this->kategori] ?? '#7f8c8d';
    }

    /** Rentang jam "08:00 - 16:00" (kosong bila tidak diisi). */
    public function getRentangJamAttribute(): string
    {
        $m = $this->jam_mulai ? substr($this->jam_mulai, 0, 5) : null;
        $s = $this->jam_selesai ? substr($this->jam_selesai, 0, 5) : null;
        if ($m && $s) return "$m - $s";
        return $m ?: ($s ?: '');
    }

    private static function norm($v): string
    {
        return mb_strtolower(trim((string) $v));
    }

    /**
     * Catatan yang boleh dilihat (serah terima shift):
     * super admin semua; admin divisi = divisinya + miliknya + lokasi dinasnya;
     * operator = miliknya + catatan operator lain di LOKASI DINAS yang sama.
     */
    public function scopeDapatDiakses($q, $user)
    {
        if ($user->isAdmin()) return $q;
        $lok = self::norm($user->lokasi_dinas);
        return $q->where(function ($w) use ($user, $lok) {
            $w->where('user_id', $user->id);
            if ($user->isAdminDivisi()) $w->orWhere('divisi', $user->divisi);
            if ($lok !== '') $w->orWhereRaw('LOWER(TRIM(lokasi_dinas)) = ?', [$lok]);
        });
    }

    public function dapatDilihat($user): bool
    {
        if ($user->isAdmin() || (int) $user->id === (int) $this->user_id) return true;
        if ($user->isAdminDivisi() && $this->divisi === $user->divisi) return true;
        $lok = self::norm($user->lokasi_dinas);
        return $lok !== '' && self::norm($this->lokasi_dinas) === $lok;
    }

    /** Mengubah/menghapus: super admin, admin divisi yang sama, atau pembuat. */
    public function dapatDiubah($user): bool
    {
        if ($user->isAdmin() || (int) $user->id === (int) $this->user_id) return true;
        return $user->isAdminDivisi() && $this->divisi === $user->divisi;
    }
}
