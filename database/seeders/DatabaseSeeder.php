<?php
namespace Database\Seeders;
use App\Models\User;
use App\Models\Pemancar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'=>'Administrator','nip'=>'19800101001',
            'email'=>'admin@monoton.id','password'=>Hash::make('password'),
            'role'=>'admin','lokasi_dinas'=>'Gedung Air','is_active'=>true,
        ]);
        $ops=[
            ['name'=>'Ahmad Fauzi',  'nip'=>'19850101001','email'=>'op1@monoton.id','lokasi_dinas'=>'Gedung Air'],
            ['name'=>'Budi Santoso', 'nip'=>'19870202002','email'=>'op2@monoton.id','lokasi_dinas'=>'Sukarame'],
            ['name'=>'Citra Lestari','nip'=>'19900303003','email'=>'op3@monoton.id','lokasi_dinas'=>'Bakauheni'],
            ['name'=>'Dian Pratiwi', 'nip'=>'19920404004','email'=>'op4@monoton.id','lokasi_dinas'=>'Way Kanan'],
        ];
        foreach($ops as $op){
            User::create(array_merge($op,['password'=>Hash::make('password'),'role'=>'operator','is_active'=>true]));
        }
        Pemancar::insert([
            ['nama_stasiun'=>'LPPL Radio Lampung 1','merk'=>'Nautel','tipe_unit'=>'VS300',
             'tipe_komponen'=>'solid_state','modulasi'=>'FM','lokasi'=>'Gedung Air',
             'latitude'=>-5.4294,'longitude'=>105.2614,'alamat_lokasi'=>'Jl. Raden Intan No.1, Bandar Lampung',
             'kapasitas_output_final'=>300,'tipe_exciter'=>'Nautel NV10','tipe_driver'=>'Internal',
             'frekuensi'=>95.60,'nomor_izin'=>'PM/00001/2023','tanggal_instalasi'=>'2023-01-15',
             'is_active'=>true,'keterangan'=>null,'created_at'=>now(),'updated_at'=>now()],
            ['nama_stasiun'=>'LPPL Radio Lampung 2','merk'=>'BW Broadcast','tipe_unit'=>'TX1000',
             'tipe_komponen'=>'solid_state','modulasi'=>'FM','lokasi'=>'Bukit Randu',
             'latitude'=>-5.3831,'longitude'=>105.2507,'alamat_lokasi'=>'Bukit Randu, Bandar Lampung',
             'kapasitas_output_final'=>1000,'tipe_exciter'=>'BW Exciter','tipe_driver'=>'BW Driver',
             'frekuensi'=>88.80,'nomor_izin'=>'PM/00002/2023','tanggal_instalasi'=>'2020-06-10',
             'is_active'=>true,'keterangan'=>null,'created_at'=>now(),'updated_at'=>now()],
        ]);
        $this->call(SettingSeeder::class);
    }
}
