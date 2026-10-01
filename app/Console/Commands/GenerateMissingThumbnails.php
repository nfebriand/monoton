<?php

namespace App\Console\Commands;

use App\Models\EvidenFoto;
use App\Models\AsetFoto;
use App\Models\MaintenanceFoto;
use App\Models\StudioLogFoto;
use App\Models\StudioPerangkatFoto;
use App\Models\PemancarFoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class GenerateMissingThumbnails extends Command
{
    /**
     * php artisan fotos:generate-thumbnails
     * php artisan fotos:generate-thumbnails --dry-run
     */
    protected $signature = 'fotos:generate-thumbnails {--dry-run : Hanya tampilkan apa yang akan diproses, tanpa menulis apa pun}';

    protected $description = 'Generate ulang thumbnail untuk foto lama (thumb_path kosong) di semua modul: Eviden, Aset, Maintenance, StudioLog, StudioPerangkat, Pemancar';

    /** @var array<string,class-string> */
    private array $models = [
        'Eviden'          => EvidenFoto::class,
        'Aset'            => AsetFoto::class,
        'Maintenance'     => MaintenanceFoto::class,
        'StudioLog'       => StudioLogFoto::class,
        'StudioPerangkat' => StudioPerangkatFoto::class,
        'Pemancar'        => PemancarFoto::class,
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $disk   = Storage::disk('public');

        $totalOk = 0;
        $totalSkip = 0;
        $totalMissingFile = 0;

        foreach ($this->models as $label => $modelClass) {
            $this->info("=== {$label} ===");

            $rows = $modelClass::whereNull('thumb_path')
                ->orWhere('thumb_path', '')
                ->get();

            if ($rows->isEmpty()) {
                $this->line('  (tidak ada foto yang perlu di-generate)');
                continue;
            }

            foreach ($rows as $foto) {
                if (!$foto->path) {
                    $totalSkip++;
                    continue;
                }

                if (!$disk->exists($foto->path)) {
                    $this->warn("  [HILANG] id={$foto->id} path={$foto->path} (file tidak ditemukan di storage, dilewati)");
                    $totalMissingFile++;
                    continue;
                }

                $thumbPath = dirname($foto->path) . '/thumb/' . basename($foto->path);

                if ($disk->exists($thumbPath)) {
                    // Thumbnail fisik sudah ada, tinggal isi kolomnya saja.
                    if (!$dryRun) {
                        $foto->forceFill(['thumb_path' => $thumbPath])->save();
                    }
                    $this->line("  [SUDAH ADA FILE] id={$foto->id} -> {$thumbPath}");
                    $totalOk++;
                    continue;
                }

                try {
                    $binary = $disk->get($foto->path);
                    $thumb  = Image::make($binary)
                        ->orientate()
                        ->fit(400, 400)
                        ->encode('jpg', 80);

                    if (!$dryRun) {
                        $disk->makeDirectory(dirname($foto->path) . '/thumb');
                        $disk->put($thumbPath, (string) $thumb);
                        $foto->forceFill(['thumb_path' => $thumbPath])->save();
                    }

                    $this->line("  [DIBUAT] id={$foto->id} -> {$thumbPath}");
                    $totalOk++;
                } catch (\Throwable $e) {
                    $this->error("  [GAGAL] id={$foto->id} path={$foto->path}: " . $e->getMessage());
                    $totalSkip++;
                }
            }
        }

        $this->newLine();
        $this->info("Selesai. Berhasil: {$totalOk}, Dilewati: {$totalSkip}, File asli hilang: {$totalMissingFile}");
        if ($dryRun) {
            $this->comment('(Mode --dry-run: tidak ada perubahan yang benar-benar disimpan)');
        }

        return self::SUCCESS;
    }
}
