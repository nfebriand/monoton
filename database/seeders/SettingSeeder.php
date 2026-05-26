<?php
namespace Database\Seeders;
use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'satuan_kerja'    => 'LPPL Radio Lampung',
            'kepala_stasiun'  => '',
            'kepala_bidang'   => '',
            'koordinator'     => '',
            'tema_warna'      => '#0a3d62',
            'logo_path'       => '',
            'kredit_pembuat'  => 'Nanda Febriandy',
            'kredit_wa'       => '082182778608',
            'kredit_telegram' => '@nfebriand',
            'kredit_dana'     => '082182778608',
            'app_version'     => '1.0.0',
            'update_log'      => "v1.0.0 - Rilis perdana MonOTOn\n- Manajemen pemancar\n- Log operasional\n- Jadwal shift\n- Laporan PDF\n- Catatan Eviden\n- Pengaturan aplikasi",
        ];
        foreach ($defaults as $key => $value) {
            AppSetting::firstOrCreate(['key'=>$key],['value'=>$value]);
        }
    }
}
