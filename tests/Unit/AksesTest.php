<?php

namespace Tests\Unit;

use App\Support\Akses;
use PHPUnit\Framework\TestCase;

/** Menguji aturan akses terpusat tanpa database (pengguna tiruan). */
class AksesTest extends TestCase
{
    private function u(string $role, ?string $divisi = null, ?string $lokasi = null, int $id = 1): object
    {
        return new class($role, $divisi, $lokasi, $id) {
            public ?string $lokasi_dinas;
            public function __construct(public string $role, public ?string $divisi, public ?string $lokasi, public int $id) { $this->lokasi_dinas = $lokasi; }
            public function isAdmin(): bool { return $this->role === 'admin'; }
            public function isOperator(): bool { return $this->role === 'operator'; }
            public function isAdminDivisi(): bool { return $this->role === 'admin_divisi'; }
            public function isDivisi(string $d): bool { return $this->divisi === $d; }
            public function isWayKanan(): bool { return strcasecmp(trim($this->lokasi ?? ''), 'Way Kanan') === 0; }
            public function canAccessStudio(): bool { return $this->isAdmin() || $this->isDivisi('studio') || $this->isWayKanan(); }
        };
    }

    public function test_tambah_perangkat_admin_way_kanan_boleh_operator_tidak(): void
    {
        $this->assertTrue(Akses::perangkatTambah($this->u('admin')));
        $this->assertTrue(Akses::perangkatTambah($this->u('admin_divisi', 'studio')));
        $this->assertTrue(Akses::perangkatTambah($this->u('admin_divisi', 'transmisi', 'Way Kanan')));
        $this->assertFalse(Akses::perangkatTambah($this->u('admin_divisi', 'transmisi', 'Pahoman')));
        $this->assertFalse(Akses::perangkatTambah($this->u('operator', 'studio', 'Way Kanan')));
    }

    public function test_ubah_perangkat_dibatasi_lokasi_untuk_admin_way_kanan(): void
    {
        $wk = $this->u('admin_divisi', 'transmisi', 'Way Kanan');
        $this->assertTrue(Akses::perangkatUbah($wk, 'Way Kanan'));
        $this->assertFalse(Akses::perangkatUbah($wk, 'Pahoman'));
        $this->assertTrue(Akses::perangkatUbah($this->u('admin_divisi', 'studio'), 'Pahoman'));
        $this->assertTrue(Akses::perangkatUbah($this->u('admin'), 'Pahoman'));
        $this->assertFalse(Akses::perangkatUbah($this->u('operator', 'studio'), 'Pahoman'));
    }

    public function test_log_studio_hanya_admin_atau_pembuat(): void
    {
        $this->assertTrue(Akses::logStudioUbah($this->u('operator', 'studio', null, 5), 5));
        $this->assertFalse(Akses::logStudioUbah($this->u('operator', 'studio', null, 5), 6));
        $this->assertTrue(Akses::logStudioUbah($this->u('admin_divisi', 'studio', null, 5), 6));
    }

    public function test_genset(): void
    {
        $this->assertTrue(Akses::gensetMasuk($this->u('operator', 'transmisi')));
        $this->assertFalse(Akses::gensetMasuk($this->u('operator', 'studio')));
        $this->assertTrue(Akses::gensetUnitKelola($this->u('admin_divisi', 'sarana')));
        $this->assertFalse(Akses::gensetUnitKelola($this->u('admin_divisi', 'transmisi')));
        // tombol Hapus log harus sama dengan izin controller (admin divisi Sarana ikut)
        $this->assertTrue(Akses::gensetLogHapus($this->u('admin_divisi', 'sarana')));
        $this->assertFalse(Akses::gensetLogHapus($this->u('operator', 'sarana')));
    }

    public function test_menu(): void
    {
        $this->assertTrue(Akses::menuTransmisi($this->u('operator', 'transmisi')));
        $this->assertFalse(Akses::menuTransmisi($this->u('operator', 'sarana')));
        $this->assertTrue(Akses::menuSarana($this->u('operator', 'sarana')));
        $this->assertTrue(Akses::studioMasuk($this->u('operator', 'transmisi', 'Way Kanan')));
        $this->assertFalse(Akses::studioMasuk($this->u('operator', 'transmisi', 'Pahoman')));
        $this->assertTrue(Akses::adminTerbatasWayKanan($this->u('admin_divisi', 'transmisi', 'Way Kanan')));
        $this->assertFalse(Akses::adminTerbatasWayKanan($this->u('admin_divisi', 'studio', 'Way Kanan')));
    }

    public function test_operasional(): void
    {
        $this->assertTrue(Akses::operasionalUbah($this->u('operator', 'transmisi', null, 3), 3));
        $this->assertFalse(Akses::operasionalUbah($this->u('operator', 'transmisi', null, 3), 4));
        $this->assertFalse(Akses::operasionalHapus($this->u('admin_divisi', 'transmisi')));
        $this->assertTrue(Akses::operasionalHapus($this->u('admin')));
    }

    public function test_perawatan(): void
    {
        $opLiwa = $this->u('operator', 'transmisi', 'Liwa');
        $this->assertTrue(Akses::perawatanLihat($opLiwa, 'pemancar', 'Liwa'));
        $this->assertFalse(Akses::perawatanLihat($opLiwa, 'pemancar', 'Sukarame'));
        $this->assertTrue(Akses::perawatanLihat($opLiwa, 'genset'));
        $this->assertFalse(Akses::perawatanLihat($this->u('operator', 'studio'), 'pemancar', 'Liwa'));
        $this->assertFalse(Akses::perawatanLihat($this->u('operator', 'studio'), 'genset'));
        $this->assertTrue(Akses::perawatanLihat($this->u('operator', 'sarana'), 'genset'));
        $this->assertFalse(Akses::perawatanLihat($this->u('operator', 'sarana'), 'pemancar', 'Liwa'));
        // kelola
        $this->assertTrue(Akses::perawatanKelola($this->u('admin_divisi', 'transmisi'), 'pemancar'));
        $this->assertFalse(Akses::perawatanKelola($this->u('admin_divisi', 'transmisi'), 'genset'));
        $this->assertTrue(Akses::perawatanKelola($this->u('admin_divisi', 'sarana'), 'genset'));
        $this->assertFalse(Akses::perawatanKelola($opLiwa, 'pemancar'));
        $this->assertTrue(Akses::perawatanKelola($this->u('admin'), 'genset'));
        // operator boleh mencatat pelaksanaan
        $this->assertTrue(Akses::perawatanCatat($opLiwa, 'pemancar', 'Liwa'));
        $this->assertTrue(Akses::perawatanMenu($opLiwa));
        $this->assertFalse(Akses::perawatanMenu($this->u('operator', 'studio')));
    }
}
