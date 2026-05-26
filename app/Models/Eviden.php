<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Eviden extends Model
{
    protected $fillable = [
        'user_id','judul','deskripsi','tanggal',
        'jam_mulai','jam_selesai','lokasi','supervisi',
    ];

    protected $casts = ['tanggal'=>'date'];

    public function user()    { return $this->belongsTo(User::class); }
    public function fotos()   { return $this->hasMany(EvidenFoto::class)->orderBy('urutan'); }
    public function operators(){ return $this->belongsToMany(User::class,'eviden_operators'); }

    public function getDurasiAttribute(): string
    {
        [$h1,$m1] = explode(':',$this->jam_mulai);
        [$h2,$m2] = explode(':',$this->jam_selesai);
        $menit = ($h2*60+$m2) - ($h1*60+$m1);
        if ($menit < 0) $menit += 1440;
        return floor($menit/60).'j '.($menit%60).'m';
    }
}
