<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Services\ImageService;

// Import semua model foto
use App\Models\EvidenFoto;
use App\Models\MaintenanceFoto;
use App\Models\StudioLogFoto;
use App\Models\StudioPerangkatFoto;
use App\Models\PemancarFoto;
use App\Models\AsetFoto;

class GenerateThumbnails extends Command
{
    protected $signature   = 'app:generate-thumbnails
                                {--model= : Spesifik model saja (eviden/maintenance/studio_log/studio_perangkat/pemancar/aset)}
                                {--force  : Regenerate meski thumbnail sudah ada}
                                {--dry-run : Hanya hitung tanpa proses}';

    protected $description = 'Generate thumbnail untuk semua foto lama yang belum punya thumbnail';

    // Mapping model => [class, folder, foreign_key]
    private array $models = [
        'eviden'           => [EvidenFoto::class,         'eviden',           'eviden_id'],
        'maintenance'      => [MaintenanceFoto::class,     'maintenance',      'maintenance_log_id'],
        'studio_log'       => [StudioLogFoto::class,       'studio/logbook',   'studio_log_id'],
        'studio_perangkat' => [StudioPerangkatFoto::class, 'studio/perangkat', 'studio_perangkat_id'],
        'pemancar'         => [PemancarFoto::class,        'pemancar',         'pemancar_id'],
        'aset'             => [AsetFoto::class,            'aset',             'aset_id'],
    ];

    public function handle(): int
    {
        $filterModel = $this->option('model');
        $force       = $this->option('force');
        $dryRun      = $this->option('dry-run');

        $this->info("=== Generate Thumbnail Foto Lama ===");
        if ($dryRun) $this->warn("Mode DRY-RUN — tidak ada perubahan");
        if ($force)  $this->warn("Mode FORCE — regenerate semua thumbnail");

        $totalProcessed = 0;
        $totalSkipped   = 0;
        $totalError     = 0;
        $totalSuccess   = 0;

        $modelsToProcess = $filterModel
            ? (isset($this->models[$filterModel]) ? [$filterModel => $this->models[$filterModel]] : [])
            : $this->models;

        if (empty($modelsToProcess)) {
            $this->error("Model tidak dikenal: {$filterModel}");
            $this->line("Pilihan: " . implode(', ', array_keys($this->models)));
            return 1;
        }

        foreach ($modelsToProcess as $name => [$modelClass, $folder, $fk]) {
            $this->line("\n📁 Memproses: <comment>{$name}</comment> ({$modelClass})");

            // Ambil foto yang belum punya thumbnail (atau semua jika --force)
            $query = $modelClass::query();
            if (!$force) {
                $query->whereNull('thumb_path');
            }

            $total = $query->count();
            if ($total === 0) {
                $this->line("   ✅ Semua sudah punya thumbnail — skip");
                continue;
            }

            $this->line("   Ditemukan {$total} foto yang perlu diproses");

            if ($dryRun) {
                $totalProcessed += $total;
                continue;
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $query->chunk(50, function ($fotos) use ($folder, &$totalSuccess, &$totalSkipped, &$totalError, $force, $bar) {
                foreach ($fotos as $foto) {
                    $bar->advance();

                    // Cari path fisik foto utama
                    $absPath = $this->findAbsPath($foto->path);

                    if (!$absPath) {
                        $totalSkipped++;
                        continue; // file tidak ditemukan
                    }

                    // Jika force dan ada thumb lama, hapus dulu
                    if ($force && $foto->thumb_path) {
                        Storage::disk('public')->delete($foto->thumb_path);
                    }

                    try {
                        // Generate thumbnail dari file yang ada
                        $thumbPath = $this->generateThumb($absPath, $folder, basename($foto->path));

                        if ($thumbPath) {
                            $foto->update(['thumb_path' => $thumbPath]);
                            $totalSuccess++;
                        } else {
                            $totalSkipped++;
                        }
                    } catch (\Throwable $e) {
                        $totalError++;
                        $this->newLine();
                        $this->warn("   Error foto ID {$foto->id}: " . $e->getMessage());
                    }
                }
            });

            $bar->finish();
            $this->newLine();
        }

        // Ringkasan
	$this->newLine();
	$this->info("=== Selesai ===");
	$this->line("--------------------------------------");
	$this->line("Berhasil        : {$totalSuccess}");
	$this->line("Skip (no file)  : {$totalSkipped}");
	$this->line("Error           : {$totalError}");
	$this->line("Total diproses  : " . ($dryRun ? $totalProcessed : ($totalSuccess + $totalSkipped + $totalError)));
	$this->line("--------------------------------------");

	return self::SUCCESS;
    }

    /**
     * Generate thumbnail dari file foto yang sudah ada di storage.
     */
    private function generateThumb(string $absPath, string $folder, string $filename): ?string
    {
        $disk      = Storage::disk('public');
        $thumbDir  = $folder . '/thumbs';
        $thumbPath = $thumbDir . '/' . $filename;

        // Pastikan folder thumbs ada
        $disk->makeDirectory($thumbDir);

        // Generate thumbnail dengan Intervention Image
        $thumb = \Intervention\Image\Facades\Image::make($absPath)
            ->orientate()
            ->fit(
                ImageService::THUMB_WIDTH,
                ImageService::THUMB_HEIGHT,
                function ($constraint) {
                    $constraint->upsize();
                }
            );

        $disk->put($thumbPath, $thumb->encode('jpg', ImageService::THUMB_QUALITY));

        return $thumbPath;
    }

    /**
     * Cari path absolut file dari beberapa lokasi (support shared hosting).
     */
    private function findAbsPath(string $path): ?string
    {
        $locations = [
            storage_path('app/public/' . $path),
            public_path('storage/' . $path),
            public_path('uploads/' . $path),
        ];

        foreach ($locations as $loc) {
            if (file_exists($loc)) return $loc;
        }

        return null;
    }
}
