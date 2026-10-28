<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Arsip pejabat penandatangan yang SUDAH TIDAK menjabat.
 * Pejabat yang sedang menjabat tetap di app_settings (Pengaturan).
 * berlaku_mulai null = sejak awal pencatatan.
 */
class PejabatRiwayat extends Model
{
    protected $table = 'pejabat_riwayat';
    protected $fillable = ['kode','nama','nip','jabatan','ttd_path','berlaku_mulai','berlaku_sampai','keterangan'];
    protected $casts = ['berlaku_mulai' => 'date', 'berlaku_sampai' => 'date'];
}
