<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Daftar exception yang tidak dilaporkan.
     */
    protected $dontReport = [];

    /**
     * Daftar input yang tidak di-flash ke session saat validasi error.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Total ukuran upload (semua foto + field lain) melebihi batas
        // post_max_size di server -> tampilkan pesan yang jelas, bukan
        // halaman error mentah.
        $this->renderable(function (PostTooLargeException $e, $request) {
            $pesan = 'Ukuran file yang diupload terlalu besar (total semua foto sekaligus). '
                   . 'Coba upload lebih sedikit foto per kali, atau perkecil ukuran foto, lalu coba lagi.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan], 413);
            }

            return back()->withErrors(['fotos' => $pesan])->withInput($request->except(['fotos']));
        });
    }
}
