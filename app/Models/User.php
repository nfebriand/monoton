<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const LOKASI_DINAS = ['Gedung Air', 'Sukarame', 'Bakauheni', 'Way Kanan', 'Relay'];

    protected $fillable = [
        'name', 'nip', 'email', 'password',
        'role', 'lokasi_dinas', 'is_active',
    ];

    protected $hidden   = ['password', 'remember_token'];
    protected $casts    = ['is_active' => 'boolean'];

    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isOperator(): bool { return $this->role === 'operator'; }

    public function jadwalShifts()    { return $this->hasMany(JadwalShift::class); }
    public function operasionalLogs() { return $this->hasMany(OperasionalLog::class); }

    public function getShiftAktifHariIni(): ?JadwalShift
    {
        $now = now();
        return JadwalShift::where('user_id', $this->id)
            ->where('tanggal', $now->toDateString())
            ->where('jam_mulai', '<=', $now->format('H:i'))
            ->where('jam_selesai', '>', $now->format('H:i'))
            ->first();
    }
}
