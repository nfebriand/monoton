<?php

namespace App\Http\Controllers;

use App\Models\Pemancar;
use App\Models\PemancarDokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Unggah / lihat / hapus dokumen pemancar. Unggah & hapus: Super Admin (route AdminOnly). Lihat: sama dengan akses halaman pemancar. */
class PemancarDokumenController extends Controller
{
    private function cekAkses(Pemancar $pemancar): void
    {
        $u = auth()->user();
        if ($u->isOperator() && $u->lokasi_dinas && $pemancar->lokasi !== $u->lokasi_dinas) {
            abort(403, 'Anda tidak memiliki akses ke pemancar ini.');
        }
    }

    public function store(Request $request, Pemancar $pemancar)
    {
        $request->validate([
            'kategori'   => 'required|in:'.implode(',', array_keys(PemancarDokumen::KATEGORI)),
            'judul'      => 'nullable|string|max:150',
            'berkas'     => 'required|array|min:1|max:10',
            'berkas.*'   => 'file|mimes:'.implode(',', PemancarDokumen::EXT).'|max:51200',
        ], [
            'berkas.*.uploaded' => 'Berkas gagal diunggah (kemungkinan melebihi batas upload_max_filesize/post_max_size di server).',
            'berkas.*.mimes'    => 'Format berkas harus: '.implode(', ', PemancarDokumen::EXT).'.',
            'berkas.*.max'      => 'Ukuran tiap berkas maksimal 50 MB.',
        ]);

        $n = 0;
        foreach ($request->file('berkas') as $f) {
            $ext   = strtolower($f->getClientOriginalExtension());
            $asli  = Str::limit(preg_replace('/[^\w .()\-]+/u', '_', $f->getClientOriginalName()), 200, '');
            $judul = trim((string) $request->judul) !== '' && count($request->file('berkas')) === 1
                   ? trim($request->judul)
                   : pathinfo($asli, PATHINFO_FILENAME);
            $path = Storage::disk('local')->putFileAs(
                'pemancar-dokumen/'.$pemancar->id, $f, Str::random(24).'.'.$ext
            );
            PemancarDokumen::create([
                'pemancar_id' => $pemancar->id,
                'user_id'     => auth()->id(),
                'kategori'    => $request->kategori,
                'judul'       => Str::limit($judul, 150, ''),
                'path'        => $path,
                'nama_asli'   => $asli,
                'mime'        => $f->getClientMimeType(),
                'ukuran'      => $f->getSize(),
            ]);
            $n++;
        }
        return back()->with('success', "{$n} berkas ditambahkan ke dokumen pemancar.");
    }

    /** Lihat (gambar/PDF tampil di browser) atau unduh. ?unduh=1 memaksa unduh. */
    public function show(Request $request, Pemancar $pemancar, PemancarDokumen $dokumen)
    {
        abort_unless($dokumen->pemancar_id === $pemancar->id, 404);
        $this->cekAkses($pemancar);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($dokumen->path), 404, 'Berkas tidak ditemukan di server.');
        $full = $disk->path($dokumen->path);

        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=3600'];
        if (!$request->boolean('unduh') && ($dokumen->is_image || $dokumen->is_pdf)) {
            $headers['Content-Type'] = $dokumen->is_pdf ? 'application/pdf' : (str_ends_with(strtolower($dokumen->nama_asli), 'png') ? 'image/png' : (str_ends_with(strtolower($dokumen->nama_asli), 'webp') ? 'image/webp' : 'image/jpeg'));
            $headers['Content-Disposition'] = 'inline; filename="'.addslashes($dokumen->nama_asli).'"';
            return response()->file($full, $headers);
        }
        return response()->download($full, $dokumen->nama_asli, $headers);
    }

    public function destroy(Pemancar $pemancar, PemancarDokumen $dokumen)
    {
        abort_unless($dokumen->pemancar_id === $pemancar->id, 404);
        Storage::disk('local')->delete($dokumen->path);
        $dokumen->delete();
        return back()->with('success', 'Berkas dihapus.');
    }
}
