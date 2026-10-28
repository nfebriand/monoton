<?php

namespace Tests\Feature;

use App\Models\Eviden;
use Tests\TestCase;

/** Butuh aplikasi ter-boot, tetapi TIDAK menyentuh database (hanya membentuk SQL). */
class PerawatanEvidenTest extends TestCase
{
    private function u(string $role, ?string $divisi = null, int $id = 5): object
    {
        return new class($role, $divisi, $id) {
            public function __construct(public string $role, public ?string $divisi, public int $id) {}
            public function isAdmin(): bool { return $this->role === 'admin'; }
            public function isAdminDivisi(): bool { return $this->role === 'admin_divisi'; }
        };
    }

    public function test_super_admin_melihat_semua_eviden(): void
    {
        $sql = Eviden::query()->dapatDiakses($this->u('admin'))->toSql();
        $this->assertStringNotContainsString('where', strtolower($sql));
    }

    public function test_operator_hanya_eviden_miliknya_atau_yang_ia_terlibat(): void
    {
        $q   = Eviden::query()->dapatDiakses($this->u('operator', 'transmisi', 9));
        $sql = strtolower($q->toSql());
        $this->assertStringContainsString('user_id', $sql);
        $this->assertStringContainsString('eviden_operators', $sql);
        $this->assertStringNotContainsString('divisi', $sql);
        $this->assertContains(9, $q->getBindings());
    }

    public function test_admin_divisi_melihat_divisinya_miliknya_atau_terlibat(): void
    {
        $q   = Eviden::query()->dapatDiakses($this->u('admin_divisi', 'sarana', 4));
        $sql = strtolower($q->toSql());
        $this->assertStringContainsString('divisi', $sql);
        $this->assertStringContainsString('eviden_operators', $sql);
        $this->assertContains('sarana', $q->getBindings());
    }

    public function test_relasi_dan_form_terpasang(): void
    {
        $base = dirname(__DIR__, 2).'/';
        $this->assertStringContainsString("'eviden_id'", file_get_contents($base.'app/Models/PerawatanRutinLog.php'));
        $this->assertStringContainsString('name="eviden_id"', file_get_contents($base.'resources/views/perawatan/show.blade.php'));
        $this->assertStringContainsString('name="perawatan_rutin_id"', file_get_contents($base.'resources/views/eviden/create.blade.php'));
    }
}
