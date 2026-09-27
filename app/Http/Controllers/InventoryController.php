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

        return view('panitia.gudang', compact('vendor', 'gudang', 'katalog', 'lot'));
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

            $lotFifo = DB::table('lot_beras')
                ->where('katalog_beras_id', $produk->id)
                ->where('sisa_kg', '>', 0)
                ->orderBy('tanggal_masuk')
                ->orderBy('id')
                ->first(['harga_beli_per_kg']);
            $margin = DB::table('pengaturan_operasional')->where('kunci', 'persen_keuntungan_beras')->value('nilai');
            abort_unless($lotFifo && $margin !== null, 503, 'Harga katalog belum dapat dihitung.');

            $pengali = bcadd('1', bcdiv((string) $margin, '100', 6), 6);
            $hargaPenawaran = bcadd(bcmul($lotFifo->harga_beli_per_kg, $pengali, 3), '0.005', 2);

            DB::table('katalog_beras')->where('id', $produk->id)->update([
                'stock' => bcadd($produk->stock, $data['jumlah_kg'], 2),
                'harga_per_kg' => $hargaPenawaran,
                'updated_at' => now(),
            ]);

            if (bccomp($produk->harga_per_kg, $hargaPenawaran, 2) !== 0) {
                DB::table('riwayat_harga_katalog')->insert([
                    'katalog_beras_id' => $produk->id,
                    'harga_pokok_per_kg' => $lotFifo->harga_beli_per_kg,
                    'persen_keuntungan' => $margin,
                    'harga_penawaran_per_kg' => $hargaPenawaran,
                    'berlaku_mulai' => now(),
                    'ditetapkan_oleh' => $request->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Beras diterima dan lot, stok, mutasi, serta harga katalog diperbarui.');
    }
}
