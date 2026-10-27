<?php

namespace Tests\Unit;

use App\Support\Preflight;
use PHPUnit\Framework\TestCase;

/** Menjaga seluruh view repo: UTF-8 valid & rujukan @include/@extends mengarah ke file yang ada. */
class PreflightViewsTest extends TestCase
{
    public function test_semua_view_valid(): void
    {
        $temuan = array_filter(Preflight::views(dirname(__DIR__, 2)), fn ($t) => $t['level'] === 'error');
        $this->assertSame([], array_values(array_map(fn ($t) => $t['pesan'], $temuan)));
    }
}
