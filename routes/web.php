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

Route::get('/login',  [LoginController::class,'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class,'login'])->name('login.post');
Route::post('/logout',[LoginController::class,'logout'])->name('logout');

Route::middleware(['auth'])->group(function(){
    Route::get('/',         [DashboardController::class,'index'])->name('dashboard');
    Route::get('/dashboard',[DashboardController::class,'index']);

    // Pemancar
    Route::get('/pemancar',        [PemancarController::class,'index'])->name('pemancar.index');
    Route::get('/pemancar/create', [PemancarController::class,'create'])->middleware('App\Http\Middleware\AdminOnly')->name('pemancar.create');
    Route::post('/pemancar',       [PemancarController::class,'store'])->middleware('App\Http\Middleware\AdminOnly')->name('pemancar.store');
    Route::get('/pemancar/{pemancar}',      [PemancarController::class,'show'])->name('pemancar.show');
    Route::get('/pemancar/{pemancar}/edit', [PemancarController::class,'edit'])->middleware('App\Http\Middleware\AdminOnly')->name('pemancar.edit');
    Route::put('/pemancar/{pemancar}',      [PemancarController::class,'update'])->middleware('App\Http\Middleware\AdminOnly')->name('pemancar.update');
    Route::delete('/pemancar/{pemancar}',   [PemancarController::class,'destroy'])->middleware('App\Http\Middleware\AdminOnly')->name('pemancar.destroy');

    // Operasional
    Route::get('/operasional',             [OperasionalController::class,'index'])->name('operasional.index');
    Route::get('/operasional/create',      [OperasionalController::class,'create'])->name('operasional.create');
    Route::post('/operasional',            [OperasionalController::class,'store'])->name('operasional.store');
    Route::post('/operasional/hitung-vswr',[OperasionalController::class,'hitungVswr'])->name('operasional.vswr');
    Route::get('/operasional/{operasional}',      [OperasionalController::class,'show'])->name('operasional.show');
    Route::get('/operasional/{operasional}/edit', [OperasionalController::class,'edit'])->name('operasional.edit');
    Route::put('/operasional/{operasional}',      [OperasionalController::class,'update'])->name('operasional.update');
    Route::delete('/operasional/{operasional}',   [OperasionalController::class,'destroy'])->name('operasional.destroy');

    // Eviden — static routes SEBELUM parameter
    Route::get('/eviden',        [EvidenController::class,'index'])->name('eviden.index');
    Route::get('/eviden/create', [EvidenController::class,'create'])->name('eviden.create');
    Route::post('/eviden',       [EvidenController::class,'store'])->name('eviden.store');
    Route::get('/eviden/{eviden}/cetak',  [EvidenController::class,'cetak'])->name('eviden.cetak');
    Route::get('/eviden/{eviden}',        [EvidenController::class,'show'])->name('eviden.show');
    Route::get('/eviden/{eviden}/edit',   [EvidenController::class,'edit'])->name('eviden.edit');
    Route::put('/eviden/{eviden}',        [EvidenController::class,'update'])->name('eviden.update');
    Route::delete('/eviden/{eviden}',     [EvidenController::class,'destroy'])->name('eviden.destroy');

    // Laporan
    Route::get('/laporan',           [LaporanController::class,'index'])->name('laporan.index');
    Route::post('/laporan/generate', [LaporanController::class,'generate'])->name('laporan.generate');
    Route::get('/laporan/suhu',      [LaporanController::class,'suhuBulanan'])->name('laporan.suhu');
    Route::get('/laporan/suhu/pdf',  [LaporanController::class,'suhuPdf'])->name('laporan.suhu.pdf');

    // Admin only
    Route::middleware(['App\Http\Middleware\AdminOnly'])->group(function(){
        Route::get('/jadwal',          [JadwalController::class,'index'])->name('jadwal.index');
        Route::post('/jadwal',         [JadwalController::class,'store'])->name('jadwal.store');
        Route::post('/jadwal/bulanan', [JadwalController::class,'storeBulanan'])->name('jadwal.bulanan');
        Route::delete('/jadwal/{jadwal}',[JadwalController::class,'destroy'])->name('jadwal.destroy');

        Route::get('/users',             [UserController::class,'index'])->name('users.index');
        Route::get('/users/create',      [UserController::class,'create'])->name('users.create');
        Route::post('/users',            [UserController::class,'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class,'edit'])->name('users.edit');
        Route::put('/users/{user}',      [UserController::class,'update'])->name('users.update');
        Route::delete('/users/{user}',   [UserController::class,'destroy'])->name('users.destroy');

        Route::get('/setting',             [SettingController::class,'index'])->name('setting.index');
        Route::post('/setting',            [SettingController::class,'update'])->name('setting.update');
        Route::post('/setting/update-log', [SettingController::class,'addUpdateLog'])->name('setting.update-log');
        Route::post('/setting/kredit',     [SettingController::class,'updateKredit'])->name('setting.kredit');
    });
});
