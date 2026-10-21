<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;

/**
 * Backup & restore database MySQL tanpa bergantung pada mysqldump/mysql CLI.
 * - Backup  : file .sql.gz berisi DROP/CREATE TABLE + INSERT (batch 100 baris), streaming (hemat memori).
 * - Restore : membaca .sql / .sql.gz (buatan aplikasi ini ATAU mysqldump), mengeksekusi per statement.
 * File disimpan di storage/app/backups/db (privat, tidak bisa diakses lewat web).
 */
class DatabaseBackup
{
    public const DIR = 'backups/db';

    public static function dir(): string
    {
        $d = storage_path('app/'.self::DIR);
        if (!is_dir($d)) @mkdir($d, 0775, true);
        return $d;
    }

    /** Daftar file backup, terbaru di atas. */
    public static function list(): array
    {
        $out = [];
        foreach (glob(self::dir().'/*.sql{,.gz}', GLOB_BRACE) ?: [] as $f) {
            $out[] = [
                'name'  => basename($f),
                'size'  => filesize($f),
                'mtime' => filemtime($f),
            ];
        }
        usort($out, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
        return $out;
    }

    /** Validasi nama file (cegah path traversal) dan kembalikan path penuh, atau null. */
    public static function path(string $name): ?string
    {
        if (!preg_match('/^[A-Za-z0-9._-]+\.sql(\.gz)?$/', $name)) return null;
        $p = self::dir().'/'.$name;
        return is_file($p) ? $p : null;
    }

    /** Buat backup baru. Mengembalikan nama file. */
    public static function backup(string $label = ''): string
    {
        @set_time_limit(0);
        $conn = DB::connection();
        $pdo  = $conn->getPdo();
        $dbName = $conn->getDatabaseName();

        $name = 'monoton-db-'.date('Ymd-His').($label !== '' ? '-'.preg_replace('/[^A-Za-z0-9_-]/', '', $label) : '').'.sql.gz';
        $path = self::dir().'/'.$name;
        $tmp  = $path.'.part';

        $gz = gzopen($tmp, 'wb6');
        if (!$gz) throw new RuntimeException('Tidak bisa menulis ke folder backup: '.self::dir());

        try {
            gzwrite($gz, "-- MonOTOn database backup\n-- Database: {$dbName}\n-- Waktu: ".date('Y-m-d H:i:s')."\n\n");
            gzwrite($gz, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET UNIQUE_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            $tables = array_map(
                fn($r) => array_values((array) $r)[0],
                DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")
            );

            foreach ($tables as $t) {
                $q = '`'.str_replace('`', '``', $t).'`';
                $create = (array) DB::selectOne("SHOW CREATE TABLE {$q}");
                $ddl = $create['Create Table'] ?? array_values($create)[1] ?? null;
                if (!$ddl) continue;

                gzwrite($gz, "-- Tabel {$t}\nDROP TABLE IF EXISTS {$q};\n{$ddl};\n\n");

                // Baca baris tanpa buffer (hemat memori)
                $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                try {
                    $stmt = $pdo->query("SELECT * FROM {$q}");
                    $stmt->setFetchMode(PDO::FETCH_NUM);
                    $batch = []; $cols = null;
                    $colList = null;
                    for ($i = 0, $n = $stmt->columnCount(); $i < $n; $i++) {
                        $m = $stmt->getColumnMeta($i);
                        $cols[] = '`'.str_replace('`', '``', $m['name']).'`';
                    }
                    $colList = $cols ? '('.implode(',', $cols).')' : '';
                    while (($row = $stmt->fetch()) !== false) {
                        $vals = [];
                        foreach ($row as $v) {
                            if ($v === null) { $vals[] = 'NULL'; continue; }
                            $v = (string) $v;
                            if ($v !== '' && !mb_check_encoding($v, 'UTF-8')) { $vals[] = '0x'.bin2hex($v); continue; }
                            $vals[] = $pdo->quote($v);
                        }
                        $batch[] = '('.implode(',', $vals).')';
                        if (count($batch) >= 100) {
                            gzwrite($gz, "INSERT INTO {$q} {$colList} VALUES\n".implode(",\n", $batch).";\n");
                            $batch = [];
                        }
                    }
                    if ($batch) gzwrite($gz, "INSERT INTO {$q} {$colList} VALUES\n".implode(",\n", $batch).";\n");
                    $stmt->closeCursor();
                } finally {
                    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                }
                gzwrite($gz, "\n");
            }

            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\nSET UNIQUE_CHECKS=1;\n-- Selesai\n");
            gzclose($gz);
        } catch (\Throwable $e) {
            @gzclose($gz);
            @unlink($tmp);
            throw $e;
        }

        rename($tmp, $path);
        @chmod($path, 0640);
        return $name;
    }

    /**
     * Pecah isi file SQL menjadi statement (aman terhadap ';' di dalam string/backtick).
     * $lines: iterable potongan teks (per baris). Yield tiap statement tanpa ';' penutup.
     */
    public static function splitStatements(iterable $lines): \Generator
    {
        $buf = ''; $q = null; $esc = false; $skipRest = false; $atLineStart = true;
        foreach ($lines as $line) {
            $endsNl = substr($line, -1) === "\n";
            if ($skipRest) { // lanjutan baris komentar yang sangat panjang
                $skipRest = !$endsNl; $atLineStart = $endsNl; continue;
            }
            if ($q === null && $atLineStart && trim($buf) === '') {
                $t = ltrim($line);
                if ($t === '' ) { $atLineStart = $endsNl; continue; }
                if ($t[0] === '#' || substr($t, 0, 2) === '--') { $skipRest = !$endsNl; $atLineStart = $endsNl; continue; }
            }
            $atLineStart = $endsNl;

            $i = 0; $len = strlen($line);
            while ($i < $len) {
                if ($q !== null) {
                    if ($esc) { $esc = false; $buf .= $line[$i]; $i++; continue; }
                    $stop = $q === '`' ? $q : $q.'\\';
                    $n = strcspn($line, $stop, $i);
                    if ($n > 0) { $buf .= substr($line, $i, $n); $i += $n; if ($i >= $len) break; }
                    $c = $line[$i]; $buf .= $c; $i++;
                    if ($c === '\\') $esc = true;
                    elseif ($c === $q) $q = null;
                } else {
                    $n = strcspn($line, ";'\"`", $i);
                    if ($n > 0) { $buf .= substr($line, $i, $n); $i += $n; if ($i >= $len) break; }
                    $c = $line[$i]; $i++;
                    if ($c === ';') {
                        $s = trim($buf); $buf = '';
                        if ($s !== '') yield $s;
                    } else { $buf .= $c; $q = $c; }
                }
            }
        }
        $s = trim($buf);
        if ($s !== '') yield $s;
    }

    /** Restore dari file backup. Mengembalikan jumlah statement yang dieksekusi. */
    public static function restore(string $path): int
    {
        @set_time_limit(0);
        $gzip = str_ends_with($path, '.gz');
        $h = $gzip ? gzopen($path, 'rb') : fopen($path, 'rb');
        if (!$h) throw new RuntimeException('File backup tidak bisa dibuka.');

        $gen = (function () use ($h, $gzip) {
            try {
                while (!($gzip ? gzeof($h) : feof($h))) {
                    $l = $gzip ? gzgets($h, 1048576) : fgets($h, 1048576);
                    if ($l === false) break;
                    yield $l;
                }
            } finally {
                $gzip ? gzclose($h) : fclose($h);
            }
        })();

        $pdo = DB::connection()->getPdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $pdo->exec('SET UNIQUE_CHECKS=0');
        $n = 0;
        try {
            foreach (self::splitStatements($gen) as $sql) {
                try {
                    $pdo->exec($sql);
                } catch (\Throwable $e) {
                    throw new RuntimeException('Gagal pada statement #'.($n + 1).' ('.mb_strimwidth(preg_replace('/\s+/', ' ', $sql), 0, 90, '…').'): '.$e->getMessage());
                }
                $n++;
            }
        } finally {
            @$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            @$pdo->exec('SET UNIQUE_CHECKS=1');
        }
        return $n;
    }

    /** Hapus backup otomatis lama, sisakan $keep terbaru (hanya file monoton-db-*). */
    public static function prune(int $keep): int
    {
        $files = array_values(array_filter(self::list(), fn($f) => str_starts_with($f['name'], 'monoton-db-') && !str_contains($f['name'], 'pre-restore')));
        $del = 0;
        foreach (array_slice($files, max(1, $keep)) as $f) {
            if (@unlink(self::dir().'/'.$f['name'])) $del++;
        }
        return $del;
    }
}
