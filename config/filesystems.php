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
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],
    ],
    'links' => [
    public_path('storage') => storage_path('app/public'),
],  // kosong — tidak pakai symlink
];
