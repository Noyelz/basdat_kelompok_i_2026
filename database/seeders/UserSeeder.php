<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun Admin / Panitia Utama
        User::updateOrCreate(
            ['email' => 'panitia@zakat.com'],
            [
                'nama'           => 'Panitia Zakat Utama',
                'telp'           => '081234567890',
                'pass_hash'      => Hash::make('password123'),
                'role'           => 'panitia',
                'provinsi'       => 'Jawa Timur',
                'kota_kabupaten' => 'Kota Surabaya',
                'alamat_lengkap' => 'Kantor Panitia Zakat Surabaya',
            ]
        );
    }
}