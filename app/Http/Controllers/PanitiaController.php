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
        ];

        return view('panitia.dashboard', compact('ringkasan'));
    }
}
