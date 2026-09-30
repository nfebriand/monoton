<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StudioLog extends Model
{
    protected $table = 'studio_logs';

    protected $fillable = [
        'user_id','tanggal','shift','jam_mulai','jam_selesai',
        'alih_tugas','alih_tugas_ket',
        'kondisi_audio','kondisi_audio_ket',
        'kondisi_video','kondisi_video_ket',
        'kondisi_internet','kondisi_internet_ket',
        'kondisi_kelistrikan','kondisi_kelistrikan_ket',
        'kondisi_mcr','kondisi_mcr_ket',
        'kondisi_distribusi','kondisi_distribusi_ket',
        'kondisi_aplikasi','kondisi_aplikasi_ket',
        'kondisi_podcast','kondisi_podcast_ket',
        'kondisi_buka_tutup','kondisi_buka_tutup_ket',
        'catatan_petugas',
    ];

    protected $casts = ['tanggal' => 'date'];

    public const SHIFT = [
        'pagi'   => 'Pagi (04:45 – 12:15)',
        'siang'  => 'Siang (08:30 – 16:00)',
        'sore'   => 'Sore (11:30 – 19:00)',
        'malam'  => 'Malam (16:15 – 23:45)',
        'mcr'    => 'MCR (08:30 – 16:00)',
    ];

    public const SHIFT_JAM = [
        'pagi'  => ['mulai'=>'04:45','selesai'=>'12:15'],
        'siang' => ['mulai'=>'08:30','selesai'=>'16:00'],
        'sore'  => ['mulai'=>'11:30','selesai'=>'19:00'],
        'malam' => ['mulai'=>'16:15','selesai'=>'23:45'],
        'mcr'   => ['mulai'=>'08:30','selesai'=>'16:00'],
    ];

    public const KONDISI_OPTIONS = [
        'baik'      => 'Baik',
        'gangguan'  => 'Gangguan',
        'tidak_ada' => 'Tidak Ada / N/A',
    ];

    public const CHECKLIST_ITEMS = [
        'alih_tugas'        => ['label'=>'Proses Alih Tugas Operator Studio',              'desc'=>null],
        'kondisi_audio'     => ['label'=>'Kondisi Peralatan Audio Studio (Pro 1,2 & 4)',    'desc'=>'Mixer, Microphone, Headphone, Telepon Hybrid, Speaker, Kabel Aux (Jack)'],
        'kondisi_video'     => ['label'=>'Kondisi Peralatan Video Studio (Pro 1,2 & 4)',    'desc'=>'Camera, PC Studio, Monitor, TV, Video Switcher, Kabel HDMI'],
        'kondisi_internet'  => ['label'=>'Kondisi Peralatan Internet Studio (Pro 1,2 & 4)', 'desc'=>'Kabel LAN, Router, Konektor, dll'],
        'kondisi_kelistrikan'=>['label'=>'Kondisi Kelistrikan Studio (Pro 1,2 dan 4)',      'desc'=>'Pemadaman Listrik, Kabel Listrik, UPS, Saklar, Lampu, Stopkontak, AC, dll'],
        'kondisi_mcr'       => ['label'=>'Kondisi Peralatan MCR',                           'desc'=>'Perangkat MCR Pemancar dan Perangkat Logger'],
        'kondisi_distribusi'=> ['label'=>'Kondisi Distribusi Siaran',                       'desc'=>'Ditulis Bila Pemancar Dimatikan Jika Ada Gangguan/Petir'],
        'kondisi_aplikasi'  => ['label'=>'Kondisi Aplikasi Siaran Radio',                   'desc'=>'Aplikasi Logger, Aircast, AirSolution, Vmix'],
        'kondisi_podcast'   => ['label'=>'Kondisi Peralatan Podcast Teknik Studio',         'desc'=>'Komputer, Microphone, Headphone, Splitter, HDMI, dll'],
        'kondisi_buka_tutup'=> ['label'=>'Kondisi Peralatan Saat Buka/Tutup Siaran',       'desc'=>null],
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fotos()
    {
        return $this->hasMany(StudioLogFoto::class, 'studio_log_id')->orderBy('urutan');
    }

    public function getShiftLabelAttribute(): string
    {
        return self::SHIFT[$this->shift] ?? $this->shift;
    }

    public function getDurasiAttribute(): string
    {
        if (!$this->jam_selesai) return '-';
        $mulai   = Carbon::parse($this->jam_mulai);
        $selesai = Carbon::parse($this->jam_selesai);
        if ($selesai->lt($mulai)) $selesai->addDay();
        $menit = $mulai->diffInMinutes($selesai);
        return intdiv($menit, 60) . ' jam ' . ($menit % 60) . ' menit';
    }

    public function getAdaGangguanAttribute(): bool
    {
        foreach (array_keys(self::CHECKLIST_ITEMS) as $key) {
            if ($this->$key === 'gangguan') return true;
        }
        return false;
    }
}
