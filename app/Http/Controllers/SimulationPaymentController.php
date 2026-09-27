<?php

namespace App\Http\Controllers;

use App\Models\TransaksiZakat;
use Carbon\CarbonImmutable;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SimulationPaymentController extends Controller
{
    public function show(Request $request, int $id): View
    {
        TransaksiZakat::expirePending();
        $pesanan = $this->pesananMilikUser($request, $id);
        $qrDataUri = null;
        $scanUrl = null;

        if ($pesanan->status_pembayaran === 'pending') {
            $token = Crypt::decryptString($pesanan->qr_payload);
            $scanUrl = route('muzakki.payment.scan', $token);
            $qrDataUri = (new SvgWriter)->write(new QrCode($scanUrl))->getDataUri();
        }

        $bukti = DB::table('bukti_serah_terima')
            ->join('alokasi_transaksi', 'alokasi_transaksi.id', '=', 'bukti_serah_terima.alokasi_transaksi_id')
            ->where('alokasi_transaksi.transaksi_id', $id)
            ->orderBy('bukti_serah_terima.id')
            ->get(['bukti_serah_terima.id', 'bukti_serah_terima.pihak']);

        return view('muzakki.payment', compact('pesanan', 'qrDataUri', 'scanUrl', 'bukti'));
    }

    public function scan(Request $request, string $token): View
    {
        TransaksiZakat::expirePending();
        $percobaan = DB::table('percobaan_pembayaran')->where('token_hash', hash('sha256', $token))->first();
        abort_unless($percobaan, 404);
        $pesanan = $this->pesananMilikUser($request, $percobaan->transaksi_id);

        return view('muzakki.payment-scan', compact('pesanan', 'token'));
    }

    public function pay(Request $request, string $token): RedirectResponse
    {
        $tokenHash = hash('sha256', $token);
        $percobaan = DB::table('percobaan_pembayaran')->where('token_hash', $tokenHash)->first();
        abort_unless($percobaan, 404);

        DB::transaction(function () use ($request, $percobaan, $tokenHash): void {
            $pesanan = DB::table('transaksi_zakat')->where('id', $percobaan->transaksi_id)->lockForUpdate()->first();
            abort_unless($pesanan && $pesanan->user_id === $request->user()->id, 403);
            if ($pesanan->status_pembayaran === 'simulated_paid') {
                return;
            }

            abort_unless($pesanan->status_pembayaran === 'pending' && CarbonImmutable::parse($pesanan->expired_at)->isFuture(), 422, 'Pesanan tidak lagi dapat dibayar.');
            $attempt = DB::table('percobaan_pembayaran')->where('token_hash', $tokenHash)->lockForUpdate()->first();
            abort_unless($attempt && $attempt->status === 'pending', 422);

            DB::table('transaksi_zakat')->where('id', $pesanan->id)->update([
                'status_pembayaran' => 'simulated_paid',
                'paid_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('percobaan_pembayaran')->where('id', $attempt->id)->update([
                'status' => 'simulated_paid',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('muzakki.payment.show', $percobaan->transaksi_id)
            ->with('success', 'Pembayaran simulasi berhasil dicatat. Penyaluran tetap menunggu proses panitia.');
    }

    public function cancel(Request $request, int $id): RedirectResponse
    {
        DB::transaction(function () use ($request, $id): void {
            $pesanan = DB::table('transaksi_zakat')->where('id', $id)->lockForUpdate()->first();
            abort_unless($pesanan && $pesanan->user_id === $request->user()->id, 404);
            abort_unless($pesanan->status_pembayaran === 'pending', 422, 'Hanya pesanan yang masih menunggu pembayaran dapat dibatalkan.');

            DB::table('transaksi_zakat')->where('id', $id)->update(['status_pembayaran' => 'cancelled', 'updated_at' => now()]);
            DB::table('percobaan_pembayaran')->where('transaksi_id', $id)->where('status', 'pending')->update(['status' => 'cancelled', 'updated_at' => now()]);
            DB::table('reservasi_stok')->where('transaksi_id', $id)->where('status', 'active')->update(['status' => 'cancelled', 'released_at' => now(), 'updated_at' => now()]);
        });

        return redirect()->route('muzakki.payment.show', $id)->with('success', 'Pesanan dibatalkan dan reservasi stok dilepas.');
    }

    private function pesananMilikUser(Request $request, int $id): object
    {
        $pesanan = DB::table('transaksi_zakat')
            ->join('yayasan', 'yayasan.id', '=', 'transaksi_zakat.yayasan_id')
            ->leftJoin('transaksi_fitrah', 'transaksi_fitrah.transaksi_id', '=', 'transaksi_zakat.id')
            ->leftJoin('transaksi_mal', 'transaksi_mal.transaksi_id', '=', 'transaksi_zakat.id')
            ->where('transaksi_zakat.id', $id)
            ->where('transaksi_zakat.user_id', $request->user()->id)
            ->select('transaksi_zakat.*', 'yayasan.nama as nama_panti', 'transaksi_fitrah.nama_beras_snapshot', 'transaksi_fitrah.jumlah_jiwa', 'transaksi_fitrah.total_beras_kg', 'transaksi_mal.nilai_nisab', 'transaksi_mal.total_zakat_wajib', 'transaksi_mal.harga_emas_per_gram_snapshot')
            ->first();
        abort_unless($pesanan, 404);

        return $pesanan;
    }
}
