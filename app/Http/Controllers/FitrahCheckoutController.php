<?php

namespace App\Http\Controllers;

use App\Models\TransaksiZakat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FitrahCheckoutController extends Controller
{
    public function index(): View
    {
        TransaksiZakat::expirePending();

        $beras = DB::table('katalog_beras')
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->select('katalog_beras.id', 'katalog_beras.nama_beras', 'katalog_beras.harga_per_kg', 'katalog_beras.stock')
            ->selectSub(function ($query): void {
                $query->from('reservasi_stok')
                    ->join('lot_beras', 'lot_beras.id', '=', 'reservasi_stok.lot_beras_id')
                    ->whereColumn('lot_beras.katalog_beras_id', 'katalog_beras.id')
                    ->where('reservasi_stok.status', 'active')
                    ->selectRaw('COALESCE(SUM(reservasi_stok.jumlah_kg), 0)');
            }, 'reservasi_kg')
            ->get()
            ->map(function ($item) {
                $item->tersedia_kg = bcsub($item->stock, $item->reservasi_kg, 2);

                return $item;
            })
            ->filter(fn ($item) => bccomp($item->tersedia_kg, '0', 2) > 0)
            ->sortBy('harga_per_kg', SORT_NUMERIC)
            ->values();

        $panti = DB::table('yayasan')
            ->join('wilayah_layanan', 'wilayah_layanan.id', '=', 'yayasan.wilayah_layanan_id')
            ->where('yayasan.status_verif', 'verified')
            ->where('yayasan.is_active', true)
            ->where('yayasan.jumlah_mustahik_layak', '>', 0)
            ->whereNotNull('yayasan.kapasitas_penerimaan_kg')
            ->whereNotNull('yayasan.jarak_jalan_km')
            ->where('wilayah_layanan.is_active', true)
            ->where('wilayah_layanan.batas_checkout_at', '>', now())
            ->orderBy('yayasan.provinsi')
            ->orderBy('yayasan.kota_kabupaten')
            ->get(['yayasan.id', 'yayasan.nama', 'yayasan.kota_kabupaten', 'yayasan.provinsi', 'yayasan.jumlah_mustahik_layak', 'yayasan.jarak_jalan_km', 'yayasan.kapasitas_penerimaan_kg', 'yayasan.kebutuhan_saat_ini']);

        $fitrahMasuk = DB::table('transaksi_zakat')
            ->join('transaksi_fitrah', 'transaksi_fitrah.transaksi_id', '=', 'transaksi_zakat.id')
            ->where('transaksi_zakat.status_pembayaran', 'simulated_paid')
            ->selectRaw('transaksi_zakat.yayasan_id, SUM(transaksi_fitrah.total_beras_kg) AS jumlah')
            ->groupBy('transaksi_zakat.yayasan_id')
            ->pluck('jumlah', 'yayasan_id');
        $fitrahSelesai = DB::table('penyaluran')
            ->where('jenis_zakat', 'fitrah')
            ->where('status', 'selesai')
            ->selectRaw('yayasan_id, SUM(jumlah) AS jumlah')
            ->groupBy('yayasan_id')
            ->pluck('jumlah', 'yayasan_id');
        $panti->each(function ($item) use ($fitrahMasuk, $fitrahSelesai): void {
            $item->fitrah_masuk = $fitrahMasuk[$item->id] ?? '0.00';
            $item->fitrah_selesai = $fitrahSelesai[$item->id] ?? '0.00';
        });

        $bobot = DB::table('pengaturan_operasional')->where('kunci', 'bobot_fitrah_per_jiwa_kg')->value('nilai');

        return view('muzakki.fitrah', compact('beras', 'panti', 'bobot'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'jumlah_jiwa' => ['required', 'integer', 'min:1', 'max:50'],
            'katalog_beras_id' => ['required', 'integer', Rule::exists('katalog_beras', 'id')],
            'yayasan_id' => ['required', 'integer', Rule::exists('yayasan', 'id')],
            'catatan_doa' => ['nullable', 'string', 'max:1000'],
        ]);

        TransaksiZakat::expirePending();

        $id = DB::transaction(function () use ($request, $data): int {
            $produk = DB::table('katalog_beras')->where('id', $data['katalog_beras_id'])->lockForUpdate()->first();
            if (! $produk || ! $produk->is_active) {
                throw ValidationException::withMessages(['katalog_beras_id' => 'Beras tidak tersedia. Pilih jenis beras lain.']);
            }

            $tujuan = DB::table('yayasan')
                ->join('wilayah_layanan', 'wilayah_layanan.id', '=', 'yayasan.wilayah_layanan_id')
                ->where('yayasan.id', $data['yayasan_id'])
                ->where('yayasan.status_verif', 'verified')
                ->where('yayasan.is_active', true)
                ->where('yayasan.jumlah_mustahik_layak', '>', 0)
                ->whereNotNull('yayasan.kapasitas_penerimaan_kg')
                ->whereNotNull('yayasan.jarak_jalan_km')
                ->where('wilayah_layanan.is_active', true)
                ->where('wilayah_layanan.batas_checkout_at', '>', now())
                ->first(['yayasan.id', 'yayasan.jarak_jalan_km', 'yayasan.kapasitas_penerimaan_kg', 'wilayah_layanan.id as wilayah_id']);
            if (! $tujuan) {
                throw ValidationException::withMessages(['yayasan_id' => 'Panti belum dapat menerima pesanan fitrah. Pilih panti lain.']);
            }

            $tarif = DB::table('tarif_pengiriman')
                ->where('wilayah_layanan_id', $tujuan->wilayah_id)
                ->where('berlaku_mulai', '<=', now())
                ->where(function ($query): void {
                    $query->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>', now());
                })
                ->latest('id')
                ->first();
            if (! $tarif) {
                throw ValidationException::withMessages(['yayasan_id' => 'Tarif pengiriman wilayah panti belum tersedia. Pilih panti lain atau hubungi panitia.']);
            }

            $pengaturan = DB::table('pengaturan_operasional')
                ->whereIn('kunci', ['bobot_fitrah_per_jiwa_kg', 'persen_keuntungan_beras', 'biaya_admin_rupiah'])
                ->pluck('nilai', 'kunci');
            abort_unless($pengaturan->count() === 3, 503, 'Pengaturan harga belum lengkap.');

            $berat = bcmul((string) $data['jumlah_jiwa'], $pengaturan['bobot_fitrah_per_jiwa_kg'], 2);
            if (bccomp($berat, $tujuan->kapasitas_penerimaan_kg, 2) > 0) {
                $kapasitas = number_format((float) $tujuan->kapasitas_penerimaan_kg, 2, ',', '.');
                $kebutuhan = number_format((float) $berat, 2, ',', '.');
                $maksimalJiwa = (int) bcdiv($tujuan->kapasitas_penerimaan_kg, $pengaturan['bobot_fitrah_per_jiwa_kg'], 0);

                throw ValidationException::withMessages([
                    'jumlah_jiwa' => "Pesanan membutuhkan {$kebutuhan} kg beras, sedangkan kapasitas panti hanya {$kapasitas} kg per kunjungan. Kurangi jumlah jiwa menjadi maksimal {$maksimalJiwa} atau pilih panti lain.",
                ]);
            }
            $sisa = $berat;
            $pokokEmpatDesimal = '0';
            $alokasiLot = [];

            $lot = DB::table('lot_beras')
                ->where('katalog_beras_id', $produk->id)
                ->where('sisa_kg', '>', 0)
                ->orderBy('tanggal_masuk')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'sisa_kg', 'harga_beli_per_kg']);
            $terpesan = DB::table('reservasi_stok')
                ->whereIn('lot_beras_id', $lot->pluck('id'))
                ->where('status', 'active')
                ->selectRaw('lot_beras_id, SUM(jumlah_kg) as jumlah_kg')
                ->groupBy('lot_beras_id')
                ->pluck('jumlah_kg', 'lot_beras_id');

            foreach ($lot as $item) {
                $tersedia = bcsub($item->sisa_kg, $terpesan[$item->id] ?? '0', 2);
                if (bccomp($tersedia, '0', 2) <= 0) {
                    continue;
                }

                $ambil = bccomp($tersedia, $sisa, 2) < 0 ? $tersedia : $sisa;
                $alokasiLot[] = ['lot_id' => $item->id, 'jumlah_kg' => $ambil, 'harga_beli_per_kg' => $item->harga_beli_per_kg];
                $pokokEmpatDesimal = bcadd($pokokEmpatDesimal, bcmul($ambil, $item->harga_beli_per_kg, 4), 4);
                $sisa = bcsub($sisa, $ambil, 2);

                if (bccomp($sisa, '0', 2) === 0) {
                    break;
                }
            }

            if (bccomp($sisa, '0', 2) > 0) {
                throw ValidationException::withMessages(['katalog_beras_id' => 'Stok beras tersedia tidak cukup untuk jumlah jiwa ini.']);
            }

            $pokok = bcadd($pokokEmpatDesimal, '0.005', 2);
            $keuntungan = bcadd(bcdiv(bcmul($pokok, $pengaturan['persen_keuntungan_beras'], 4), '100', 4), '0.005', 2);
            $berasTagihan = bcadd($pokok, $keuntungan, 2);
            $ongkirEmpatDesimal = bcadd($tarif->biaya_tetap, bcmul(bcmul($tarif->biaya_per_km_kg, $tujuan->jarak_jalan_km, 4), $berat, 4), 4);
            $ongkir = bcadd($ongkirEmpatDesimal, '0.5', 0).'.00';
            $admin = bcadd($pengaturan['biaya_admin_rupiah'], '0', 2);
            $total = bcadd(bcadd($berasTagihan, $admin, 2), $ongkir, 2);
            $kedaluwarsa = now()->addMinutes(30);
            $token = Str::random(64);

            $transaksiId = DB::table('transaksi_zakat')->insertGetId([
                'kode_transaksi' => 'ZF-'.Str::ulid(),
                'user_id' => $request->user()->id,
                'yayasan_id' => $tujuan->id,
                'jenis_zakat' => 'fitrah',
                'harga_pokok_snapshot' => $pokok,
                'keuntungan_snapshot' => $keuntungan,
                'biaya_admin_snapshot' => $admin,
                'ongkir_snapshot' => $ongkir,
                'total_nominal' => $total,
                'mode_pembayaran' => 'simulation',
                'metode_pembayaran' => 'QR simulasi',
                'status_pembayaran' => 'pending',
                'status_penyaluran' => 'menunggu_alokasi',
                'qr_payload' => Crypt::encryptString($token),
                'catatan_doa' => $data['catatan_doa'] ?? null,
                'expired_at' => $kedaluwarsa,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('transaksi_fitrah')->insert([
                'transaksi_id' => $transaksiId,
                'katalog_beras_id' => $produk->id,
                'nama_beras_snapshot' => $produk->nama_beras,
                'harga_per_kg_snapshot' => bcdiv($berasTagihan, $berat, 2),
                'bobot_per_jiwa_kg' => $pengaturan['bobot_fitrah_per_jiwa_kg'],
                'jumlah_jiwa' => $data['jumlah_jiwa'],
                'total_beras_kg' => $berat,
                'total_biaya_fitrah' => $berasTagihan,
                'jarak_jalan_km_snapshot' => $tujuan->jarak_jalan_km,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($alokasiLot as $item) {
                DB::table('reservasi_stok')->insert([
                    'transaksi_id' => $transaksiId,
                    'lot_beras_id' => $item['lot_id'],
                    'jumlah_kg' => $item['jumlah_kg'],
                    'harga_beli_per_kg_snapshot' => $item['harga_beli_per_kg'],
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('percobaan_pembayaran')->insert([
                'transaksi_id' => $transaksiId,
                'token_hash' => hash('sha256', $token),
                'status' => 'pending',
                'expired_at' => $kedaluwarsa,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $transaksiId;
        }, attempts: 3);

        return redirect()->route('muzakki.payment.show', $id);
    }
}
