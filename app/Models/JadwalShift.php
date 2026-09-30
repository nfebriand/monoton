<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class JadwalShift extends Model
{
    protected $fillable = [
        'user_id','tanggal','shift','skema','jam_mulai','jam_selesai','catatan',
    ];

    protected $casts = ['tanggal' => 'date'];

    // ── Skema Shift per Divisi ──────────────────────────────────────────

    /**
     * Skema Transmisi (default/lama — berdasarkan lokasi)
     * Shift 1/2/3 dengan jam berbeda per lokasi
     */
    public const SKEMA_TRANSMISI = [
        1 => ['label'=>'Shift 1','mulai'=>'06:00','selesai'=>'14:00'],
        2 => ['label'=>'Shift 2','mulai'=>'14:00','selesai'=>'22:00'],
        3 => ['label'=>'Shift 3','mulai'=>'22:00','selesai'=>'06:00'],
    ];

    /**
     * Skema Divisi Studio
     */
    public const SKEMA_STUDIO = [
        1 => ['label'=>'Pagi',  'mulai'=>'04:45','selesai'=>'12:15'],
        2 => ['label'=>'Siang', 'mulai'=>'08:30','selesai'=>'16:00'],
        3 => ['label'=>'Sore',  'mulai'=>'11:30','selesai'=>'19:00'],
        4 => ['label'=>'Malam', 'mulai'=>'16:15','selesai'=>'23:45'],
        5 => ['label'=>'MCR',   'mulai'=>'08:30','selesai'=>'16:00'],
    ];

    /**
     * Skema Divisi Sarana & Prasarana
     */
    public const SKEMA_SARANA = [
        1 => ['label'=>'Shift 1','mulai'=>'03:00','selesai'=>'10:30'],
        2 => ['label'=>'Shift 2','mulai'=>'08:30','selesai'=>'16:00'],
        3 => ['label'=>'Shift 3','mulai'=>'16:00','selesai'=>'23:30'],
    ];

    /**
     * Semua skema yang tersedia — untuk referensi UI
     */
    public const ALL_SKEMA = [
        'transmisi' => self::SKEMA_TRANSMISI,
        'studio'    => self::SKEMA_STUDIO,
        'sarana'    => self::SKEMA_SARANA,
    ];

    // ── Helper Methods ──────────────────────────────────────────────────

    /**
     * Ambil skema berdasarkan divisi user.
     * Fallback ke transmisi jika divisi tidak dikenal.
     */
    public static function getSkemaForDivisi(string $divisi): string
    {
        return match($divisi) {
            'studio' => 'studio',
            'sarana' => 'sarana',
            default  => 'transmisi',
        };
    }

    /**
     * Backward compat: dulu pakai lokasi_dinas, sekarang pakai divisi.
     * Tetap support lokasi untuk transmisi.
     */
    public static function getSkemaForLokasi(string $lokasi = ''): string
    {
        return 'transmisi';
    }

    /**
     * Ambil data shift (label, jam_mulai, jam_selesai) berdasarkan skema & nomor shift.
     */
    public static function getShiftData(string $skema, int $shift): ?array
    {
        $data = match($skema) {
            'studio' => self::SKEMA_STUDIO,
            'sarana' => self::SKEMA_SARANA,
            default  => self::SKEMA_TRANSMISI,
        };
        return $data[$shift] ?? null;
    }

    /**
     * Ambil semua shift untuk skema tertentu.
     */
    public static function getShiftsForSkema(string $skema): array
    {
        return match($skema) {
            'studio' => self::SKEMA_STUDIO,
            'sarana' => self::SKEMA_SARANA,
            default  => self::SKEMA_TRANSMISI,
        };
    }

    /**
     * Label shift lengkap (misal: "Pagi (04:45–12:15)")
     */
    public function getShiftLabelAttribute(): string
    {
        $data = self::getShiftData($this->skema ?? 'transmisi', (int)$this->shift);
        if (!$data) return "Shift {$this->shift}";
        return "{$data['label']} ({$data['mulai']}–{$data['selesai']})";
    }

    /**
     * Warna badge shift untuk UI
     */
    public function getShiftColorAttribute(): string
    {
        // Studio
        $studioColors = [1=>'#f39c12',2=>'#27ae60',3=>'#8e44ad',4=>'#2c3e50',5=>'#2980b9'];
        // Sarana & Transmisi
        $defaultColors = [1=>'#2980b9',2=>'#27ae60',3=>'#2c3e50'];

        $skema = $this->skema ?? 'transmisi';
        $shift = (int)$this->shift;

        if ($skema === 'studio') return $studioColors[$shift] ?? '#888';
        return $defaultColors[$shift] ?? '#888';
    }

    // ── Relasi ──────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
