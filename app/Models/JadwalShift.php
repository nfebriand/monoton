<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalShift extends Model
{
    protected $fillable = [
        'user_id', 'tanggal', 'shift',
        'jam_mulai', 'jam_selesai', 'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    const SHIFTS = [
        1 => ['label' => 'Shift 1', 'mulai' => '00:15', 'selesai' => '07:45'],
        2 => ['label' => 'Shift 2', 'mulai' => '07:45', 'selesai' => '15:45'],
        3 => ['label' => 'Shift 3', 'mulai' => '15:45', 'selesai' => '23:45'],
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function operasionalLogs()
    {
        return $this->hasMany(OperasionalLog::class);
    }

    public function getShiftLabelAttribute(): string
    {
        return self::SHIFTS[$this->shift]['label'] ?? 'Unknown';
    }

    public function getJamRangeAttribute(): string
    {
        $s = self::SHIFTS[$this->shift] ?? [];
        return isset($s['mulai']) ? "{$s['mulai']} – {$s['selesai']}" : '-';
    }

    /**
     * Dapatkan nomor shift yang sedang aktif saat ini
     */
    public static function getShiftAktifSekarang(): ?int
    {
        $now = now()->format('H:i');
        foreach (self::SHIFTS as $shiftNo => $shift) {
            if ($now >= $shift['mulai'] && $now < $shift['selesai']) {
                return $shiftNo;
            }
        }
        return null;
    }
}
