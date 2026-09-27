<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $vendor = DB::table('vendor')->orderBy('nama')->get(['id', 'nama', 'is_active']);
        $gudang = DB::table('gudang')->where('is_active', true)->orderBy('nama')->get(['id', 'nama']);
        $katalog = DB::table('katalog_beras')
            ->select('katalog_beras.*')
            ->selectSub(function ($query): void {
                $query->from('reservasi_stok')
                    ->join('lot_beras', 'lot_beras.id', '=', 'reservasi_stok.lot_beras_id')
                    ->whereColumn('lot_beras.katalog_beras_id', 'katalog_beras.id')
                    ->where('reservasi_stok.status', 'active')
                    ->selectRaw('COALESCE(SUM(reservasi_stok.jumlah_kg), 0)');
            }, 'reservasi_kg')
            ->orderBy('nama_beras')
            ->get();

        $lot = DB::table('lot_beras')
            ->join('katalog_beras', 'katalog_beras.id', '=', 'lot_beras.katalog_beras_id')
            ->join('pembelian_beras', 'pembelian_beras.id', '=', 'lot_beras.pembelian_beras_id')
            ->join('vendor', 'vendor.id', '=', 'pembelian_beras.vendor_id')
            ->orderByDesc('lot_beras.id')
            ->limit(30)
            ->get(['lot_beras.*', 'katalog_beras.nama_beras', 'vendor.nama as nama_vendor']);

        $koreksi = DB::table('mutasi_stok')
            ->join('lot_beras', 'lot_beras.id', '=', 'mutasi_stok.lot_beras_id')
            ->join('user', 'user.id', '=', 'mutasi_stok.dicatat_oleh')
            ->where('mutasi_stok.jenis', 'koreksi_stok')
            ->orderByDesc('mutasi_stok.id')
            ->limit(20)
            ->get(['mutasi_stok.*', 'lot_beras.kode_lot', 'user.nama as nama_panitia']);

        return view('panitia.gudang', compact('vendor', 'gudang', 'katalog', 'lot', 'koreksi'));
    }

    public function storeVendor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'telp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:191'],
            'alamat' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::table('vendor')->insert([
            ...$data,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Vendor ditambahkan.');
    }

    public function storeKatalog(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_beras' => ['required', 'string', 'max:100', Rule::unique('katalog_beras', 'nama_beras')],
        ]);

        DB::table('katalog_beras')->insert([
            'nama_beras' => $data['nama_beras'],
            'harga_per_kg' => 0,
            'stock' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Jenis beras ditambahkan. Harga muncul setelah stok pertama diterima.');
    }

    public function receive(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'integer', Rule::exists('vendor', 'id')->where('is_active', true)],
            'gudang_id' => ['required', 'integer', Rule::exists('gudang', 'id')->where('is_active', true)],
            'katalog_beras_id' => ['required', 'integer', Rule::exists('katalog_beras', 'id')->where('is_active', true)],
            'jumlah_kg' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'harga_beli_per_kg' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999999.99'],
            'tanggal_masuk' => ['required', 'date'],
            'tanggal_kedaluwarsa' => ['nullable', 'date', 'after:tanggal_masuk'],
            'nomor_faktur' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $produk = DB::table('katalog_beras')->where('id', $data['katalog_beras_id'])->lockForUpdate()->first();
            abort_unless($produk && $produk->is_active, 422);
            abort_unless(DB::table('vendor')->where('id', $data['vendor_id'])->where('is_active', true)->exists(), 422);
            abort_unless(DB::table('gudang')->where('id', $data['gudang_id'])->where('is_active', true)->exists(), 422);

            $pembelianId = DB::table('pembelian_beras')->insertGetId([
                'kode_pembelian' => 'BELI-'.Str::ulid(),
                'vendor_id' => $data['vendor_id'],
                'gudang_id' => $data['gudang_id'],
                'dicatat_oleh' => $request->user()->id,
                'tanggal_pembelian' => $data['tanggal_masuk'],
                'nomor_faktur' => $data['nomor_faktur'] ?? null,
                'status' => 'diterima',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $lotId = DB::table('lot_beras')->insertGetId([
                'kode_lot' => 'LOT-'.Str::ulid(),
                'pembelian_beras_id' => $pembelianId,
                'katalog_beras_id' => $produk->id,
                'jumlah_masuk_kg' => $data['jumlah_kg'],
                'sisa_kg' => $data['jumlah_kg'],
                'harga_beli_per_kg' => $data['harga_beli_per_kg'],
                'tanggal_masuk' => $data['tanggal_masuk'],
                'tanggal_kedaluwarsa' => $data['tanggal_kedaluwarsa'] ?? null,
                'is_simulation' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('mutasi_stok')->insert([
                'lot_beras_id' => $lotId,
                'dicatat_oleh' => $request->user()->id,
                'jenis' => 'masuk_pembelian',
                'perubahan_kg' => $data['jumlah_kg'],
                'saldo_setelah_kg' => $data['jumlah_kg'],
                'referensi' => 'pembelian_beras:'.$pembelianId,
                'is_simulation' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('katalog_beras')->where('id', $produk->id)->update([
                'stock' => bcadd($produk->stock, $data['jumlah_kg'], 2),
                'updated_at' => now(),
            ]);
            $this->refreshCatalogPrice($produk, $request->user()->id);
        });

        return back()->with('success', 'Beras diterima dan lot, stok, mutasi, serta harga katalog diperbarui.');
    }

    public function adjust(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'perubahan_kg' => ['required', 'numeric', 'decimal:0,2', 'between:-9999999999.99,9999999999.99'],
            'alasan' => ['required', 'string', 'max:2000'],
        ]);
        abort_if(bccomp($data['perubahan_kg'], '0', 2) === 0, 422, 'Koreksi tidak boleh nol.');

        DB::transaction(function () use ($request, $id, $data): void {
            $lotAwal = DB::table('lot_beras')->where('id', $id)->first(['katalog_beras_id']);
            abort_unless($lotAwal, 404);
            $produk = DB::table('katalog_beras')->where('id', $lotAwal->katalog_beras_id)->lockForUpdate()->first();
            $lot = DB::table('lot_beras')->where('id', $id)->lockForUpdate()->first();
            abort_unless($produk && $lot && $lot->katalog_beras_id === $produk->id, 404);

            $terpesan = DB::table('reservasi_stok')
                ->where('lot_beras_id', $lot->id)
                ->where('status', 'active')
                ->selectRaw('COALESCE(SUM(jumlah_kg), 0) as jumlah')
                ->value('jumlah');
            $sisaBaru = bcadd($lot->sisa_kg, $data['perubahan_kg'], 2);
            $stokBaru = bcadd($produk->stock, $data['perubahan_kg'], 2);
            abort_if(bccomp($sisaBaru, (string) $terpesan, 2) < 0, 422, 'Koreksi akan mengurangi stok di bawah jumlah yang sudah dipesan.');
            abort_if(bccomp($sisaBaru, '9999999999.99', 2) > 0 || bccomp($stokBaru, '9999999999.99', 2) > 0, 422, 'Koreksi melampaui kapasitas angka stok.');
            abort_if(bccomp($stokBaru, '0', 2) < 0, 422, 'Stok katalog tidak boleh negatif.');

            DB::table('lot_beras')->where('id', $id)->update(['sisa_kg' => $sisaBaru, 'updated_at' => now()]);
            DB::table('katalog_beras')->where('id', $produk->id)->update(['stock' => $stokBaru, 'updated_at' => now()]);
            DB::table('mutasi_stok')->insert([
                'lot_beras_id' => $id,
                'dicatat_oleh' => $request->user()->id,
                'jenis' => 'koreksi_stok',
                'perubahan_kg' => $data['perubahan_kg'],
                'saldo_setelah_kg' => $sisaBaru,
                'alasan' => $data['alasan'],
                'referensi' => 'lot_beras:'.$id,
                'is_simulation' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->refreshCatalogPrice($produk, $request->user()->id);
        }, attempts: 3);

        return back()->with('success', 'Koreksi lot dan katalog tersimpan bersama alasan dan mutasi stok.');
    }

    private function refreshCatalogPrice(object $produk, int $userId): void
    {
        $lotFifo = DB::table('lot_beras')
            ->where('katalog_beras_id', $produk->id)
            ->where('sisa_kg', '>', 0)
            ->orderBy('tanggal_masuk')
            ->orderBy('id')
            ->first(['harga_beli_per_kg']);
        if (! $lotFifo) {
            DB::table('katalog_beras')->where('id', $produk->id)->update(['harga_per_kg' => 0, 'updated_at' => now()]);

            return;
        }

        $margin = DB::table('pengaturan_operasional')->where('kunci', 'persen_keuntungan_beras')->value('nilai');
        abort_unless($margin !== null, 503, 'Persentase keuntungan beras belum tersedia.');
        $pengali = bcadd('1', bcdiv((string) $margin, '100', 6), 6);
        $hargaPenawaran = bcadd(bcmul($lotFifo->harga_beli_per_kg, $pengali, 3), '0.005', 2);

        if (bccomp($produk->harga_per_kg, $hargaPenawaran, 2) !== 0) {
            DB::table('katalog_beras')->where('id', $produk->id)->update(['harga_per_kg' => $hargaPenawaran, 'updated_at' => now()]);
            DB::table('riwayat_harga_katalog')->insert([
                'katalog_beras_id' => $produk->id,
                'harga_pokok_per_kg' => $lotFifo->harga_beli_per_kg,
                'persen_keuntungan' => $margin,
                'harga_penawaran_per_kg' => $hargaPenawaran,
                'berlaku_mulai' => now(),
                'ditetapkan_oleh' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
