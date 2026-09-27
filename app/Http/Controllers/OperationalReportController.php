<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OperationalReportController extends Controller
{
    public function index(): View
    {
        $status = DB::table('transaksi_zakat')
            ->selectRaw('jenis_zakat, status_pembayaran, status_penyaluran, COUNT(*) as jumlah')
            ->groupBy('jenis_zakat', 'status_pembayaran', 'status_penyaluran')
            ->orderBy('jenis_zakat')
            ->orderBy('status_pembayaran')
            ->get();

        $lot = DB::table('lot_beras')->selectRaw('katalog_beras_id, SUM(sisa_kg) as jumlah')->groupBy('katalog_beras_id');
        $reservasi = DB::table('reservasi_stok')
            ->join('lot_beras', 'lot_beras.id', '=', 'reservasi_stok.lot_beras_id')
            ->where('reservasi_stok.status', 'active')
            ->selectRaw('lot_beras.katalog_beras_id, SUM(reservasi_stok.jumlah_kg) as jumlah')
            ->groupBy('lot_beras.katalog_beras_id');
        $stok = DB::table('katalog_beras')
            ->leftJoinSub($lot, 'lot', 'lot.katalog_beras_id', '=', 'katalog_beras.id')
            ->leftJoinSub($reservasi, 'reservasi', 'reservasi.katalog_beras_id', '=', 'katalog_beras.id')
            ->orderBy('katalog_beras.nama_beras')
            ->get(['katalog_beras.nama_beras', 'katalog_beras.stock', DB::raw('COALESCE(lot.jumlah, 0) as lot_kg'), DB::raw('COALESCE(reservasi.jumlah, 0) as reservasi_kg')]);

        $ongkirDitagih = DB::table('alokasi_transaksi')
            ->join('titik_pengiriman', 'titik_pengiriman.id', '=', 'alokasi_transaksi.titik_pengiriman_id')
            ->join('transaksi_zakat', 'transaksi_zakat.id', '=', 'alokasi_transaksi.transaksi_id')
            ->selectRaw('titik_pengiriman.perjalanan_pengiriman_id, SUM(transaksi_zakat.ongkir_snapshot) as jumlah')
            ->groupBy('titik_pengiriman.perjalanan_pengiriman_id')
            ->pluck('jumlah', 'perjalanan_pengiriman_id');
        $perjalanan = DB::table('perjalanan_pengiriman')->orderByDesc('id')->limit(30)->get();

        $panti = DB::table('yayasan')
            ->where('status_verif', 'verified')
            ->orderBy('nama')
            ->get(['id', 'nama', 'kota_kabupaten', 'provinsi', 'jumlah_mustahik_layak']);
        $fitrahTerkumpul = DB::table('transaksi_zakat')
            ->join('transaksi_fitrah', 'transaksi_fitrah.transaksi_id', '=', 'transaksi_zakat.id')
            ->where('transaksi_zakat.status_pembayaran', 'simulated_paid')
            ->selectRaw('transaksi_zakat.yayasan_id, SUM(transaksi_fitrah.total_beras_kg) as jumlah')
            ->groupBy('transaksi_zakat.yayasan_id')
            ->pluck('jumlah', 'yayasan_id');
        $maalTerkumpul = DB::table('transaksi_zakat')
            ->where('jenis_zakat', 'mal')
            ->where('status_pembayaran', 'simulated_paid')
            ->selectRaw('yayasan_id, SUM(total_nominal) as jumlah')
            ->groupBy('yayasan_id')
            ->pluck('jumlah', 'yayasan_id');
        $fitrahSelesai = DB::table('penyaluran')
            ->where('jenis_zakat', 'fitrah')
            ->where('status', 'selesai')
            ->selectRaw('yayasan_id, SUM(jumlah) as jumlah')
            ->groupBy('yayasan_id')
            ->pluck('jumlah', 'yayasan_id');
        $maalSelesai = DB::table('penyaluran')
            ->where('jenis_zakat', 'mal')
            ->where('status', 'selesai')
            ->selectRaw('yayasan_id, SUM(jumlah) as jumlah')
            ->groupBy('yayasan_id')
            ->pluck('jumlah', 'yayasan_id');

        $tugas = [
            'panti_pending' => DB::table('yayasan')->where('status_verif', 'pending')->count(),
            'alokasi_bermasalah' => DB::table('alokasi_transaksi')->where('status', 'perlu_tinjauan')->count(),
            'bukti_tertunda' => DB::table('alokasi_transaksi')->whereIn('status', ['dikirim', 'menunggu_bukti'])->count(),
            'ongkir_belum_dicatat' => DB::table('perjalanan_pengiriman')->whereIn('status', ['dikirim', 'selesai'])->whereNull('ongkir_aktual')->count(),
        ];

        return view('panitia.laporan', compact('status', 'stok', 'ongkirDitagih', 'perjalanan', 'panti', 'fitrahTerkumpul', 'maalTerkumpul', 'fitrahSelesai', 'maalSelesai', 'tugas'));
    }
}
