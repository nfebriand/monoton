<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PemancarController;
use App\Http\Controllers\OperasionalController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EvidenController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\GensetController;
use App\Http\Controllers\SkemaShiftController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\AsetController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\StudioLogController;
use App\Http\Controllers\StudioPerangkatController;
use App\Http\Controllers\StudioMaintenanceController;

Route::get('/login',  [LoginController::class,'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class,'login'])
    ->middleware('throttle:5,1')
    ->name('login.post');
Route::post('/logout',[LoginController::class,'logout'])->name('logout');

Route::get('/offline', fn()=>view('offline'))->name('offline');

Route::middleware(['auth'])->group(function(){
    Route::get('/',          [DashboardController::class,'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class,'index']);

    // Pemancar
    Route::get('/pemancar',                    [PemancarController::class,'index'])->name('pemancar.index');
    Route::get('/pemancar/create',             [PemancarController::class,'create'])->middleware('App\Http\Middleware\AdminOnly')->name('pemancar.create');	
    Route::get('/pemancar/{pemancar}',         [PemancarController::class,'show'])->name('pemancar.show');
    Route::post('/pemancar',                   [PemancarController::class,'store'])->middleware('App\Http\Middleware\AdminOnly')->name('pemancar.store');
    Route::get('/pemancar/{pemancar}/edit',    [PemancarController::class,'edit'])->middleware('App\Http\Middleware\AdminOnly')->name('pemancar.edit');
    Route::put('/pemancar/{pemancar}',         [PemancarController::class,'update'])->middleware('App\Http\Middleware\AdminOnly')->name('pemancar.update');
    Route::delete('/pemancar/{pemancar}',      [PemancarController::class,'destroy'])->middleware('App\Http\Middleware\AdminOnly')->name('pemancar.destroy');

    // Operasional
    Route::get('/operasional',                  [OperasionalController::class,'index'])->name('operasional.index');
    Route::get('/operasional/create',           [OperasionalController::class,'create'])->name('operasional.create');
    Route::post('/operasional',                 [OperasionalController::class,'store'])->name('operasional.store');
    Route::post('/operasional/hitung-vswr',     [OperasionalController::class,'hitungVswr'])->name('operasional.vswr');
	Route::get('/operasional/isi-susulan', 			[OperasionalController::class,'backfillCreate'])->name('operasional.backfill.create');	
	Route::post('/operasional/isi-susulan', 		[OperasionalController::class,'backfillStore'])->name('operasional.backfill.store');	
    Route::get('/operasional/{operasional}',        [OperasionalController::class,'show'])->name('operasional.show');
    Route::get('/operasional/{operasional}/edit',   [OperasionalController::class,'edit'])->name('operasional.edit');
    Route::put('/operasional/{operasional}',        [OperasionalController::class,'update'])->name('operasional.update');
    Route::delete('/operasional/{operasional}',     [OperasionalController::class,'destroy'])->name('operasional.destroy');

    // Eviden
    Route::get('/eviden',              [EvidenController::class,'index'])->name('eviden.index');
    Route::get('/eviden/create',       [EvidenController::class,'create'])->name('eviden.create');
    Route::post('/eviden',             [EvidenController::class,'store'])->name('eviden.store');
    Route::get('/eviden/{eviden}/cetak',[EvidenController::class,'cetak'])->name('eviden.cetak');
    Route::get('/eviden/{eviden}',     [EvidenController::class,'show'])->name('eviden.show');
    Route::get('/eviden/{eviden}/edit',[EvidenController::class,'edit'])->name('eviden.edit');
    Route::put('/eviden/{eviden}',     [EvidenController::class,'update'])->name('eviden.update');
    Route::delete('/eviden/{eviden}',  [EvidenController::class,'destroy'])->name('eviden.destroy');
    Route::get('/eviden-bulk-cetak',   [EvidenController::class,'bulkCetak'])->name('eviden.bulk-cetak');

    // Laporan
    Route::get('/laporan',                    [LaporanController::class,'index'])->name('laporan.index');
    Route::match(['get','post'],'/laporan/generate',[LaporanController::class,'generate'])->name('laporan.generate');
    Route::get('/laporan/suhu',               [LaporanController::class,'suhuBulanan'])->name('laporan.suhu');
    Route::get('/laporan/suhu/pdf',           [LaporanController::class,'suhuPdf'])->name('laporan.suhu.pdf');
    
    // ── BARU: Rekap Eviden per Divisi ──
    Route::get('/laporan/eviden-rekap',       [LaporanController::class,'evidenRekap'])->name('laporan.eviden-rekap');
    Route::get('/laporan/eviden-rekap/pdf',   [LaporanController::class,'evidenRekapPdf'])->name('laporan.eviden-rekap-pdf');

    // Profil Diri Sendiri & Ganti Password
    Route::get('/profile',          [UserController::class,'profile'])->name('users.profile');
    Route::get('/password/change',  [UserController::class,'changePasswordForm'])->name('users.change-password');
    Route::post('/password/change', [UserController::class,'updatePassword'])->name('users.update-password');
    
     // TTD sendiri (semua user terautentikasi)
    Route::post('/profile/ttd',   [UserController::class,'updateTtdSelf'])->name('users.update-ttd-self');
    Route::delete('/profile/ttd', [UserController::class,'hapusTtdSelf'])->name('users.hapus-ttd-self');

    // Sync Manager
    Route::get('/sync', fn()=>view('sync.index'))->name('sync.index');
    Route::prefix('api/sync')->group(function(){
        Route::get('/status',  [\App\Http\Controllers\Api\SyncController::class,'status'])->name('api.sync.status');
        Route::post('/log',    [\App\Http\Controllers\Api\SyncController::class,'syncLog'])->name('api.sync.log');
        Route::post('/eviden', [\App\Http\Controllers\Api\SyncController::class,'syncEviden'])->name('api.sync.eviden');
    });

    // Kelola Unit Genset (Admin super + Admin Divisi Sarana — dicek di controller)
    Route::get('/genset-unit',          [GensetController::class,'units'])->name('genset.units');
    Route::post('/genset-unit',         [GensetController::class,'storeUnit'])->name('genset-unit.store');
    Route::put('/genset-unit/{unit}',   [GensetController::class,'updateUnit'])->name('genset-unit.update');
    Route::delete('/genset-unit/{unit}',[GensetController::class,'destroyUnit'])->name('genset-unit.destroy');

    // Operasional Genset (Sarana)
    Route::get('/genset',               [GensetController::class,'index'])->name('genset.index');
    Route::get('/genset/create',        [GensetController::class,'create'])->name('genset.create');
    Route::post('/genset',              [GensetController::class,'store'])->name('genset.store');
    Route::get('/genset/{genset}',          [GensetController::class,'show'])->name('genset.show');
    Route::get('/genset/{genset}/edit',     [GensetController::class,'edit'])->name('genset.edit');
    Route::put('/genset/{genset}',          [GensetController::class,'update'])->name('genset.update');
    Route::delete('/genset/{genset}',       [GensetController::class,'destroy'])->name('genset.destroy');
    
    // Aset & Inventaris (Sarana)
    Route::get('/aset',              [AsetController::class,'index'])->name('aset.index');
    Route::get('/aset/create',       [AsetController::class,'create'])->name('aset.create');
    Route::post('/aset',             [AsetController::class,'store'])->name('aset.store');
    Route::get('/aset/{aset}',       [AsetController::class,'show'])->name('aset.show');
    Route::get('/aset/{aset}/edit',  [AsetController::class,'edit'])->name('aset.edit');
    Route::put('/aset/{aset}',       [AsetController::class,'update'])->name('aset.update');
    Route::delete('/aset/{aset}',    [AsetController::class,'destroy'])->name('aset.destroy');

	// Logbook Studio
    Route::get('/studio/logbook',                    [StudioLogController::class,'index'])->name('studio.logbook.index');
    Route::get('/studio/logbook/create',             [StudioLogController::class,'create'])->name('studio.logbook.create');
    Route::post('/studio/logbook',                   [StudioLogController::class,'store'])->name('studio.logbook.store');
    Route::get('/studio/logbook/bulk-cetak',   [StudioLogController::class,'bulkCetak'])->name('studio.logbook.bulk-cetak');
        Route::get('/studio/logbook/cetak-harian',       [StudioLogController::class,'cetakHarian'])->name('studio.logbook.cetak-harian');
    Route::get('/studio/logbook/cetak-bulanan',      [StudioLogController::class,'cetakBulanan'])->name('studio.logbook.cetak-bulanan');
    Route::get('/studio/logbook/{studioLog}',        [StudioLogController::class,'show'])->name('studio.logbook.show');
    Route::get('/studio/logbook/{studioLog}/edit',   [StudioLogController::class,'edit'])->name('studio.logbook.edit');
    Route::put('/studio/logbook/{studioLog}',        [StudioLogController::class,'update'])->name('studio.logbook.update');
    Route::delete('/studio/logbook/{studioLog}',     [StudioLogController::class,'destroy'])->name('studio.logbook.destroy');

    // Master Perangkat Studio
    Route::get('/studio/perangkat',                      [StudioPerangkatController::class,'index'])->name('studio.perangkat.index');
    Route::get('/studio/perangkat/create',               [StudioPerangkatController::class,'create'])->name('studio.perangkat.create');
    Route::post('/studio/perangkat',                     [StudioPerangkatController::class,'store'])->name('studio.perangkat.store');
    Route::get('/studio/perangkat/cetak-inventaris',     [StudioPerangkatController::class,'cetakInventaris'])->name('studio.perangkat.cetak-inventaris');
    Route::get('/studio/perangkat/{studioPerangkat}',    [StudioPerangkatController::class,'show'])->name('studio.perangkat.show');
    Route::get('/studio/perangkat/{studioPerangkat}/edit',[StudioPerangkatController::class,'edit'])->name('studio.perangkat.edit');
    Route::put('/studio/perangkat/{studioPerangkat}',    [StudioPerangkatController::class,'update'])->name('studio.perangkat.update');
    Route::delete('/studio/perangkat/{studioPerangkat}', [StudioPerangkatController::class,'destroy'])->name('studio.perangkat.destroy');

    // Maintenance Perangkat Studio
    Route::get('/studio/maintenance',                        [StudioMaintenanceController::class,'index'])->name('studio.maintenance.index');
    Route::get('/studio/maintenance/create',                 [StudioMaintenanceController::class,'create'])->name('studio.maintenance.create');
    Route::post('/studio/maintenance',                       [StudioMaintenanceController::class,'store'])->name('studio.maintenance.store');
    Route::get('/studio/maintenance/cetak',                  [StudioMaintenanceController::class,'cetakMaintenance'])->name('studio.maintenance.cetak');
    Route::get('/studio/maintenance/{studioMaintenance}',    [StudioMaintenanceController::class,'show'])->name('studio.maintenance.show');
    Route::delete('/studio/maintenance/{studioMaintenance}', [StudioMaintenanceController::class,'destroy'])->name('studio.maintenance.destroy');
    
    // Maintenance Aset (hanya Admin + Divisi Sarana)
    Route::middleware(['App\Http\Middleware\SaranaOrAdmin'])->group(function(){
        Route::get('/maintenance',                [MaintenanceController::class,'index'])->name('maintenance.index');
        Route::get('/maintenance/create',         [MaintenanceController::class,'create'])->name('maintenance.create');
        Route::post('/maintenance',               [MaintenanceController::class,'store'])->name('maintenance.store');
        Route::get('/maintenance/{maintenance}',  [MaintenanceController::class,'show'])->name('maintenance.show');
        Route::delete('/maintenance/{maintenance}',[MaintenanceController::class,'destroy'])->name('maintenance.destroy');
    });

    // ── Admin Divisi + Super Admin ──
    Route::middleware(['App\Http\Middleware\AdminOrDivisiAdmin'])->group(function(){
        Route::get('/jadwal',           [JadwalController::class,'index'])->name('jadwal.index');
        Route::post('/jadwal',          [JadwalController::class,'store'])->name('jadwal.store');
        Route::post('/jadwal/bulanan',  [JadwalController::class,'storeBulanan'])->name('jadwal.bulanan');
        Route::delete('/jadwal/{jadwal}',[JadwalController::class,'destroy'])->name('jadwal.destroy');

        // Kelola Skema Shift (Admin + Admin Divisi)
        Route::get('/skema-shift',                    [SkemaShiftController::class,'index'])->name('skema-shift.index');
        Route::get('/skema-shift/create',             [SkemaShiftController::class,'create'])->name('skema-shift.create');
        Route::post('/skema-shift',                   [SkemaShiftController::class,'store'])->name('skema-shift.store');
        Route::get('/skema-shift/{skemaShift}/edit',  [SkemaShiftController::class,'edit'])->name('skema-shift.edit');
        Route::put('/skema-shift/{skemaShift}',       [SkemaShiftController::class,'update'])->name('skema-shift.update');
        Route::delete('/skema-shift/{skemaShift}',    [SkemaShiftController::class,'destroy'])->name('skema-shift.destroy');
        Route::patch('/skema-shift/{skemaShift}/toggle',[SkemaShiftController::class,'toggleAktif'])->name('skema-shift.toggle');

        Route::get('/users',             [UserController::class,'index'])->name('users.index');
        Route::get('/users/create',      [UserController::class,'create'])->name('users.create');
        Route::post('/users',            [UserController::class,'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class,'edit'])->name('users.edit');
        Route::put('/users/{user}',      [UserController::class,'update'])->name('users.update');
        Route::delete('/users/{user}',   [UserController::class,'destroy'])->name('users.destroy');
    });

    // ── Super Admin Only ──
    Route::middleware(['App\Http\Middleware\AdminOnly'])->group(function(){
        Route::get('/setting',            [SettingController::class,'index'])->name('setting.index');
        Route::post('/setting',           [SettingController::class,'update'])->name('setting.update');
        Route::post('/setting/update-log',[SettingController::class,'addUpdateLog'])->name('setting.update-log');
        Route::post('/setting/kredit',    [SettingController::class,'updateKredit'])->name('setting.kredit');
        Route::post('/setting/hapus-ttd', [SettingController::class,'removeTtd'])->name('setting.remove-ttd');

        // Master Lokasi (Admin only)
        Route::get('/lokasi',             [LokasiController::class,'index'])->name('lokasi.index');
        Route::post('/lokasi',            [LokasiController::class,'store'])->name('lokasi.store');
        Route::put('/lokasi/{lokasi}',    [LokasiController::class,'update'])->name('lokasi.update');
        Route::delete('/lokasi/{lokasi}', [LokasiController::class,'destroy'])->name('lokasi.destroy');

        // genset-unit dipindah ke luar grup ini, akses dicek di GensetController::checkUnitAccess()
        
        // Kategori Aset (Admin)
        Route::get('/aset-kategori',              [AsetController::class,'kategoriIndex'])->name('aset.kategori.index');
        Route::post('/aset-kategori',             [AsetController::class,'kategoriStore'])->name('aset.kategori.store');
        Route::put('/aset-kategori/{kategori}',   [AsetController::class,'kategoriUpdate'])->name('aset.kategori.update');
        Route::delete('/aset-kategori/{kategori}',[AsetController::class,'kategoriDestroy'])->name('aset.kategori.destroy');
    });
});
