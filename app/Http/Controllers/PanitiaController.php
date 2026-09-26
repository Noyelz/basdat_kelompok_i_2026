<?php

namespace App\Http\Controllers;

use App\Models\KatalogBeras;
use App\Models\Penyaluran;
use App\Models\TransaksiZakat;
use App\Models\User;
use App\Models\Yayasan;
use Illuminate\Http\Request;

class PanitiaController extends Controller
{
    /**
     * Dashboard Ringkasan Panitia
     */
    public function index()
    {
        // Hitung total zakat maal masuk dan tersisa
        $fundMaalMasuk = (float) TransaksiZakat::where('jenis_zakat', 'mal')
            ->where('status_pembayaran', 'paid')
            ->sum('total_nominal');
        $danaMaalDisalurkan = (float) Penyaluran::where('jenis_zakat', 'mal')->sum('jumlah');
        $fundMaalTersedia = max(0, $fundMaalMasuk - $danaMaalDisalurkan);

        // Hitung total zakat fitrah uang masuk
        $fundFitrahTersedia = (float) TransaksiZakat::where('jenis_zakat', 'fitrah')
            ->where('status_pembayaran', 'paid')
            ->sum('total_nominal');

        // Data stok gudang beras
        $stokBeras = (float) KatalogBeras::sum('stock');
        $gudang = (object) [
            'stok_beras_kg'         => $stokBeras,
            'total_uang_dikonversi' => 0,
        ];

        // Total pengguna
        $totalMuzakki = User::where('role', 'pemberi')->count();
        $totalYayasan = Yayasan::count();

        return view('panitia.dashboard', compact(
            'fundMaalTersedia',
            'fundFitrahTersedia',
            'gudang',
            'totalMuzakki',
            'totalYayasan'
        ));
    }

    /**
     * Halaman Verifikasi Pembayaran Transaksi Muzakki
     */
    public function transaksiMuzakki()
    {
        $transaksi = TransaksiZakat::with('user')->latest()->get();
        return view('panitia.transaksi', compact('transaksi'));
    }

    /**
     * Proses Approve/Tolak Verifikasi Transaksi
     */
    public function verifikasiTransaksi($id, Request $request)
    {
        $transaksi = TransaksiZakat::findOrFail($id);
        
        $statusBaru = $request->status === 'success' ? 'paid' : 'failed';
        $transaksi->update([
            'status_pembayaran' => $statusBaru,
            'paid_at'           => $statusBaru === 'paid' ? now() : null,
        ]);

        return back()->with('success', 'Status transaksi berhasil diperbarui!');
    }

    /**
     * Konversi Fund Fitrah Menjadi Beras di Gudang
     */
    public function konversiBeras(Request $request)
    {
        $request->validate([
            'nominal_uang' => 'required|numeric|min:1',
            'jumlah_kg'    => 'required|numeric|min:0.1',
        ]);

        // Tambahkan stok ke katalog beras yang aktif pertama, atau buat jika belum ada
        $beras = KatalogBeras::where('is_active', true)->first();
        if ($beras) {
            $beras->increment('stock', $request->jumlah_kg);
        } else {
            KatalogBeras::create([
                'nama_beras'   => 'Beras Gudang Utama',
                'harga_per_kg' => $request->nominal_uang / $request->jumlah_kg,
                'stock'        => $request->jumlah_kg,
                'is_active'    => true,
            ]);
        }

        return back()->with('success', 'Beras sebesar ' . $request->jumlah_kg . ' kg berhasil ditambahkan ke gudang!');
    }

    /**
     * Halaman Penyaluran ke Yayasan
     */
    public function pengajuanYayasan()
    {
        $pengajuan = Penyaluran::with(['yayasan', 'mustahik'])->latest()->get();
        return view('panitia.pengajuan', compact('pengajuan'));
    }

    /**
     * Proses Aksi Penyaluran
     */
    public function prosesPengajuan($id, Request $request)
    {
        $penyaluran = Penyaluran::findOrFail($id);
        if ($request->status === 'disalurkan') {
            $penyaluran->bukti_pict_id = 'disalurkan_' . time();
            $penyaluran->save();
            return back()->with('success', 'Bantuan zakat berhasil disalurkan ke yayasan!');
        } elseif ($request->status === 'rejected') {
            $penyaluran->delete();
            return back()->with('success', 'Pengajuan bantuan yayasan telah ditolak/dihapus.');
        }

        return back()->with('success', 'Aksi penyaluran berhasil diproses!');
    }

    /**
     * Halaman Data Pengguna (Muzakki & Yayasan)
     */
    public function dataPengguna()
    {
        $muzakki = User::where('role', 'pemberi')->latest()->get();
        $yayasan = Yayasan::with('pjUser')->latest()->get();

        return view('panitia.pengguna', compact('muzakki', 'yayasan'));
    }
}