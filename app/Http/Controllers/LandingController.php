<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LandingController extends Controller
{
    public const DEFAULT_CONTENT = [
        'landing_judul' => 'Zakat yang jelas alurnya, dari niat hingga diterima panti',
        'landing_pengantar' => 'Portal Zakat membantu Anda memahami perhitungan zakat, memilih panti yang terverifikasi, dan mengikuti proses penyaluran secara terbuka.',
        'landing_tentang' => 'Untuk tahap pengembangan, pembayaran dan angka dampak di situs ini adalah simulasi internal. Panitia mengelola stok beras di gudang Surabaya dan rute layanan awal di Pulau Jawa.',
        'landing_faq_judul' => 'Apakah pembayaran di sini sudah menerima uang sungguhan?',
        'landing_faq_jawaban' => 'Belum. Tombol bayar hanya mencoba alur aplikasi. Status dibayar simulasi bukan bukti penerimaan uang dan tidak otomatis mengirim beras.',
    ];

    public function index(): View
    {
        $tersimpan = DB::table('pengaturan_operasional')
            ->whereIn('kunci', array_keys(self::DEFAULT_CONTENT))
            ->pluck('nilai', 'kunci');
        $konten = collect(self::DEFAULT_CONTENT)
            ->mapWithKeys(fn (string $nilai, string $kunci): array => [$kunci => $tersimpan[$kunci] ?? $nilai]);

        $panti = DB::table('yayasan')
            ->where('status_verif', 'verified')
            ->where('is_active', true)
            ->orderBy('nama')
            ->limit(6)
            ->get(['nama', 'kota_kabupaten', 'provinsi', 'jumlah_mustahik_layak', 'kebutuhan_saat_ini']);

        $jumlahPanti = DB::table('yayasan')
            ->where('status_verif', 'verified')
            ->where('is_active', true)
            ->count();

        $selesai = DB::table('penyaluran')
            ->join('yayasan', 'yayasan.id', '=', 'penyaluran.yayasan_id')
            ->join('alokasi_transaksi', 'alokasi_transaksi.id', '=', 'penyaluran.alokasi_transaksi_id')
            ->join('transaksi_zakat', 'transaksi_zakat.id', '=', 'alokasi_transaksi.transaksi_id')
            ->where('penyaluran.status', 'selesai')
            ->where('transaksi_zakat.mode_pembayaran', 'simulation')
            ->where('transaksi_zakat.status_pembayaran', 'simulated_paid')
            ->where('yayasan.status_verif', 'verified')
            ->where('yayasan.is_active', true)
            ->selectRaw("SUM(CASE WHEN penyaluran.jenis_zakat = 'fitrah' THEN penyaluran.jumlah ELSE 0 END) AS fitrah_kg")
            ->selectRaw("SUM(CASE WHEN penyaluran.jenis_zakat = 'mal' THEN penyaluran.jumlah ELSE 0 END) AS maal_rupiah")
            ->first();

        $bobotPerJiwa = DB::table('pengaturan_operasional')
            ->where('kunci', 'bobot_fitrah_per_jiwa_kg')
            ->value('nilai') ?? '2.5';

        return view('landing', compact('konten', 'panti', 'jumlahPanti', 'selesai', 'bobotPerJiwa'));
    }
}
