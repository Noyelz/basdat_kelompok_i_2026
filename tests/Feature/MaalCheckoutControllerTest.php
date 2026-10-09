<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MaalCheckoutControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['muzakki.dashboard', 'muzakki.dashboard'])]
    #[TestWith(['muzakki.fitrah', 'muzakki.fitrah'])]
    #[TestWith(['muzakki.maal', 'muzakki.maal'])]
    public function test_muzakki_pages_render_without_legacy_payer_profiles(string $route, string $view): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get(route($route))->assertOk()->assertViewIs($view);
    }

    public function test_checkout_stores_maal_summary_and_subject_without_legacy_profiles(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['is_active' => true]);
        $pantiId = DB::table('yayasan')->insertGetId([
            'nama' => 'Panti pengujian maal',
            'nomor_izin' => 'MAAL-TEST',
            'telp' => '081234567890',
            'email' => 'maal@example.test',
            'kota_kabupaten' => 'Kota Surabaya',
            'provinsi' => 'Jawa Timur',
            'alamat_lengkap' => 'Surabaya',
            'pj_user_id' => User::factory()->create(['role' => 'pj'])->id,
            'status_verif' => 'verified',
            'is_active' => true,
            'jumlah_mustahik_layak' => 10,
        ]);
        $hargaId = DB::table('harga_emas')->insertGetId([
            'harga_per_gram' => '1000000.00',
            'sumber' => 'Harga pengujian',
            'tanggal_sumber' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('muzakki.maal.store'), [
            'yayasan_id' => $pantiId,
            'harga_emas_id' => $hargaId,
            'subjek' => [$this->subjectData()],
        ]);

        $transaksiId = DB::table('transaksi_zakat')->sole()->id;
        $response->assertRedirectToRoute('muzakki.payment.show', $transaksiId)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('transaksi_zakat', [
            'id' => $transaksiId, 'user_id' => $user->id, 'yayasan_id' => $pantiId,
            'jenis_zakat' => 'mal', 'total_nominal' => '2500000.00', 'status_pembayaran' => 'pending',
        ]);
        $this->assertDatabaseHas('transaksi_mal', [
            'transaksi_id' => $transaksiId, 'total_harta_bersih' => '100000000.00',
            'nilai_nisab' => '85000000.00', 'total_zakat_wajib' => '2500000.00',
        ]);
        $this->assertDatabaseHas('subjek_zakat_maal', [
            'user_id' => $user->id, 'transaksi_id' => $transaksiId,
            'hubungan' => 'diri', 'is_snapshot' => true, 'zakat_wajib' => '2500000.00',
        ]);
        $this->assertDatabaseHas('percobaan_pembayaran', ['transaksi_id' => $transaksiId, 'status' => 'pending']);
        $this->assertDatabaseCount('transaksi_zakat', 1);
        $this->assertDatabaseCount('transaksi_mal', 1);
        $this->assertDatabaseCount('subjek_zakat_maal', 1);
        $this->assertDatabaseCount('audit_log', 0);
    }

    public function test_draft_is_saved_in_subjects_without_creating_an_order(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post(route('muzakki.maal.draft'), ['subjek' => [$this->subjectData()]]);

        $response->assertRedirectToRoute('muzakki.maal')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('subjek_zakat_maal', [
            'user_id' => $user->id, 'transaksi_id' => null, 'hubungan' => 'diri',
            'tabungan' => '100000000.00', 'is_snapshot' => false,
        ]);
        $this->assertDatabaseCount('subjek_zakat_maal', 1);
        $this->assertDatabaseCount('transaksi_zakat', 0);
        $this->assertDatabaseCount('transaksi_mal', 0);
        $this->assertDatabaseCount('percobaan_pembayaran', 0);
    }

    /**
     * @return array{hubungan: string, nama_tampilan: string, tabungan: string, emas_gram: string, investasi: string, aset_dagang: string, piutang: string, utang: string, sudah_haul: bool}
     */
    private function subjectData(): array
    {
        return [
            'hubungan' => 'diri', 'nama_tampilan' => 'Pemilik pengujian',
            'tabungan' => '100000000.00', 'emas_gram' => '0', 'investasi' => '0',
            'aset_dagang' => '0', 'piutang' => '0', 'utang' => '0', 'sudah_haul' => true,
        ];
    }
}
