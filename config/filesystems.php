<?php
return [
    'default' => env('FILESYSTEM_DISK', 'public'),
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
            'throw'  => false,
        ],
        // Disk 'public' langsung ke /public/uploads — tanpa symlink
        'public' => [
            'driver'     => 'local',
            'root'       => public_path('uploads'),
            'url'        => env('APP_URL').'/uploads',
            'visibility' => 'public',
            'throw'      => false,
        ],
    ],
    'links' => [],  // kosong — tidak pakai symlink
];
