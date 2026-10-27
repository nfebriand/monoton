<?php

namespace App\Support;

/**
 * Pemeriksaan kesiapan deploy (dipakai `php artisan monoton:preflight` & deploy.sh).
 * Bagian file-sistem (views) murni PHP agar bisa diuji tanpa Laravel.
 * Mengembalikan daftar temuan: ['level' => 'error'|'warn', 'pesan' => string].
 */
class Preflight
{
    /** @return array<int,array{level:string,pesan:string}> */
    public static function views(string $basePath): array
    {
        $out = [];
        $dir = rtrim($basePath, '/').'/resources/views';
        if (!is_dir($dir)) return [['level' => 'error', 'pesan' => "Folder view tidak ditemukan: $dir"]];

        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && preg_match('/\.php$/', $f->getFilename())) $files[] = $f->getPathname();
        }
        sort($files);

        $ada = function (string $nama) use ($dir): bool {
            $rel = str_replace('.', '/', $nama);
            return is_file("$dir/$rel.blade.php") || is_file("$dir/$rel.php");
        };

        foreach ($files as $path) {
            $rel = ltrim(substr($path, strlen($dir)), '/');
            $isi = (string) file_get_contents($path);

            // 1. Encoding: harus UTF-8 valid (penyebab simbol "�")
            if (!mb_check_encoding($isi, 'UTF-8')) {
                $out[] = ['level' => 'error', 'pesan' => "View bukan UTF-8 valid (simpan ulang sebagai UTF-8): $rel"];
                continue;
            }
            if (strncmp($isi, "\xEF\xBB\xBF", 3) === 0) {
                $out[] = ['level' => 'warn', 'pesan' => "View berawalan BOM UTF-8 (simpan tanpa BOM): $rel"];
            }

            // 2. Rujukan view yang tidak ada (penyebab 500 saat file belum terunggah)
            $tanpaKomentar = preg_replace('/\{\{--.*?--\}\}/s', '', $isi);
            if (preg_match_all('/@(?:include|extends|includeIf|each)\(\s*[\'"]([A-Za-z0-9_.\-]+)[\'"]/', $tanpaKomentar, $m)) {
                foreach (array_unique($m[1]) as $nama) {
                    if (!$ada($nama)) $out[] = ['level' => 'error', 'pesan' => "View '$nama' dirujuk di $rel tetapi file-nya tidak ada"];
                }
            }

            // 3. Direktif Blade tidak seimbang
            foreach ([['@if', '@endif'], ['@foreach', '@endforeach'], ['@forelse', '@endforelse'], ['@push', '@endpush'], ['@section', '@endsection']] as [$a, $b]) {
                $na = preg_match_all('/'.preg_quote($a, '/').'\b(?!\w)/', $tanpaKomentar);
                if ($a === '@section') {
                    // @section('x','y') satu-baris tidak memerlukan @endsection
                    $na = preg_match_all('/@section\(\s*[\'"][^\'"]+[\'"]\s*\)/', $tanpaKomentar);
                }
                if ($a === '@if') $na = preg_match_all('/@if\s*\(/', $tanpaKomentar);
                if ($a === '@push') $na = preg_match_all('/^\s*@push\s*\(/m', $tanpaKomentar);
                $nb = preg_match_all('/'.preg_quote($b, '/').'\b/', $tanpaKomentar);
                if ($na !== $nb) $out[] = ['level' => 'warn', 'pesan' => "Direktif $a/$b tidak seimbang ($na vs $nb) di $rel"];
            }

            // 4. Direktif menempel pada kata sebelumnya (mis. "shift@if(")
            if (preg_match('/[A-Za-z0-9_]@(?:if|foreach|forelse|endif|endforeach|else|elseif)\b/', preg_replace('/[\w.+-]+@[\w.-]+\.\w+/', '', $tanpaKomentar))) {
                $out[] = ['level' => 'warn', 'pesan' => "Kemungkinan direktif Blade menempel pada teks di $rel"];
            }
        }
        return $out;
    }

    /** Kolom di $fillable model yang tidak ada di tabel (penyebab data tidak tersimpan diam-diam / error). */
    public static function fillable(): array
    {
        $out = [];
        foreach (glob(base_path('app/Models/*.php')) as $file) {
            $kelas = 'App\\Models\\'.basename($file, '.php');
            if (!class_exists($kelas)) { $out[] = ['level' => 'error', 'pesan' => "Class $kelas tidak termuat (jalankan composer dump-autoload -o)"]; continue; }
            try {
                $m = new $kelas;
                if (!($m instanceof \Illuminate\Database\Eloquent\Model)) continue;
                $tabel = $m->getTable();
                if (!\Illuminate\Support\Facades\Schema::hasTable($tabel)) { $out[] = ['level' => 'error', 'pesan' => "Tabel '$tabel' (model ".class_basename($kelas).") belum ada - jalankan migrate"]; continue; }
                foreach ($m->getFillable() as $kol) {
                    if (!\Illuminate\Support\Facades\Schema::hasColumn($tabel, $kol)) {
                        $out[] = ['level' => 'error', 'pesan' => "Kolom '$kol' ada di fillable ".class_basename($kelas)." tetapi tidak ada di tabel '$tabel' (migrasi belum jalan?)"];
                    }
                }
            } catch (\Throwable $e) {
                $out[] = ['level' => 'warn', 'pesan' => "Gagal memeriksa ".class_basename($kelas).": ".$e->getMessage()];
            }
        }
        return $out;
    }

    /** Route yang menunjuk ke controller/method yang tidak ada. */
    public static function routes(): array
    {
        $out = [];
        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $r) {
            $a = $r->getAction('controller');
            if (!$a || !is_string($a)) continue;
            [$kelas, $method] = array_pad(explode('@', $a, 2), 2, '__invoke');
            if (!class_exists($kelas)) { $out[] = ['level' => 'error', 'pesan' => "Route ".$r->uri()." -> class $kelas tidak ada"]; continue; }
            if (!method_exists($kelas, $method)) $out[] = ['level' => 'error', 'pesan' => "Route ".$r->uri()." -> $kelas::$method tidak ada"];
        }
        return $out;
    }

    /** Migrasi yang belum dijalankan. */
    public static function migrasiTertunda(): array
    {
        try {
            $migrator = app('migrator');
            $repo = $migrator->getRepository();
            if (!$repo->repositoryExists()) return [['level' => 'error', 'pesan' => 'Tabel migrations belum ada']];
            $sudah = $repo->getRan();
            $files = $migrator->getMigrationFiles([database_path('migrations')]);
            $out = [];
            foreach (array_keys($files) as $nama) {
                if (!in_array($nama, $sudah, true)) $out[] = ['level' => 'error', 'pesan' => "Migrasi belum dijalankan: $nama"];
            }
            return $out;
        } catch (\Throwable $e) {
            return [['level' => 'warn', 'pesan' => 'Tidak bisa memeriksa migrasi: '.$e->getMessage()]];
        }
    }
}
