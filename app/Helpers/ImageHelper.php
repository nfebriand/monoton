<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Intervention\Image\Image as InterventionImage;

class ImageHelper
{
    /**
     * Baca tag EXIF Orientation dari sebuah file gambar.
     * Return 1 (normal) kalau tidak ada data EXIF (mis. PNG/WEBP, atau
     * foto tanpa metadata), supaya aman dipakai untuk file apa saja.
     */
    private static function readExifOrientation(string $path): int
    {
        if (!function_exists('exif_read_data')) return 1;
        $exif = @exif_read_data($path);
        if (!$exif || empty($exif['Orientation'])) return 1;
        return (int) $exif['Orientation'];
    }

    /**
     * Terapkan rotasi/flip yang benar sesuai nilai EXIF Orientation (1-8).
     * Ditulis manual (bukan andalkan ->orientate() bawaan Intervention)
     * karena driver GD kadang kurang tepat menangani nilai yang melibatkan
     * flip/mirror (2, 4, 5, 7) — umum terjadi pada foto dari kamera depan
     * HP tertentu.
     */
    public static function applyOrientation(InterventionImage $image, int $orientation): InterventionImage
    {
        switch ($orientation) {
            case 2: return $image->flip('h');
            case 3: return $image->rotate(180);
            case 4: return $image->flip('v');
            case 5: return $image->flip('h')->rotate(-90);
            case 6: return $image->rotate(-90);
            case 7: return $image->flip('h')->rotate(90);
            case 8: return $image->rotate(90);
            default: return $image; // 1 atau tidak diketahui: tidak diubah
        }
    }

    /**
     * Simpan gambar + thumbnail
     *
     * return:
     * [
     *   'original' => 'pemancar/xxxxx.jpg',
     *   'thumbnail' => 'pemancar/thumb/xxxxx.jpg'
     * ]
     */
    public static function saveWithThumbnail($file, $folder)
    {
        // pastikan folder ada
        Storage::disk('public')->makeDirectory($folder);
        Storage::disk('public')->makeDirectory($folder . '/thumb');

        // Baca orientasi EXIF dari file asli SEBELUM diproses Intervention
        // (Intervention/encode() akan membuang metadata EXIF, jadi harus
        // dibaca lebih dulu dari file upload mentahnya).
        $orientation = self::readExifOrientation($file->getRealPath());

        // selalu jpg
	$filename = \Illuminate\Support\Str::uuid() . '.jpg';
        $originalPath = $folder . '/' . $filename;
        $thumbPath    = $folder . '/thumb/' . $filename;

        /**
         * ORIGINAL
         */
        $image = self::applyOrientation(Image::make($file), $orientation)
            ->resize(1600, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode('jpg', 85);

        Storage::disk('public')->put(
            $originalPath,
            (string) $image
        );

        /**
         * THUMBNAIL
         */
        $thumb = self::applyOrientation(Image::make($file), $orientation)
            ->fit(400, 400)
            ->encode('jpg', 80);

        Storage::disk('public')->put(
            $thumbPath,
            (string) $thumb
        );

        return [
            'original'  => $originalPath,
            'thumbnail' => $thumbPath,
        ];
    }

    /**
     * Hitung path thumbnail dari path original (folder/thumb/namafile).
     */
    public static function thumbnail($path)
    {
        return dirname($path) . '/thumb/' . basename($path);
    }

    /**
     * Hapus file original + thumbnail-nya sekaligus (aman walau salah
     * satunya tidak ada).
     */
    public static function deleteWithThumbnail($path)
    {
        if (!$path) return;
        $disk = Storage::disk('public');
        if ($disk->exists($path)) $disk->delete($path);
        $thumb = self::thumbnail($path);
        if ($disk->exists($thumb)) $disk->delete($thumb);
    }

    /**
     * URL original (lewat storage symlink: public/storage -> storage/app/public)
     */
    public static function url($path)
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http')) return $path;
        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * URL thumbnail
     */
    public static function thumbnailUrl($path)
    {
        if (!$path) return null;
        return self::url(self::thumbnail($path));
    }
}
