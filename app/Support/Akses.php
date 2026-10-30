<?php

namespace App\Support;


/**
 * SATU-SATUNYA tempat aturan "siapa boleh apa".
 *
 * Dipakai oleh controller (abort_unless) maupun view (@can via Gate di AuthServiceProvider),
 * sehingga tombol di layar selalu sama dengan izin sebenarnya di server.
 * Murni PHP (hanya memanggil method peran pada User) agar mudah diuji.
 *
 * Istilah: super admin = isAdmin(); admin divisi = isAdminDivisi(); lokasi_dinas Way Kanan = isWayKanan().
 */
class Akses
{
    // ───────────────────────── Menu / modul ─────────────────────────
    /** Pemancar, Catat/Riwayat Log, Cetak Laporan, Grafik Suhu */
    public static function menuTransmisi($u): bool
    {
        return $u->isAdmin() || $u->isDivisi('transmisi');
    }

    /** Studio: divisi Studio, super admin, atau personil berlokasi dinas Way Kanan */
    public static function studioMasuk($u): bool
    {
        return $u->canAccessStudio();
    }

    /** Maintenance & Aset (Sarana) */
    public static function menuSarana($u): bool
    {
        return $u->isAdmin() || $u->isDivisi('sarana');
    }

    /** Operasional Genset: Sarana + Transmisi + super admin */
    public static function gensetMasuk($u): bool
    {
        return $u->isAdmin() || $u->isDivisi('sarana') || $u->isDivisi('transmisi');
    }

    /** Kelola unit genset: super admin & admin divisi Sarana */
    public static function gensetUnitKelola($u): bool
    {
        return $u->isAdmin() || ($u->isAdminDivisi() && $u->isDivisi('sarana'));
    }

    // ───────────────────────── Perawatan rutin pemancar & genset ─────────────────────────
    /** Menu Perawatan Rutin: siapa pun yang bisa masuk modul pemancar atau genset */
    public static function perawatanMenu($u): bool
    {
        return self::menuTransmisi($u) || self::gensetMasuk($u);
    }

    /** Boleh melihat satu item. Operator pemancar dibatasi ke lokasi dinasnya. */
    public static function perawatanLihat($u, string $tipe, ?string $lokasiPemancar = null): bool
    {
        if ($tipe === 'genset') return self::gensetMasuk($u);
        if (!self::menuTransmisi($u)) return false;
        if ($u->isOperator() && !empty($u->lokasi_dinas)) {
            return strcasecmp(trim((string) $u->lokasi_dinas), trim((string) $lokasiPemancar)) === 0;
        }
        return true;
    }

    /** Mengelola jadwal (tambah/ubah/hapus): pemancar -> super admin & admin divisi Transmisi; genset -> super admin & admin divisi Sarana */
    public static function perawatanKelola($u, string $tipe): bool
    {
        if ($u->isAdmin()) return true;
        if (!$u->isAdminDivisi()) return false;
        return $tipe === 'genset' ? $u->isDivisi('sarana') : $u->isDivisi('transmisi');
    }

    /** Mencatat pelaksanaan: semua yang boleh melihat item tersebut (termasuk operator) */
    public static function perawatanCatat($u, string $tipe, ?string $lokasiPemancar = null): bool
    {
        return self::perawatanLihat($u, $tipe, $lokasiPemancar);
    }

    // ───────────────────────── Studio: master perangkat ─────────────────────────
    /** Menambah perangkat (tanpa record): super admin, admin divisi Studio, admin divisi berlokasi Way Kanan */
    public static function perangkatTambah($u): bool
    {
        return $u->isAdmin()
            || ($u->isAdminDivisi() && ($u->isDivisi('studio') || $u->isWayKanan()));
    }

    /** Mengubah/menghapus SATU perangkat. Admin Way Kanan (non-Studio) hanya perangkat lokasi Way Kanan. */
    public static function perangkatUbah($u, ?string $lokasiPerangkat): bool
    {
        if ($u->isAdmin()) return true;
        if (!$u->isAdminDivisi()) return false;
        if ($u->isDivisi('studio')) return true;
        return $u->isWayKanan() && strcasecmp(trim((string) $lokasiPerangkat), 'Way Kanan') === 0;
    }

    /** Admin yang dibatasi ke lokasi Way Kanan saja (admin divisi non-Studio berlokasi Way Kanan) */
    public static function adminTerbatasWayKanan($u): bool
    {
        return !$u->isAdmin() && $u->isAdminDivisi() && $u->isWayKanan() && !$u->isDivisi('studio');
    }

    // ───────────────────────── Studio: logbook & maintenance ─────────────────────────
    /** Mengubah log studio: super admin, admin divisi, atau pembuat log */
    public static function logStudioUbah($u, $pembuatId): bool
    {
        return $u->isAdmin() || $u->isAdminDivisi() || (int) $u->id === (int) $pembuatId;
    }

    /** Menghapus log maintenance studio: super admin & admin divisi Studio */
    public static function maintenanceStudioHapus($u): bool
    {
        return $u->isAdmin() || ($u->isAdminDivisi() && $u->isDivisi('studio'));
    }

    // ───────────────────────── Logbook Sarana ─────────────────────────
    /** Masuk menu Logbook Sarana: super admin & divisi Sarana (semua anggota boleh melihat logbook rekan) */
    public static function saranaLogMasuk($u): bool
    {
        return self::menuSarana($u);
    }

    /** Mengubah/menghapus logbook sarana: pembuat, super admin, atau admin divisi Sarana */
    public static function saranaLogUbah($u, $pembuatId): bool
    {
        return $u->isAdmin() || ($u->isAdminDivisi() && $u->isDivisi('sarana')) || (int) $u->id === (int) $pembuatId;
    }

    // ───────────────────────── Genset & operasional pemancar ─────────────────────────
    /** Mengubah log genset: super admin atau pembuat log */
    public static function gensetLogUbah($u, $pembuatId): bool
    {
        return $u->isAdmin() || (int) $u->id === (int) $pembuatId;
    }

    /** Menghapus log genset: harus bisa masuk modul genset DAN kelola unit (super admin / admin divisi Sarana) */
    public static function gensetLogHapus($u): bool
    {
        return self::gensetMasuk($u) && self::gensetUnitKelola($u);
    }

    /** Mengubah log operasional pemancar: super admin atau pembuat log */
    public static function operasionalUbah($u, $pembuatId): bool
    {
        return $u->isAdmin() || (int) $u->id === (int) $pembuatId;
    }

    public static function operasionalHapus($u): bool
    {
        return $u->isAdmin();
    }
}
