<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationalSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ([
            ['kode' => 'BSI', 'nama' => 'Bank Syariah Indonesia'],
            ['kode' => 'BRI', 'nama' => 'Bank Rakyat Indonesia'],
            ['kode' => 'BNI', 'nama' => 'Bank Negara Indonesia'],
            ['kode' => 'MANDIRI', 'nama' => 'Bank Mandiri'],
            ['kode' => 'BCA', 'nama' => 'Bank Central Asia'],
            ['kode' => 'BTN', 'nama' => 'Bank Tabungan Negara'],
            ['kode' => 'BANK_JATIM', 'nama' => 'Bank Jatim'],
        ] as $bank) {
            DB::table('bank')->insertOrIgnore([
                ...$bank,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('gudang')->insertOrIgnore([
            'kode' => 'SBY-PUSAT',
            'nama' => 'Gudang Pusat Surabaya',
            'provinsi' => 'Jawa Timur',
            'kota_kabupaten' => 'Kota Surabaya',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $gudangId = DB::table('gudang')->where('kode', 'SBY-PUSAT')->value('id');

        foreach ([
            ['Jawa Timur', 5],
            ['Jawa Tengah', 7],
            ['DI Yogyakarta', 7],
            ['Jawa Barat', 10],
            ['DKI Jakarta', 10],
            ['Banten', 10],
        ] as [$provinsi, $hariBatas]) {
            DB::table('wilayah_layanan')->insertOrIgnore([
                'gudang_id' => $gudangId,
                'nama' => $provinsi,
                'provinsi' => $provinsi,
                'hari_batas_fitrah' => $hariBatas,
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $wilayahId = DB::table('wilayah_layanan')
                ->where('gudang_id', $gudangId)
                ->where('provinsi', $provinsi)
                ->value('id');

            if (! DB::table('tarif_pengiriman')->where('wilayah_layanan_id', $wilayahId)->exists()) {
                DB::table('tarif_pengiriman')->insert([
                    'wilayah_layanan_id' => $wilayahId,
                    'biaya_tetap' => 3000,
                    'biaya_per_km_kg' => 8,
                    'berlaku_mulai' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach ([
            ['kunci' => 'biaya_admin_rupiah', 'nilai' => '1000', 'satuan' => 'rupiah'],
            ['kunci' => 'persen_keuntungan_beras', 'nilai' => '5', 'satuan' => 'persen'],
            ['kunci' => 'bobot_fitrah_per_jiwa_kg', 'nilai' => '2.5', 'satuan' => 'kg'],
        ] as $pengaturan) {
            DB::table('pengaturan_operasional')->insertOrIgnore([
                ...$pengaturan,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
