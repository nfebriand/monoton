<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Eviden extends Model
{
    protected $fillable = [
        'user_id','judul','deskripsi','tanggal',
        'jam_mulai','jam_selesai','lokasi','divisi','supervisi',
    ];

    protected $casts = ['tanggal'=>'date'];

    public function user()    { return $this->belongsTo(User::class); }
    public function fotos()   { return $this->hasMany(EvidenFoto::class)->orderBy('urutan'); }
    public function operators(){ return $this->belongsToMany(User::class,'eviden_operators'); }

    /** Pelaksanaan perawatan rutin yang memakai eviden ini sebagai bukti. */
    public function perawatanLogs() { return $this->hasMany(PerawatanRutinLog::class, 'eviden_id'); }

    /**
     * Eviden yang boleh dilihat pengguna (aturan sama dengan daftar & authorizeEviden):
     * super admin semua; admin divisi = divisinya / miliknya / ia terlibat; operator = miliknya / ia terlibat.
     */
    public function scopeDapatDiakses($q, $user)
    {
        if ($user->isAdmin()) return $q;
        $uid = $user->id;
        return $q->where(function ($w) use ($user, $uid) {
            if ($user->isAdminDivisi()) {
                $w->where('divisi', $user->divisi)->orWhere('user_id', $uid);
            } else {
                $w->where('user_id', $uid);
            }
            $w->orWhereHas('operators', fn($o) => $o->where('users.id', $uid));
        });
    }

    public function getDurasiAttribute(): string
    {
        [$h1,$m1] = explode(':',$this->jam_mulai);
        [$h2,$m2] = explode(':',$this->jam_selesai);
        $menit = ($h2*60+$m2) - ($h1*60+$m1);
        if ($menit < 0) $menit += 1440;
        return floor($menit/60).'j '.($menit%60).'m';
    }
}