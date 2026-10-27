<?php

namespace App\Providers;

use App\Support\Akses;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    /**
     * Semua aturan berasal dari App\Support\Akses (satu sumber kebenaran).
     * Di view:  @can('studio.perangkat.ubah', $perangkat) ... @endcan
     * Di controller: abort_unless(Gate::allows('...'), 403) atau Akses::... langsung.
     */
    public function boot(): void
    {
        Gate::define('admin-only', fn($u) => $u->isAdmin());

        // Menu / modul
        Gate::define('menu.transmisi',  fn($u) => Akses::menuTransmisi($u));
        Gate::define('menu.studio',     fn($u) => Akses::studioMasuk($u));
        Gate::define('menu.sarana',     fn($u) => Akses::menuSarana($u));
        Gate::define('menu.genset',     fn($u) => Akses::gensetMasuk($u));
        Gate::define('genset.unit.kelola', fn($u) => Akses::gensetUnitKelola($u));

        // Perawatan rutin pemancar & genset
        Gate::define('perawatan.menu',   fn($u) => Akses::perawatanMenu($u));
        Gate::define('perawatan.kelola', fn($u, $item) => Akses::perawatanKelola($u, is_string($item) ? $item : ($item->objek_tipe ?? 'pemancar')));
        Gate::define('perawatan.catat',  fn($u, $item) => Akses::perawatanCatat($u, $item->objek_tipe, $item->objek_tipe === 'pemancar' ? ($item->pemancar->lokasi ?? null) : null));

        // Studio
        Gate::define('studio.perangkat.tambah', fn($u) => Akses::perangkatTambah($u));
        Gate::define('studio.perangkat.ubah',   fn($u, $perangkat) => Akses::perangkatUbah($u, $perangkat->lokasi ?? null));
        Gate::define('studio.log.ubah',         fn($u, $log) => Akses::logStudioUbah($u, $log->user_id ?? null));
        Gate::define('studio.maintenance.hapus', fn($u) => Akses::maintenanceStudioHapus($u));

        // Genset & operasional pemancar
        Gate::define('genset.log.ubah',    fn($u, $log) => Akses::gensetLogUbah($u, $log->user_id ?? null));
        Gate::define('genset.log.hapus',   fn($u) => Akses::gensetLogHapus($u));
        Gate::define('operasional.ubah',   fn($u, $log) => Akses::operasionalUbah($u, $log->user_id ?? null));
        Gate::define('operasional.hapus',  fn($u) => Akses::operasionalHapus($u));
    }
}
