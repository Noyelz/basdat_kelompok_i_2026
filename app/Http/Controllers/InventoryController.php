<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class InventoryController extends Controller
{
    public function index(): View
    {
        $vendor = DB::table('vendor')
            ->select('id', 'nama', 'telp', 'email', 'alamat', 'is_active')
            ->selectSub(function ($query): void {
                $query->from('pembelian_beras')
                    ->whereColumn('pembelian_beras.vendor_id', 'vendor.id')
                    ->selectRaw('COUNT(*)');
            }, 'pembelian_count')
            ->orderBy('nama')
            ->get();
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
            ->selectSub(function ($query): void {
                $query->from('lot_beras')->whereColumn('lot_beras.katalog_beras_id', 'katalog_beras.id')->selectRaw('COUNT(*)');
            }, 'lot_count')
            ->selectSub(function ($query): void {
                $query->from('transaksi_fitrah')->whereColumn('transaksi_fitrah.katalog_beras_id', 'katalog_beras.id')->selectRaw('COUNT(*)');
            }, 'transaksi_count')
            ->selectSub(function ($query): void {
                $query->from('riwayat_harga_katalog')->whereColumn('riwayat_harga_katalog.katalog_beras_id', 'katalog_beras.id')->selectRaw('COUNT(*)');
            }, 'riwayat_harga_count')
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

        $pembelian = DB::table('pembelian_beras')
            ->join('vendor', 'vendor.id', '=', 'pembelian_beras.vendor_id')
            ->join('gudang', 'gudang.id', '=', 'pembelian_beras.gudang_id')
            ->orderByDesc('pembelian_beras.id')
            ->limit(30)
            ->get(['pembelian_beras.id', 'pembelian_beras.kode_pembelian', 'pembelian_beras.nomor_faktur', 'pembelian_beras.bukti_path', 'pembelian_beras.tanggal_pembelian', 'vendor.nama as nama_vendor', 'gudang.nama as nama_gudang']);

        return view('panitia.gudang', compact('vendor', 'gudang', 'katalog', 'lot', 'koreksi', 'pembelian'));
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

    public function updateVendor(Request $request, int $id): RedirectResponse
    {
        $request->merge(['vendor_edit_id' => $id]);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'telp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:191'],
            'alamat' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $id, $data): void {
            $vendor = DB::table('vendor')->where('id', $id)->lockForUpdate()->first();
            abort_unless($vendor, 404);

            DB::table('vendor')->where('id', $id)->update([
                ...$data,
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);
            DB::table('audit_log')->insert([
                'actor_user_id' => $request->user()->id,
                'aksi' => 'vendor.diperbarui',
                'jenis_objek' => 'vendor',
                'objek_id' => $id,
                'detail' => json_encode([
                    'nama_sebelum' => $vendor->nama,
                    'nama_sesudah' => $data['nama'],
                    'aktif_sebelum' => (bool) $vendor->is_active,
                    'aktif_sesudah' => (bool) $data['is_active'],
                ], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        }, attempts: 3);

        return redirect()->route('panitia.gudang.index')->with('success', 'Vendor diperbarui. Vendor nonaktif tidak dapat dipilih untuk penerimaan baru; riwayat pembelian tetap tersimpan.');
    }

    public function destroyVendor(Request $request, int $id): RedirectResponse
    {
        DB::transaction(function () use ($request, $id): void {
            $vendor = DB::table('vendor')->where('id', $id)->lockForUpdate()->first();
            abort_unless($vendor, 404);

            if (DB::table('pembelian_beras')->where('vendor_id', $id)->exists()) {
                throw ValidationException::withMessages([
                    'vendor' => 'Vendor sudah memiliki riwayat pembelian dan tidak boleh dihapus. Gunakan Edit vendor untuk menonaktifkannya.',
                ]);
            }

            DB::table('vendor')->where('id', $id)->delete();
            DB::table('audit_log')->insert([
                'actor_user_id' => $request->user()->id,
                'aksi' => 'vendor.dihapus',
                'jenis_objek' => 'vendor',
                'objek_id' => $id,
                'detail' => json_encode(['nama' => $vendor->nama], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        }, attempts: 3);

        return redirect()->route('panitia.gudang.index')->with('success', 'Vendor yang belum digunakan berhasil dihapus.');
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

    public function destroyKatalog(Request $request, int $id): RedirectResponse
    {
        DB::transaction(function () use ($request, $id): void {
            $produk = DB::table('katalog_beras')->where('id', $id)->lockForUpdate()->first();
            abort_unless($produk, 404);

            $sudahDigunakan = DB::table('lot_beras')->where('katalog_beras_id', $id)->exists()
                || DB::table('transaksi_fitrah')->where('katalog_beras_id', $id)->exists()
                || DB::table('riwayat_harga_katalog')->where('katalog_beras_id', $id)->exists();
            if ($sudahDigunakan || bccomp((string) $produk->stock, '0', 2) !== 0) {
                throw ValidationException::withMessages([
                    'katalog' => 'Jenis beras hanya boleh dihapus jika stok nol dan belum memiliki lot, transaksi, atau riwayat harga. Stok habis tidak berarti riwayatnya boleh dihapus.',
                ]);
            }

            DB::table('katalog_beras')->where('id', $id)->delete();
            DB::table('audit_log')->insert([
                'actor_user_id' => $request->user()->id,
                'aksi' => 'katalog_beras.dihapus',
                'jenis_objek' => 'katalog_beras',
                'objek_id' => $id,
                'detail' => json_encode(['nama_beras' => $produk->nama_beras], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        }, attempts: 3);

        return redirect()->route('panitia.gudang.index')->with('success', 'Jenis beras yang belum digunakan berhasil dihapus.');
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
            'bukti_faktur' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $buktiPath = $request->hasFile('bukti_faktur')
            ? $request->file('bukti_faktur')->store('faktur-vendor', 'local')
            : null;
        if ($request->hasFile('bukti_faktur') && ! $buktiPath) {
            throw new RuntimeException('Bukti faktur gagal disimpan.');
        }

        try {
            DB::transaction(function () use ($request, $data, $buktiPath): void {
                $produk = DB::table('katalog_beras')->where('id', $data['katalog_beras_id'])->lockForUpdate()->first();
                if (! $produk || ! $produk->is_active) {
                    throw ValidationException::withMessages(['katalog_beras_id' => 'Jenis beras sudah dihapus atau dinonaktifkan. Pilih jenis beras aktif lain.']);
                }
                $vendor = DB::table('vendor')->where('id', $data['vendor_id'])->lockForUpdate()->first(['id', 'is_active']);
                if (! $vendor || ! $vendor->is_active) {
                    throw ValidationException::withMessages(['vendor_id' => 'Vendor sudah dihapus atau dinonaktifkan. Pilih vendor aktif lain.']);
                }
                abort_unless(DB::table('gudang')->where('id', $data['gudang_id'])->where('is_active', true)->exists(), 422);

                $pembelianId = DB::table('pembelian_beras')->insertGetId([
                    'kode_pembelian' => 'BELI-'.Str::ulid(),
                    'vendor_id' => $data['vendor_id'],
                    'gudang_id' => $data['gudang_id'],
                    'dicatat_oleh' => $request->user()->id,
                    'tanggal_pembelian' => $data['tanggal_masuk'],
                    'nomor_faktur' => $data['nomor_faktur'] ?? null,
                    'bukti_path' => $buktiPath,
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
        } catch (Throwable $exception) {
            if ($buktiPath) {
                Storage::disk('local')->delete($buktiPath);
            }

            throw $exception;
        }

        return back()->with('success', 'Beras diterima dan lot, stok, mutasi, serta harga katalog diperbarui.');
    }

    public function uploadInvoice(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'bukti_faktur' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $path = $request->file('bukti_faktur')->store('faktur-vendor', 'local');
        if (! $path) {
            throw new RuntimeException('Bukti faktur gagal disimpan.');
        }

        try {
            DB::transaction(function () use ($request, $id, $path): void {
                $pembelian = DB::table('pembelian_beras')->where('id', $id)->lockForUpdate()->first();
                abort_unless($pembelian, 404);
                abort_if($pembelian->bukti_path, 409, 'Bukti faktur untuk pembelian ini sudah ada.');

                DB::table('pembelian_beras')->where('id', $id)->update(['bukti_path' => $path, 'updated_at' => now()]);
                DB::table('audit_log')->insert([
                    'actor_user_id' => $request->user()->id,
                    'aksi' => 'pembelian.faktur_diunggah',
                    'jenis_objek' => 'pembelian_beras',
                    'objek_id' => $id,
                    'detail' => json_encode(['nomor_faktur' => $pembelian->nomor_faktur], JSON_THROW_ON_ERROR),
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        return back()->with('success', 'Bukti faktur tersimpan secara privat.');
    }

    public function invoice(int $id): StreamedResponse
    {
        $path = DB::table('pembelian_beras')->where('id', $id)->value('bukti_path');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store']);
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
