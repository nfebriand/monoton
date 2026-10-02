<?php

namespace App\Console\Commands;

use App\Helpers\ImageHelper;
use App\Models\EvidenFoto;
use App\Models\AsetFoto;
use App\Models\MaintenanceFoto;
use App\Models\StudioLogFoto;
use App\Models\StudioPerangkatFoto;
use App\Models\PemancarFoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class FixPhotoOrientation extends Command
{
    /**
     * php artisan fotos:fix-orientation
     * php artisan fotos:fix-orientation --dry-run
     */
    protected $signature = 'fotos:fix-orientation {--dry-run : Hanya tampilkan apa yang akan diproses, tanpa menulis apa pun}';

    protected $description = 'Putar ulang permanen foto LAMA yang orientasinya salah, berdasarkan tag EXIF Orientation yang masih tersimpan di filenya';

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

        if (!function_exists('exif_read_data')) {
            $this->error('Ekstensi PHP "exif" tidak aktif di server ini. Tidak bisa membaca orientasi foto.');
            return self::FAILURE;
        }

        $totalFixed  = 0;
        $totalNormal = 0;
        $totalMissingFile = 0;
        $totalNoExif = 0;

        foreach ($this->models as $label => $modelClass) {
            $this->info("=== {$label} ===");
            $rows = $modelClass::whereNotNull('path')->where('path', '!=', '')->get();

            if ($rows->isEmpty()) {
                $this->line('  (tidak ada foto)');
                continue;
            }

            foreach ($rows as $foto) {
                if (!$disk->exists($foto->path)) {
                    $totalMissingFile++;
                    continue;
                }

                $fullPath = $disk->path($foto->path);
                $exif = @exif_read_data($fullPath);

                if (!$exif || empty($exif['Orientation'])) {
                    $totalNoExif++;
                    continue; // tidak ada tag EXIF, tidak ada yang bisa diperbaiki otomatis
                }

                $orientation = (int) $exif['Orientation'];

                if ($orientation === 1) {
                    $totalNormal++;
                    continue; // sudah normal
                }

                try {
                    $binary = $disk->get($foto->path);

                    // Putar ulang file ORIGINAL, lalu bakukan rotasinya (re-encode,
                    // EXIF dibuang supaya tidak ter-rotate dobel kalau diproses lagi nanti)
                    $fixed = ImageHelper::applyOrientation(Image::make($binary), $orientation)
                        ->encode('jpg', 85);

                    if (!$dryRun) {
                        $disk->put($foto->path, (string) $fixed);
                    }

                    // Regenerasi thumbnail juga dari versi yang sudah benar
                    $thumbPath = ImageHelper::thumbnail($foto->path);
                    $thumbFixed = ImageHelper::applyOrientation(Image::make($binary), $orientation)
                        ->fit(400, 400)
                        ->encode('jpg', 80);

                    if (!$dryRun) {
                        $disk->makeDirectory(dirname($foto->path) . '/thumb');
                        $disk->put($thumbPath, (string) $thumbFixed);
                        $foto->forceFill(['thumb_path' => $thumbPath])->save();
                    }

                    $this->line("  [DIPUTAR] id={$foto->id} orientation={$orientation} -> {$foto->path}");
                    $totalFixed++;
                } catch (\Throwable $e) {
                    $this->error("  [GAGAL] id={$foto->id} path={$foto->path}: " . $e->getMessage());
                }
            }
        }

        $this->newLine();
        $this->info("Selesai. Diputar: {$totalFixed}, Sudah normal: {$totalNormal}, Tanpa data EXIF: {$totalNoExif}, File hilang: {$totalMissingFile}");
        if ($totalNoExif > 0) {
            $this->comment("Catatan: {$totalNoExif} foto tidak punya data EXIF sama sekali (sering terjadi pada foto yang sudah pernah diedit/screenshot/dikompres aplikasi lain) -- tidak bisa dideteksi otomatis orientasinya, harus diperbaiki manual kalau memang salah.");
        }
        if ($dryRun) {
            $this->comment('(Mode --dry-run: tidak ada perubahan yang benar-benar disimpan)');
        }

        return self::SUCCESS;
    }
}
