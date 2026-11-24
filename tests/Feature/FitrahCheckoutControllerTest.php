<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\OperationalSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class FitrahCheckoutControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_excess_capacity_returns_to_form_with_explanation_and_preserved_input(): void
    {
        $this->freezeTime();
        $muzakki = User::factory()->create(['is_active' => true]);
        $data = $this->createCheckoutData($muzakki);

        $response = $this->actingAs($muzakki)->from(route('muzakki.fitrah'))
            ->post(route('muzakki.fitrah.store'), [...$data, 'jumlah_jiwa' => 50, 'catatan_doa' => 'bismillah']);

        $response->assertRedirectToRoute('muzakki.fitrah')
            ->assertSessionHasErrors([
                'jumlah_jiwa' => 'Pesanan membutuhkan 125,00 kg beras, sedangkan kapasitas panti hanya 39,85 kg per kunjungan. Kurangi jumlah jiwa menjadi maksimal 15 atau pilih panti lain.',
            ])
            ->assertSessionHasInput('jumlah_jiwa', 50)
            ->assertSessionHasInput('yayasan_id', $data['yayasan_id'])
            ->assertSessionHasInput('katalog_beras_id', $data['katalog_beras_id'])
            ->assertSessionHasInput('catatan_doa', 'bismillah');

        $this->assertNoOrderCreated();
    }

    #[TestWith(['39.85'])]
    #[TestWith(['37.50'])]
    public function test_order_within_or_equal_to_capacity_creates_order_and_stock_reservation(string $kapasitas): void
    {
        $this->freezeTime();
        $muzakki = User::factory()->create(['is_active' => true]);
        $data = $this->createCheckoutData($muzakki);
        DB::table('yayasan')->where('id', $data['yayasan_id'])->update(['kapasitas_penerimaan_kg' => $kapasitas]);

        $response = $this->actingAs($muzakki)->post(route('muzakki.fitrah.store'), [...$data, 'jumlah_jiwa' => 15]);

        $transaksiId = DB::table('transaksi_zakat')->sole()->id;
        $response->assertRedirectToRoute('muzakki.payment.show', $transaksiId)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('transaksi_zakat', [
            'id' => $transaksiId,
            'user_id' => $muzakki->id,
            'yayasan_id' => $data['yayasan_id'],
            'status_pembayaran' => 'pending',
        ]);
        $this->assertDatabaseHas('transaksi_fitrah', [
            'transaksi_id' => $transaksiId,
            'jumlah_jiwa' => 15,
            'total_beras_kg' => '37.50',
        ]);
        $this->assertDatabaseHas('reservasi_stok', ['transaksi_id' => $transaksiId, 'jumlah_kg' => '37.50', 'status' => 'active']);
        $this->assertDatabaseHas('percobaan_pembayaran', ['transaksi_id' => $transaksiId, 'status' => 'pending']);
    }

    public function test_unavailable_rice_returns_field_error_without_creating_order(): void
    {
        $this->freezeTime();
        $muzakki = User::factory()->create(['is_active' => true]);
        $data = $this->createCheckoutData($muzakki);
        DB::table('katalog_beras')->where('id', $data['katalog_beras_id'])->update(['is_active' => false]);

        $response = $this->actingAs($muzakki)->from(route('muzakki.fitrah'))
            ->post(route('muzakki.fitrah.store'), [...$data, 'jumlah_jiwa' => 1]);

        $response->assertRedirectToRoute('muzakki.fitrah')
            ->assertSessionHasErrors(['katalog_beras_id' => 'Beras tidak tersedia. Pilih jenis beras lain.']);
        $this->assertNoOrderCreated();
    }

    public function test_unavailable_destination_returns_field_error_without_creating_order(): void
    {
        $this->freezeTime();
        $muzakki = User::factory()->create(['is_active' => true]);
        $data = $this->createCheckoutData($muzakki);
        DB::table('yayasan')->where('id', $data['yayasan_id'])->update(['is_active' => false]);

        $response = $this->actingAs($muzakki)->from(route('muzakki.fitrah'))
            ->post(route('muzakki.fitrah.store'), [...$data, 'jumlah_jiwa' => 1]);

        $response->assertRedirectToRoute('muzakki.fitrah')
            ->assertSessionHasErrors(['yayasan_id' => 'Panti belum dapat menerima pesanan fitrah. Pilih panti lain.']);
        $this->assertNoOrderCreated();
    }

    public function test_missing_shipping_rate_returns_field_error_without_creating_order(): void
    {
        $this->freezeTime();
        $muzakki = User::factory()->create(['is_active' => true]);
        $data = $this->createCheckoutData($muzakki);
        DB::table('tarif_pengiriman')->update(['berlaku_sampai' => now()->subMinute()]);

        $response = $this->actingAs($muzakki)->from(route('muzakki.fitrah'))
            ->post(route('muzakki.fitrah.store'), [...$data, 'jumlah_jiwa' => 1]);

        $response->assertRedirectToRoute('muzakki.fitrah')
            ->assertSessionHasErrors(['yayasan_id' => 'Tarif pengiriman wilayah panti belum tersedia. Pilih panti lain atau hubungi panitia.']);
        $this->assertNoOrderCreated();
    }

    public function test_form_displays_capacity_and_required_weight_from_preserved_input(): void
    {
        $this->freezeTime();
        $muzakki = User::factory()->create(['is_active' => true]);
        $this->createCheckoutData($muzakki);

        $response = $this->actingAs($muzakki)->withSession(['_old_input' => ['jumlah_jiwa' => 50]])
            ->get(route('muzakki.fitrah'));

        $response->assertSee('Kapasitas per kunjungan: 39,85 kg · maksimal 15 jiwa per pesanan')
            ->assertSee('<span id="kebutuhan-beras">125,00</span>', escape: false);
    }

    private function assertNoOrderCreated(): void
    {
        $this->assertDatabaseEmpty('transaksi_zakat');
        $this->assertDatabaseEmpty('transaksi_fitrah');
        $this->assertDatabaseEmpty('reservasi_stok');
        $this->assertDatabaseEmpty('percobaan_pembayaran');
    }

    /**
     * @return array{katalog_beras_id: int, yayasan_id: int}
     */
    private function createCheckoutData(User $muzakki): array
    {
        $this->seed(OperationalSeeder::class);
        $gudangId = DB::table('gudang')->where('kode', 'SBY-PUSAT')->value('id');
        $wilayahId = DB::table('wilayah_layanan')->where('provinsi', 'Jawa Timur')->value('id');
        DB::table('wilayah_layanan')->where('id', $wilayahId)->update([
            'is_active' => true,
            'batas_checkout_at' => now()->addMonth(),
        ]);
        $yayasanId = DB::table('yayasan')->insertGetId([
            'nama' => 'bojonegoroberiman',
            'nomor_izin' => 'PANTI-TEST',
            'telp' => '081234567890',
            'email' => 'panti@example.test',
            'kota_kabupaten' => 'Kab. Bojonegoro',
            'provinsi' => 'Jawa Timur',
            'alamat_lengkap' => 'Bojonegoro',
            'pj_user_id' => User::factory()->create(['role' => 'pj'])->id,
            'status_verif' => 'verified',
            'is_active' => true,
            'jumlah_mustahik_layak' => 50,
            'wilayah_layanan_id' => $wilayahId,
            'jarak_jalan_km' => '10.00',
            'kapasitas_penerimaan_kg' => '39.85',
        ]);
        $berasId = DB::table('katalog_beras')->insertGetId([
            'nama_beras' => 'Basmati',
            'harga_per_kg' => '17850.00',
            'stock' => '200.00',
        ]);
        $vendorId = DB::table('vendor')->insertGetId(['nama' => 'Pemasok beras']);
        $pembelianId = DB::table('pembelian_beras')->insertGetId([
            'kode_pembelian' => 'BELI-TEST',
            'vendor_id' => $vendorId,
            'gudang_id' => $gudangId,
            'dicatat_oleh' => $muzakki->id,
            'tanggal_pembelian' => now()->toDateString(),
            'status' => 'diterima',
        ]);
        DB::table('lot_beras')->insert([
            'kode_lot' => 'LOT-TEST',
            'pembelian_beras_id' => $pembelianId,
            'katalog_beras_id' => $berasId,
            'jumlah_masuk_kg' => '200.00',
            'sisa_kg' => '200.00',
            'harga_beli_per_kg' => '17000.00',
            'tanggal_masuk' => now()->toDateString(),
        ]);

        return ['katalog_beras_id' => $berasId, 'yayasan_id' => $yayasanId];
    }
}
