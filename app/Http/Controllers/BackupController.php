<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** Backup & restore database — khusus Super Admin (grup route AdminOnly). */
class BackupController extends Controller
{
    public function index()
    {
        $files = DatabaseBackup::list();
        $total = array_sum(array_column($files, 'size'));
        $dbName = \DB::connection()->getDatabaseName();
        return view('backup.index', compact('files', 'total', 'dbName'));
    }

    public function create()
    {
        try {
            $name = DatabaseBackup::backup();
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['backup' => 'Backup gagal: '.$e->getMessage()]);
        }
        return back()->with('success', "Backup dibuat: {$name}");
    }

    public function download(string $file)
    {
        $path = DatabaseBackup::path($file) ?? abort(404);
        return response()->download($path, $file, ['Cache-Control' => 'no-store']);
    }

    public function destroy(string $file)
    {
        $path = DatabaseBackup::path($file) ?? abort(404);
        @unlink($path);
        return back()->with('success', "Backup {$file} dihapus.");
    }

    /** Unggah file backup (.sql / .sql.gz) ke daftar (belum di-restore). */
    public function upload(Request $request)
    {
        $request->validate(['berkas' => 'required|file|max:512000']); // 500 MB (dibatasi juga oleh php.ini)
        $f = $request->file('berkas');
        $orig = strtolower($f->getClientOriginalName());
        if (!preg_match('/\.sql(\.gz)?$/', $orig)) {
            return back()->withErrors(['berkas' => 'Berkas harus berekstensi .sql atau .sql.gz']);
        }
        $ext  = str_ends_with($orig, '.gz') ? '.sql.gz' : '.sql';
        $name = 'upload-'.date('Ymd-His').$ext;
        $f->move(DatabaseBackup::dir(), $name);
        return back()->with('success', "Berkas diunggah sebagai {$name}. Klik Restore untuk memulihkan.");
    }

    /** Restore dari file di daftar. Wajib password + ketik RESTORE. Backup pengaman dibuat dulu. */
    public function restore(Request $request, string $file)
    {
        $path = DatabaseBackup::path($file) ?? abort(404);

        $request->validate([
            'password'   => 'required|string',
            'konfirmasi' => 'required|in:RESTORE',
        ], ['konfirmasi.in' => 'Ketik RESTORE (huruf besar) untuk konfirmasi.']);

        if (!Hash::check($request->password, $request->user()->password)) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        try {
            $pengaman = DatabaseBackup::backup('pre-restore');
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['backup' => 'Restore dibatalkan: backup pengaman gagal ('.$e->getMessage().').']);
        }

        try {
            $n = DatabaseBackup::restore($path);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['backup' => 'Restore gagal: '.$e->getMessage().' — kondisi sebelum restore tersimpan di '.$pengaman.'.']);
        }

        return back()->with('success', "Restore selesai ({$n} statement). Kondisi sebelum restore tersimpan di {$pengaman}. Jika sesi login berubah, silakan login ulang.");
    }
}
