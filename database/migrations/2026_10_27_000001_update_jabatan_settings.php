<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $jabatan = [
        'koordinator_transmisi_jabatan' => 'Pengelola Transmisi dan Distribusi',
        'koordinator_studio_jabatan'    => 'Pengelola Teknik Studio & Media Baru',
        'koordinator_sarana_jabatan'    => 'Pengelola Teknik Sarana & Prasarana',
        'kabid_jabatan'                 => 'Ketua Tim Kerja Teknologi & Media Baru',
        'kepala_stasiun_jabatan'        => 'Kepala LPP RRI Bandar Lampung',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('app_settings')) return;
        foreach ($this->jabatan as $key => $val) {
            $ada = DB::table('app_settings')->where('key', $key)->exists();
            if ($ada) {
                DB::table('app_settings')->where('key', $key)->update(['value' => $val]);
            } else {
                $row = ['key' => $key, 'value' => $val];
                if (Schema::hasColumn('app_settings', 'created_at')) { $row['created_at'] = now(); $row['updated_at'] = now(); }
                DB::table('app_settings')->insert($row);
            }
        }
        if (class_exists(\App\Models\AppSetting::class) && method_exists(\App\Models\AppSetting::class, 'flush')) {
            \App\Models\AppSetting::flush();
        }
        try { \Illuminate\Support\Facades\Cache::flush(); } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        // tidak dikembalikan (nilai lama tidak disimpan)
    }
};
