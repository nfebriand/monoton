<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageService
{
    public const ORIGINAL_WIDTH = 1600;
    public const MEDIUM_WIDTH   = 900;
    public const THUMB_WIDTH    = 300;
    public const THUMB_HEIGHT   = 300;

    public static function upload(
        UploadedFile $file,
        string $folder
    ) {

        $filename = Str::random(40) . '.jpg';

        /*
        |--------------------------------------------------------------------------
        | Folder
        |--------------------------------------------------------------------------
        */

        $originalPath = $folder.'/original/'.$filename;
        $mediumPath   = $folder.'/medium/'.$filename;
        $thumbPath    = $folder.'/thumb/'.$filename;


        /*
        |--------------------------------------------------------------------------
        | Load Image
        |--------------------------------------------------------------------------
        */

        $image = Image::make($file);


        /*
        |--------------------------------------------------------------------------
        | Original compressed
        |--------------------------------------------------------------------------
        */

        $image->orientate()
              ->resize(
                    self::ORIGINAL_WIDTH,
                    null,
                    function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
              )
              ->encode('jpg',85);


        Storage::disk('public')
            ->put($originalPath,$image);


        /*
        |--------------------------------------------------------------------------
        | Medium
        |--------------------------------------------------------------------------
        */

        $image = Image::make($file);

        $image->orientate()
              ->resize(
                    self::MEDIUM_WIDTH,
                    null,
                    function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
              )
              ->encode('jpg',80);


        Storage::disk('public')
            ->put($mediumPath,$image);



        /*
        |--------------------------------------------------------------------------
        | Thumbnail
        |--------------------------------------------------------------------------
        */

        $image = Image::make($file);

        $image->orientate()
              ->fit(self::THUMB_WIDTH, self::THUMB_HEIGHT)
              ->encode('jpg',75);


        Storage::disk('public')
            ->put($thumbPath,$image);



        return [
            'original'=>$originalPath,
            'medium'=>$mediumPath,
            'thumb'=>$thumbPath,
        ];

    }
}
