<?php

namespace App\Console\Commands;

use App\Support\Preflight as Cek;
use Illuminate\Console\Command;

class Preflight extends Command
{
    protected $signature = 'monoton:preflight {--views-only : Hanya periksa file view (tanpa database)}';
    protected $description = 'Periksa kesiapan deploy: migrasi tertunda, file view hilang/rusak encoding, fillable vs kolom, route';

    public function handle(): int
    {
        $temuan = Cek::views(base_path());
        if (!$this->option('views-only')) {
            $temuan = array_merge($temuan, Cek::migrasiTertunda(), Cek::fillable(), Cek::routes());
        }

        $err = 0; $warn = 0;
        foreach ($temuan as $t) {
            if ($t['level'] === 'error') { $err++; $this->error('  [X] '.$t['pesan']); }
            else { $warn++; $this->warn('  [!] '.$t['pesan']); }
        }
        if (!$temuan) { $this->info('Preflight OK: tidak ada masalah ditemukan.'); return self::SUCCESS; }
        $this->line("Ringkasan: $err error, $warn peringatan.");
        return $err > 0 ? self::FAILURE : self::SUCCESS;
    }
}
