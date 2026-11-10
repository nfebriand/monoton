<?php

return [
    /*
     | Password standar/awal yang diberikan administrator (pisahkan dengan koma di .env).
     | Pengguna yang masih memakai salah satunya wajib mengganti password sebelum memakai aplikasi.
     | Contoh .env:  MONOTON_PASSWORD_STANDAR="monoton123,password"
     */
    'password_standar' => array_values(array_filter(array_map('trim', explode(',', (string) env('MONOTON_PASSWORD_STANDAR', 'password,monoton123'))))),
];
