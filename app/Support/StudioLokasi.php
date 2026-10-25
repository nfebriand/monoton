<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/** Pembeda lokasi studio: Pahoman vs Studio Produksi Way Kanan. */
class StudioLokasi
{
    public const LIST  = ['Pahoman', 'Way Kanan'];
    public const LABEL = ['Pahoman' => 'Studio Pahoman', 'Way Kanan' => 'Studio Produksi Way Kanan'];
    public const COLOR = ['Pahoman' => '#0a3d62', 'Way Kanan' => '#8e44ad'];

    public static function label(?string $l): string
    {
        return self::LABEL[$l] ?? ($l ?: '-');
    }

    public static function color(?string $l): string
    {
        return self::COLOR[$l] ?? '#636e72';
    }

    /** Pengguna terbatas: personil Way Kanan non-Studio & non-Super Admin (default tampilan = Way Kanan). */
    public static function restricted(User $u): bool
    {
        return $u->isWayKanan() && !$u->isAdmin() && !$u->isDivisi('studio');
    }

    public static function default(User $u): string
    {
        return $u->isWayKanan() ? 'Way Kanan' : 'Pahoman';
    }

    /**
     * Lokasi aktif dari request. Parameter "lokasi" ada tapi kosong = Semua.
     * Tanpa parameter: personil Way Kanan terbatas -> Way Kanan; lainnya -> Semua (null).
     */
    public static function aktif(Request $r, User $u): ?string
    {
        if ($r->has('lokasi')) {
            $v = (string) $r->input('lokasi');
            return in_array($v, self::LIST, true) ? $v : null;
        }
        return self::restricted($u) ? 'Way Kanan' : null;
    }

    /** Nilai lokasi untuk simpan data: personil terbatas selalu Way Kanan. */
    public static function untukSimpan(?string $dipilih, User $u): string
    {
        if (self::restricted($u)) return 'Way Kanan';
        return in_array($dipilih, self::LIST, true) ? $dipilih : self::default($u);
    }
}
