<?php

namespace Tests\Feature;

use App\Models\OperasionalLog;
use Illuminate\Http\Request;
use Tests\TestCase;

/** Butuh aplikasi ter-boot, tetapi TIDAK menyentuh database. */
class WiringTest extends TestCase
{
    private function routeName(string $uri, string $method = 'GET'): ?string
    {
        return app('router')->getRoutes()->match(Request::create($uri, $method))->getName();
    }

    public function test_route_statis_tidak_tertelan_wildcard(): void
    {
        $this->assertSame('pemancar.create', $this->routeName('/pemancar/create'));
        $this->assertSame('operasional.backfill.create', $this->routeName('/operasional/isi-susulan'));
        $this->assertSame('jadwal.export', $this->routeName('/jadwal/export'));
    }

    public function test_status_off_ditandai_dan_scope_on_only_memfilter_status(): void
    {
        $this->assertTrue((new OperasionalLog(['status' => 'off']))->isOff());
        $this->assertFalse((new OperasionalLog(['status' => 'on']))->isOff());
        $sql = OperasionalLog::query()->onOnly()->toSql();
        $this->assertStringContainsString('status', $sql);
    }
}
