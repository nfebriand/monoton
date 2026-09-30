<?php

namespace App\Services;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

class ImageOptimizer
{

    public function optimize($file, $folder)
    {

        $image = Image::make($file);

        // resize jika terlalu besar
        if ($image->width() > 1600) {

            $image->resize(1600, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

        }


        $filename = Str::random(40).'.webp';


        $path = storage_path(
            'app/public/'.$folder.'/'.$filename
        );


        $image
            ->encode('webp',80)
            ->save($path);


        return $folder.'/'.$filename;

    }

}
