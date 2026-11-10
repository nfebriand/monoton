<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class TandaiPasswordStandar extends Command
{
    protected $signature = 'monoton:tandai-password-standar {password? : Password standar yang diberikan (kosong = pakai config monoton.password_standar)} {--semua : Tandai SEMUA pengguna wajib ganti password}';
    protected $description = 'Tandai pengguna yang masih memakai password standar agar wajib menggantinya saat login berikutnya';

    public function handle(): int
    {
        if ($this->option('semua')) {
            $n = User::query()->update(['must_change_password' => true]);
            $this->info("$n pengguna ditandai wajib ganti password.");
            return self::SUCCESS;
        }
        $daftar = $this->argument('password') ? [$this->argument('password')] : (array) config('monoton.password_standar', []);
        if (!$daftar) { $this->error('Tidak ada password standar. Beri argumen atau set MONOTON_PASSWORD_STANDAR.'); return self::FAILURE; }

        $n = 0;
        User::where('must_change_password', false)->each(function (User $u) use ($daftar, &$n) {
            foreach ($daftar as $p) {
                if (Hash::check($p, $u->password)) {
                    $u->forceFill(['must_change_password' => true])->save();
                    $this->line(" - {$u->email}");
                    $n++;
                    break;
                }
            }
        });
        $this->info("$n pengguna ditandai wajib ganti password.");
        return self::SUCCESS;
    }
}
