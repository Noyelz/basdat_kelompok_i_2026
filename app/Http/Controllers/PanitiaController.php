<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PanitiaController extends Controller
{
    public function index(): View
    {
        $ringkasan = [
            'panti_pending' => DB::table('yayasan')->where('status_verif', 'pending')->count(),
            'pesanan_dibayar' => DB::table('transaksi_zakat')
                ->where('status_pembayaran', 'simulated_paid')
                ->where('status_penyaluran', 'menunggu_alokasi')
                ->count(),
            'pengiriman_tertunda' => DB::table('titik_pengiriman')
                ->whereIn('status', ['dijadwalkan', 'dikirim'])
                ->count(),
            'stok_kg' => DB::table('lot_beras')->sum('sisa_kg'),
            'reservasi_kg' => DB::table('reservasi_stok')->where('status', 'active')->sum('jumlah_kg'),
            'bukti_bermasalah' => DB::table('alokasi_transaksi')->where('status', 'perlu_tinjauan')->count(),
        ];

        $pantiMenunggu = DB::table('yayasan')
            ->where('status_verif', 'pending')
            ->orderBy('created_at')
            ->limit(5)
            ->get(['nama', 'kota_kabupaten', 'provinsi', 'created_at']);

        $reservasiPerProduk = DB::table('reservasi_stok')
            ->join('lot_beras', 'lot_beras.id', '=', 'reservasi_stok.lot_beras_id')
            ->where('reservasi_stok.status', 'active')
            ->selectRaw('lot_beras.katalog_beras_id, SUM(reservasi_stok.jumlah_kg) AS dipesan_kg')
            ->groupBy('lot_beras.katalog_beras_id');
        $stokRendah = DB::table('katalog_beras')
            ->leftJoinSub($reservasiPerProduk, 'reservasi', 'reservasi.katalog_beras_id', '=', 'katalog_beras.id')
            ->where('katalog_beras.is_active', true)
            ->whereRaw('katalog_beras.stock - COALESCE(reservasi.dipesan_kg, 0) < 25')
            ->orderByRaw('katalog_beras.stock - COALESCE(reservasi.dipesan_kg, 0)')
            ->limit(5)
            ->get(['katalog_beras.nama_beras', 'katalog_beras.stock', DB::raw('COALESCE(reservasi.dipesan_kg, 0) AS dipesan_kg')]);

        $pesananSiap = DB::table('transaksi_zakat')
            ->join('yayasan', 'yayasan.id', '=', 'transaksi_zakat.yayasan_id')
            ->where('transaksi_zakat.status_pembayaran', 'simulated_paid')
            ->where('transaksi_zakat.status_penyaluran', 'menunggu_alokasi')
            ->orderBy('transaksi_zakat.created_at')
            ->limit(5)
            ->get(['transaksi_zakat.kode_transaksi', 'transaksi_zakat.jenis_zakat', 'yayasan.nama as nama_panti']);

        $perjalananMendesak = DB::table('perjalanan_pengiriman')
            ->whereIn('status', ['direncanakan', 'dikirim'])
            ->where('jadwal_berangkat', '<=', now()->addDays(3))
            ->orderBy('jadwal_berangkat')
            ->limit(5)
            ->get(['kode_perjalanan', 'jadwal_berangkat', 'status']);

        $buktiTertunda = DB::table('alokasi_transaksi')
            ->join('transaksi_zakat', 'transaksi_zakat.id', '=', 'alokasi_transaksi.transaksi_id')
            ->join('yayasan', 'yayasan.id', '=', 'alokasi_transaksi.yayasan_id')
            ->whereIn('alokasi_transaksi.status', ['dikirim', 'menunggu_bukti', 'perlu_tinjauan'])
            ->orderBy('alokasi_transaksi.created_at')
            ->limit(5)
            ->get(['transaksi_zakat.kode_transaksi', 'yayasan.nama as nama_panti', 'alokasi_transaksi.status']);

        return view('panitia.dashboard', compact('ringkasan', 'pantiMenunggu', 'stokRendah', 'pesananSiap', 'perjalananMendesak', 'buktiTertunda'));
    }
}
