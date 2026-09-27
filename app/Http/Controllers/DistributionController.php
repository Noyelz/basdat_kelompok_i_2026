<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DistributionController extends Controller
{
    public function index(): View
    {
        $gudang = DB::table('gudang')->where('is_active', true)->get(['id', 'nama']);
        $pesananFitrah = DB::table('transaksi_zakat')
            ->join('transaksi_fitrah', 'transaksi_fitrah.transaksi_id', '=', 'transaksi_zakat.id')
            ->join('yayasan', 'yayasan.id', '=', 'transaksi_zakat.yayasan_id')
            ->where('transaksi_zakat.jenis_zakat', 'fitrah')
            ->where('transaksi_zakat.status_pembayaran', 'simulated_paid')
            ->where('transaksi_zakat.status_penyaluran', 'menunggu_alokasi')
            ->orderBy('transaksi_zakat.id')
            ->get(['transaksi_zakat.id', 'transaksi_zakat.kode_transaksi', 'transaksi_zakat.yayasan_id', 'transaksi_fitrah.total_beras_kg', 'yayasan.nama as nama_panti']);
        $pesananMaal = DB::table('transaksi_zakat')
            ->join('yayasan', 'yayasan.id', '=', 'transaksi_zakat.yayasan_id')
            ->where('transaksi_zakat.jenis_zakat', 'mal')
            ->where('transaksi_zakat.status_pembayaran', 'simulated_paid')
            ->where('transaksi_zakat.status_penyaluran', 'menunggu_alokasi')
            ->orderBy('transaksi_zakat.id')
            ->get(['transaksi_zakat.id', 'transaksi_zakat.kode_transaksi', 'transaksi_zakat.total_nominal', 'yayasan.nama as nama_panti']);
        $perjalanan = DB::table('perjalanan_pengiriman')->orderByDesc('id')->limit(30)->get();
        $titik = DB::table('titik_pengiriman')
            ->join('yayasan', 'yayasan.id', '=', 'titik_pengiriman.yayasan_id')
            ->whereIn('titik_pengiriman.perjalanan_pengiriman_id', $perjalanan->pluck('id'))
            ->orderBy('titik_pengiriman.urutan')
            ->get(['titik_pengiriman.*', 'yayasan.nama as nama_panti'])
            ->groupBy('perjalanan_pengiriman_id');

        return view('panitia.distribusi', compact('gudang', 'pesananFitrah', 'pesananMaal', 'perjalanan', 'titik'));
    }

    public function createTrip(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'gudang_id' => ['required', 'integer', Rule::exists('gudang', 'id')->where('is_active', true)],
            'kendaraan' => ['required', 'string', 'max:100'],
            'kapasitas_kg' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'jadwal_berangkat' => ['required', 'date'],
        ]);
        $jadwal = CarbonImmutable::parse($data['jadwal_berangkat'], 'Asia/Jakarta')->utc();
        abort_if($jadwal->isPast(), 422, 'Jadwal berangkat harus di masa depan.');

        DB::table('perjalanan_pengiriman')->insert([
            'kode_perjalanan' => 'KIRIM-'.Str::ulid(),
            'gudang_id' => $data['gudang_id'],
            'panitia_id' => $request->user()->id,
            'kendaraan' => $data['kendaraan'],
            'kapasitas_kg' => $data['kapasitas_kg'],
            'jadwal_berangkat' => $jadwal,
            'status' => 'direncanakan',
            'is_simulation' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Perjalanan simulasi dibuat. Tambahkan tujuan dan pesanan sebelum berangkat.');
    }

    public function addStop(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'perjalanan_id' => ['required', 'integer', Rule::exists('perjalanan_pengiriman', 'id')],
            'yayasan_id' => ['required', 'integer', Rule::exists('yayasan', 'id')],
            'transaksi_ids' => ['required', 'array', 'min:1', 'max:100'],
            'transaksi_ids.*' => ['required', 'integer', Rule::exists('transaksi_zakat', 'id')],
        ]);
        $ids = collect($data['transaksi_ids'])->unique()->sort()->values();
        if ($ids->count() !== count($data['transaksi_ids'])) {
            throw ValidationException::withMessages(['transaksi_ids' => 'Satu pesanan hanya boleh dipilih sekali.']);
        }

        DB::transaction(function () use ($data, $ids): void {
            $perjalanan = DB::table('perjalanan_pengiriman')->where('id', $data['perjalanan_id'])->lockForUpdate()->first();
            abort_unless($perjalanan && $perjalanan->status === 'direncanakan', 422, 'Perjalanan tidak dapat diubah.');
            $panti = DB::table('yayasan')
                ->join('wilayah_layanan', 'wilayah_layanan.id', '=', 'yayasan.wilayah_layanan_id')
                ->where('yayasan.id', $data['yayasan_id'])
                ->where('yayasan.status_verif', 'verified')
                ->where('yayasan.is_active', true)
                ->where('wilayah_layanan.gudang_id', $perjalanan->gudang_id)
                ->first(['yayasan.id']);
            abort_unless($panti, 422, 'Panti harus aktif dan berada pada rute gudang perjalanan.');
            abort_if(DB::table('titik_pengiriman')->where('perjalanan_pengiriman_id', $perjalanan->id)->where('yayasan_id', $data['yayasan_id'])->exists(), 422, 'Panti sudah menjadi tujuan perjalanan ini.');

            $pesanan = DB::table('transaksi_zakat')
                ->join('transaksi_fitrah', 'transaksi_fitrah.transaksi_id', '=', 'transaksi_zakat.id')
                ->whereIn('transaksi_zakat.id', $ids)
                ->orderBy('transaksi_zakat.id')
                ->lockForUpdate()
                ->get(['transaksi_zakat.id', 'transaksi_zakat.yayasan_id', 'transaksi_zakat.status_pembayaran', 'transaksi_zakat.status_penyaluran', 'transaksi_fitrah.total_beras_kg']);
            abort_unless($pesanan->count() === $ids->count(), 422, 'Ada pesanan fitrah yang tidak ditemukan.');

            $jumlah = '0.00';
            foreach ($pesanan as $item) {
                abort_unless($item->yayasan_id === (int) $data['yayasan_id'] && $item->status_pembayaran === 'simulated_paid' && $item->status_penyaluran === 'menunggu_alokasi', 422, 'Pesanan harus dibayar simulasi, belum dialokasikan, dan menuju panti yang sama.');
                abort_if(DB::table('alokasi_transaksi')->where('transaksi_id', $item->id)->exists(), 422, 'Pesanan sudah dialokasikan.');
                $jumlah = bcadd($jumlah, $item->total_beras_kg, 2);
            }

            $terpakai = DB::table('titik_pengiriman')->where('perjalanan_pengiriman_id', $perjalanan->id)->sum('jumlah_kg');
            abort_if(bccomp(bcadd((string) $terpakai, $jumlah, 2), $perjalanan->kapasitas_kg, 2) > 0, 422, 'Muatan melebihi kapasitas kendaraan.');
            $urutan = (int) DB::table('titik_pengiriman')->where('perjalanan_pengiriman_id', $perjalanan->id)->max('urutan') + 1;

            $titikId = DB::table('titik_pengiriman')->insertGetId([
                'perjalanan_pengiriman_id' => $perjalanan->id,
                'yayasan_id' => $data['yayasan_id'],
                'urutan' => $urutan,
                'jumlah_kg' => $jumlah,
                'status' => 'dijadwalkan',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($pesanan as $item) {
                DB::table('alokasi_transaksi')->insert([
                    'transaksi_id' => $item->id,
                    'yayasan_id' => $data['yayasan_id'],
                    'titik_pengiriman_id' => $titikId,
                    'jumlah_kg' => $item->total_beras_kg,
                    'status' => 'dijadwalkan',
                    'is_simulation' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('transaksi_zakat')->where('id', $item->id)->update(['status_penyaluran' => 'dijadwalkan', 'updated_at' => now()]);
            }
        });

        return back()->with('success', 'Titik pengiriman dan alokasi pesanan dibuat. Stok fisik belum berubah.');
    }

    public function allocateMaal(Request $request, int $id): RedirectResponse
    {
        DB::transaction(function () use ($id): void {
            $pesanan = DB::table('transaksi_zakat')->where('id', $id)->lockForUpdate()->first();
            abort_unless($pesanan && $pesanan->jenis_zakat === 'mal' && $pesanan->status_pembayaran === 'simulated_paid' && $pesanan->status_penyaluran === 'menunggu_alokasi', 422);
            abort_if(DB::table('alokasi_transaksi')->where('transaksi_id', $id)->exists(), 422);

            DB::table('alokasi_transaksi')->insert([
                'transaksi_id' => $id,
                'yayasan_id' => $pesanan->yayasan_id,
                'nominal' => $pesanan->total_nominal,
                'status' => 'menunggu_bukti',
                'is_simulation' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('transaksi_zakat')->where('id', $id)->update(['status_penyaluran' => 'menunggu_bukti', 'updated_at' => now()]);
        });

        return back()->with('success', 'Alokasi maal uji dibuat. Panitia dan PJ perlu mengunggah bukti serah terima terpisah.');
    }

    public function dispatch(Request $request, int $id): RedirectResponse
    {
        DB::transaction(function () use ($request, $id): void {
            $perjalanan = DB::table('perjalanan_pengiriman')->where('id', $id)->lockForUpdate()->first();
            abort_unless($perjalanan && $perjalanan->status === 'direncanakan', 422, 'Perjalanan sudah diproses.');
            $titik = DB::table('titik_pengiriman')->where('perjalanan_pengiriman_id', $id)->orderBy('urutan')->get();
            abort_if($titik->isEmpty(), 422, 'Tambahkan tujuan terlebih dahulu.');
            $alokasi = DB::table('alokasi_transaksi')->whereIn('titik_pengiriman_id', $titik->pluck('id'))->orderBy('transaksi_id')->get();

            $transaksiIds = $alokasi->pluck('transaksi_id');
            $reservasi = DB::table('reservasi_stok')->whereIn('transaksi_id', $transaksiIds)->where('status', 'active')->orderBy('lot_beras_id')->get();
            $produkIds = DB::table('lot_beras')->whereIn('id', $reservasi->pluck('lot_beras_id'))->pluck('katalog_beras_id')->unique()->sort()->values();
            $produk = DB::table('katalog_beras')->whereIn('id', $produkIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $lot = DB::table('lot_beras')->whereIn('id', $reservasi->pluck('lot_beras_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($alokasi as $item) {
                $beratReservasi = $reservasi->where('transaksi_id', $item->transaksi_id)->reduce(fn ($jumlah, $baris) => bcadd($jumlah, $baris->jumlah_kg, 2), '0.00');
                abort_unless(bccomp($beratReservasi, $item->jumlah_kg, 2) === 0, 422, 'Reservasi pesanan tidak cocok dengan alokasi.');
            }

            foreach ($reservasi as $baris) {
                $itemLot = $lot[$baris->lot_beras_id] ?? null;
                abort_unless($itemLot && bccomp($itemLot->sisa_kg, $baris->jumlah_kg, 2) >= 0, 422, 'Stok lot kurang dari jumlah yang dialokasikan.');
                $itemProduk = $produk[$itemLot->katalog_beras_id];
                abort_unless(bccomp($itemProduk->stock, $baris->jumlah_kg, 2) >= 0, 422, 'Stok katalog tidak konsisten.');

                $itemLot->sisa_kg = bcsub($itemLot->sisa_kg, $baris->jumlah_kg, 2);
                $itemProduk->stock = bcsub($itemProduk->stock, $baris->jumlah_kg, 2);
                DB::table('lot_beras')->where('id', $itemLot->id)->update(['sisa_kg' => $itemLot->sisa_kg, 'updated_at' => now()]);
                DB::table('katalog_beras')->where('id', $itemProduk->id)->update(['stock' => $itemProduk->stock, 'updated_at' => now()]);
                DB::table('mutasi_stok')->insert([
                    'lot_beras_id' => $itemLot->id,
                    'dicatat_oleh' => $request->user()->id,
                    'jenis' => 'keluar_pengiriman',
                    'perubahan_kg' => '-'.$baris->jumlah_kg,
                    'saldo_setelah_kg' => $itemLot->sisa_kg,
                    'referensi' => 'perjalanan_pengiriman:'.$id,
                    'is_simulation' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('reservasi_stok')->where('id', $baris->id)->update(['status' => 'fulfilled', 'released_at' => now(), 'updated_at' => now()]);
            }

            $margin = DB::table('pengaturan_operasional')->where('kunci', 'persen_keuntungan_beras')->value('nilai');
            abort_unless($margin !== null, 503, 'Persentase keuntungan beras belum tersedia.');
            $pengali = bcadd('1', bcdiv((string) $margin, '100', 6), 6);
            foreach ($produk as $itemProduk) {
                $lotBerikut = DB::table('lot_beras')
                    ->where('katalog_beras_id', $itemProduk->id)
                    ->where('sisa_kg', '>', 0)
                    ->orderBy('tanggal_masuk')
                    ->orderBy('id')
                    ->first(['harga_beli_per_kg']);
                if (! $lotBerikut) {
                    continue;
                }

                $hargaPenawaran = bcadd(bcmul($lotBerikut->harga_beli_per_kg, $pengali, 3), '0.005', 2);
                if (bccomp($itemProduk->harga_per_kg, $hargaPenawaran, 2) !== 0) {
                    DB::table('katalog_beras')->where('id', $itemProduk->id)->update(['harga_per_kg' => $hargaPenawaran, 'updated_at' => now()]);
                    DB::table('riwayat_harga_katalog')->insert([
                        'katalog_beras_id' => $itemProduk->id,
                        'harga_pokok_per_kg' => $lotBerikut->harga_beli_per_kg,
                        'persen_keuntungan' => $margin,
                        'harga_penawaran_per_kg' => $hargaPenawaran,
                        'berlaku_mulai' => now(),
                        'ditetapkan_oleh' => $request->user()->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table('perjalanan_pengiriman')->where('id', $id)->update(['status' => 'dikirim', 'berangkat_at' => now(), 'updated_at' => now()]);
            DB::table('titik_pengiriman')->whereIn('id', $titik->pluck('id'))->update(['status' => 'dikirim', 'updated_at' => now()]);
            DB::table('alokasi_transaksi')->whereIn('id', $alokasi->pluck('id'))->update(['status' => 'dikirim', 'updated_at' => now()]);
            DB::table('transaksi_zakat')->whereIn('id', $transaksiIds)->update(['status_penyaluran' => 'dikirim', 'updated_at' => now()]);
        }, attempts: 3);

        return back()->with('success', 'Perjalanan dicatat berangkat. Stok lot berkurang dan reservasi pesanan dilepas dalam satu transaksi database.');
    }
}
