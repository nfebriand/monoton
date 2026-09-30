<?php
/**
 * MONOTON+ PWA Adaptive Icon Generator v2
 *
 * Generate:
 * - icon-72 sampai icon-512
 * - adaptive/foreground-512.png
 * - adaptive/background-512.png
 *
 * Jalankan:
 * php generate-icons.php
 */

$key = $_GET['key'] ?? '';

if ($key !== 'monoton2024' && PHP_SAPI !== 'cli') {
    die('Unauthorized');
}

if (!extension_loaded('gd')) {
    die('GD extension not available');
}

$baseDir = __DIR__;
$uploadsDir = __DIR__ . '/../uploads/settings/';

$logoFile = null;

if (is_dir($uploadsDir)) {
    $files = glob($uploadsDir . '*.{png,jpg,jpeg}', GLOB_BRACE);
    if ($files) {
        usort($files, fn($a,$b)=>filemtime($b)-filemtime($a));
        $logoFile = $files[0];
    }
}

function loadImage($file)
{
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($ext === 'png') return imagecreatefrompng($file);
    if ($ext === 'jpg' || $ext === 'jpeg') return imagecreatefromjpeg($file);

    return null;
}

function savePNG($img,$file)
{
    imagealphablending($img,true);
    imagesavealpha($img,true);
    imagepng($img,$file,6);
}

function createBackground($file)
{
    $size = 512;

    $img=imagecreatetruecolor($size,$size);
    imagealphablending($img,true);
    imagesavealpha($img,true);

    for($y=0;$y<$size;$y++){

        $ratio=$y/$size;

        $r=(int)(0 + (20*$ratio));
        $g=(int)(60 + (100*$ratio));
        $b=(int)(140 + (30*$ratio));

        $color=imagecolorallocate($img,$r,$g,$b);

        imageline($img,0,$y,$size,$y,$color);
    }

    savePNG($img,$file);
    imagedestroy($img);
}


function createForeground($logo,$file)
{
    $size=512;

    $canvas=imagecreatetruecolor($size,$size);

    imagealphablending($canvas,false);
    imagesavealpha($canvas,true);

    $transparent=imagecolorallocatealpha($canvas,0,0,0,127);
    imagefill($canvas,0,0,$transparent);


    if($logo && file_exists($logo)){

        $src=loadImage($logo);

        if($src){

            $sw=imagesx($src);
            $sh=imagesy($src);

            // Safe zone adaptive icon 60%
            $max=300;

            $ratio=min($max/$sw,$max/$sh);

            $nw=(int)($sw*$ratio);
            $nh=(int)($sh*$ratio);

            $x=(512-$nw)/2;
            $y=(512-$nh)/2;


            imagecopyresampled(
                $canvas,
                $src,
                $x,$y,
                0,0,
                $nw,$nh,
                $sw,$sh
            );

            imagedestroy($src);
        }
    }

    savePNG($canvas,$file);
    imagedestroy($canvas);
}


// Folder adaptive
if(!is_dir($baseDir.'/adaptive')){
    mkdir($baseDir.'/adaptive',0777,true);
}


// Adaptive assets
createBackground(
    $baseDir.'/adaptive/background-512.png'
);

createForeground(
    $logoFile,
    $baseDir.'/adaptive/foreground-512.png'
);


// Normal PWA icons
$sizes=[72,96,128,144,152,192,384,512];

foreach($sizes as $size){

    $canvas=imagecreatetruecolor($size,$size);

    imagealphablending($canvas,true);
    imagesavealpha($canvas,true);

    $bg=imagecolorallocate($canvas,10,61,98);

    imagefill($canvas,0,0,$bg);


    if($logoFile){

        $src=loadImage($logoFile);

        if($src){

            $sw=imagesx($src);
            $sh=imagesy($src);

            // lebih kecil agar aman crop Android
            $max=$size*0.55;

            $ratio=min($max/$sw,$max/$sh);

            $nw=$sw*$ratio;
            $nh=$sh*$ratio;

            imagecopyresampled(
                $canvas,
                $src,
                ($size-$nw)/2,
                ($size-$nh)/2,
                0,0,
                $nw,$nh,
                $sw,$sh
            );

            imagedestroy($src);
        }
    }


    imagepng(
        $canvas,
        $baseDir."/icon-$size.png",
        6
    );

    imagedestroy($canvas);

    echo "Generated icon-$size.png\n";
}


echo "MONOTON+ Adaptive Icon Ready\n";
