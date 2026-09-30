<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageHelper
{
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

        // selalu jpg
	$filename = \Illuminate\Support\Str::uuid() . '.jpg';
        $originalPath = $folder . '/' . $filename;
        $thumbPath    = $folder . '/thumb/' . $filename;

        /**
         * ORIGINAL
         */
        $image = Image::make($file)
            ->orientate()
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
        $thumb = Image::make($file)
            ->orientate()
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
     * Hapus original + thumbnail
     */
    public static function deleteWithThumbnail($originalPath)
    {
        if (!$originalPath) {
            return;
        }

        Storage::disk('public')->delete($originalPath);

        $thumbPath = dirname($originalPath)
            . '/thumb/'
            . basename($originalPath);

        Storage::disk('public')->delete($thumbPath);
    }

    /**
     * Ambil path thumbnail
     */
    public static function thumbnail($originalPath)
    {
        if (!$originalPath) {
            return null;
        }

        return dirname($originalPath)
            . '/thumb/'
            . basename($originalPath);
    }

    /**
     * URL original
     */
    public static function url($path)
    {
        return asset('uploads/' . ltrim($path, '/'));
    }

    /**
     * URL thumbnail
     */
    public static function thumbnailUrl($path)
    {
        return asset('uploads/' . self::thumbnail($path));
    }
}
