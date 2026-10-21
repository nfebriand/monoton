<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackup;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'monoton:backup-db {--keep=14 : Jumlah backup otomatis terbaru yang disimpan}';
    protected $description = 'Backup database ke storage/app/backups/db (.sql.gz) dan hapus yang lama';

    public function handle(): int
    {
        try {
            $name = DatabaseBackup::backup();
        } catch (\Throwable $e) {
            $this->error('Backup gagal: '.$e->getMessage());
            return self::FAILURE;
        }
        $del = DatabaseBackup::prune((int) $this->option('keep'));
        $this->info("Backup dibuat: {$name}".($del ? " ({$del} backup lama dihapus)" : ''));
        return self::SUCCESS;
    }
}
