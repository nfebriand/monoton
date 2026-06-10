<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class JadwalShift extends Model
{
    protected $fillable = [
        'user_id','tanggal','shift','jam_mulai','jam_selesai',
        'skema','catatan',
    ];

    protected $casts = ['tanggal' => 'date'];

    /**
     * Skema shift per lokasi dinas
     * skema: 'gedung_air' | 'sukarame' | 'bakauheni' | 'default'
     */
    public const SKEMA = [

        // Gedung Air — 3 shift 24 jam
        'gedung_air' => [
            'label' => 'Gedung Air (3 Shift 24 Jam)',
            'shifts' => [
                1 => ['label' => 'Shift 1', 'mulai' => '00:15', 'selesai' => '07:45'],
                2 => ['label' => 'Shift 2', 'mulai' => '07:45', 'selesai' => '15:45'],
                3 => ['label' => 'Shift 3', 'mulai' => '15:45', 'selesai' => '23:45'],
            ],
        ],

        // Sukarame — 3 shift (pagi, malam, maintenance)
        'sukarame' => [
            'label' => 'Sukarame (Pagi/Malam/Maintenance)',
            'shifts' => [
                1 => ['label' => 'Pagi',        'mulai' => '09:00', 'selesai' => '16:30'],
                2 => ['label' => 'Malam',       'mulai' => '15:30', 'selesai' => '23:00'],
                3 => ['label' => 'Maintenance', 'mulai' => '08:30', 'selesai' => '16:00'],
            ],
        ],

        // Bakauheni — 2 shift
        'bakauheni' => [
            'label' => 'Bakauheni (Pagi/Sore)',
            'shifts' => [
                1 => ['label' => 'Pagi', 'mulai' => '04:30', 'selesai' => '12:00'],
                2 => ['label' => 'Sore', 'mulai' => '16:15', 'selesai' => '23:45'],
            ],
        ],

        // Default / Way Kanan / Relay (sama dengan Gedung Air)
        'default' => [
            'label' => 'Default (3 Shift 24 Jam)',
            'shifts' => [
                1 => ['label' => 'Shift 1', 'mulai' => '00:15', 'selesai' => '07:45'],
                2 => ['label' => 'Shift 2', 'mulai' => '07:45', 'selesai' => '15:45'],
                3 => ['label' => 'Shift 3', 'mulai' => '15:45', 'selesai' => '23:45'],
            ],
        ],
    ];

    // Alias SHIFTS untuk kompatibilitas kode lama
    public const SHIFTS = self::SKEMA['gedung_air']['shifts'];

    /**
     * Mapping lokasi dinas → skema
     */
    public const LOKASI_SKEMA = [
        'Gedung Air'        => 'gedung_air',
        'Sukarame'          => 'sukarame',
        'Bakauheni'         => 'bakauheni',
        'Way Kanan'         => 'default',
        'Relay'             => 'default',
        'Simpang Pematang'  => 'default',
    ];

    /**
     * Ambil skema berdasarkan lokasi dinas
     */
    public static function getSkemaForLokasi(string $lokasi): string
    {
        return self::LOKASI_SKEMA[$lokasi] ?? 'default';
    }

    /**
     * Ambil definisi shift untuk skema tertentu
     */
    public static function getShiftsForSkema(string $skema): array
    {
        return self::SKEMA[$skema]['shifts'] ?? self::SKEMA['default']['shifts'];
    }

    /**
     * Ambil data shift tertentu
     */
    public static function getShiftData(string $skema, int $shift): ?array
    {
        return self::SKEMA[$skema]['shifts'][$shift] ?? null;
    }

    /**
     * Apakah waktu sekarang masuk toleransi shift?
     * Toleransi 30 menit sebelum mulai dan 30 menit setelah selesai
     */
    public function isAktifSekarang(int $toleransiMenit = 30): bool
    {
        $now      = Carbon::now();
        $tanggal  = $this->tanggal->format('Y-m-d');
        $mulai    = Carbon::parse("{$tanggal} {$this->jam_mulai}")->subMinutes($toleransiMenit);
        $selesai  = Carbon::parse("{$tanggal} {$this->jam_selesai}")->addMinutes($toleransiMenit);

        // Handle shift melewati tengah malam (misal shift 1: 00:15–07:45)
        // atau shift malam yang mulai sore dan selesai dini hari
        if ($selesai->lessThan($mulai)) {
            $selesai->addDay();
        }

        return $now->between($mulai, $selesai);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function operasionalLogs()
    {
        return $this->hasMany(OperasionalLog::class);
    }

    /** Label shift (Shift 1 / Pagi / dll) */
    public function getShiftLabelAttribute(): string
    {
        $skema = $this->skema ?? 'default';
        return self::SKEMA[$skema]['shifts'][$this->shift]['label'] ?? 'Shift '.$this->shift;
    }
}
