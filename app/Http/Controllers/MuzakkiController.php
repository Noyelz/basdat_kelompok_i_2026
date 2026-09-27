<?php

namespace App\Http\Controllers;

use App\Models\TransaksiZakat;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MuzakkiController extends Controller
{
    public function index(Request $request): View
    {
        TransaksiZakat::expirePending();
        $userId = $request->user()->id;
        $ringkasan = [
            'menunggu_pembayaran' => DB::table('transaksi_zakat')->where('user_id', $userId)->where('status_pembayaran', 'pending')->count(),
            'dibayar_simulasi' => DB::table('transaksi_zakat')->where('user_id', $userId)->where('status_pembayaran', 'simulated_paid')->count(),
            'selesai' => DB::table('transaksi_zakat')->where('user_id', $userId)->where('status_penyaluran', 'selesai')->count(),
        ];
        $terbaru = TransaksiZakat::with('yayasan')->where('user_id', $userId)->latest()->limit(5)->get();
        $notifikasi = DB::table('notifikasi_aplikasi')
            ->where('user_id', $userId)
            ->where('jenis', 'penyaluran_selesai')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'payload', 'dibaca_at', 'created_at'])
            ->map(function ($item) {
                $item->detail = json_decode($item->payload, true) ?: [];

                return $item;
            });

        $fitrahMasuk = DB::table('transaksi_zakat')
            ->join('transaksi_fitrah', 'transaksi_fitrah.transaksi_id', '=', 'transaksi_zakat.id')
            ->where('transaksi_zakat.status_pembayaran', 'simulated_paid')
            ->selectRaw('transaksi_zakat.yayasan_id, SUM(transaksi_fitrah.total_beras_kg) as jumlah')
            ->groupBy('transaksi_zakat.yayasan_id')
            ->pluck('jumlah', 'yayasan_id');
        $maalMasuk = DB::table('transaksi_zakat')
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

        $panti = DB::table('yayasan')
            ->where('status_verif', 'verified')
            ->where('is_active', true)
            ->get(['id', 'nama', 'kota_kabupaten', 'provinsi', 'jumlah_mustahik_layak'])
            ->map(function ($item) use ($fitrahMasuk, $maalMasuk, $fitrahSelesai, $maalSelesai) {
                $item->fitrah_masuk = $fitrahMasuk[$item->id] ?? '0.00';
                $item->maal_masuk = $maalMasuk[$item->id] ?? '0.00';
                $item->fitrah_selesai = $fitrahSelesai[$item->id] ?? '0.00';
                $item->maal_selesai = $maalSelesai[$item->id] ?? '0.00';
                $item->fitrah_per_penerima = $item->jumlah_mustahik_layak > 0
                    ? (float) $item->fitrah_masuk / $item->jumlah_mustahik_layak
                    : null;
                $item->maal_per_penerima = $item->jumlah_mustahik_layak > 0
                    ? (float) $item->maal_masuk / $item->jumlah_mustahik_layak
                    : null;

                return $item;
            });

        $pantiDenganPenerima = $panti->filter(fn ($item): bool => $item->jumlah_mustahik_layak > 0);
        $pantiFitrah = $pantiDenganPenerima->sortByDesc('fitrah_per_penerima', SORT_NUMERIC)->take(3);
        $pantiFitrahRendah = $pantiDenganPenerima->sortBy('fitrah_per_penerima', SORT_NUMERIC)->take(3);
        $pantiMaal = $pantiDenganPenerima->sortByDesc('maal_per_penerima', SORT_NUMERIC)->take(3);
        $pantiMaalRendah = $pantiDenganPenerima->sortBy('maal_per_penerima', SORT_NUMERIC)->take(3);
        $pantiSekitar = $pantiDenganPenerima->where('provinsi', $request->user()->provinsi)->sortBy('fitrah_per_penerima', SORT_NUMERIC)->take(3);
        $tanggalIdulfitri = DB::table('pengaturan_operasional')->where('kunci', 'tanggal_idulfitri')->value('nilai');
        $sisaHari = $tanggalIdulfitri
            ? (int) now('Asia/Jakarta')->startOfDay()->diffInDays(CarbonImmutable::parse($tanggalIdulfitri, 'Asia/Jakarta')->startOfDay(), false)
            : null;

        return view('muzakki.dashboard', compact('ringkasan', 'terbaru', 'notifikasi', 'pantiFitrah', 'pantiFitrahRendah', 'pantiMaal', 'pantiMaalRendah', 'pantiSekitar', 'tanggalIdulfitri', 'sisaHari'));
    }

    public function showMaal(): View
    {
        return view('muzakki.maal');
    }

    public function riwayat(Request $request): View
    {
        TransaksiZakat::expirePending();
        $transaksi = TransaksiZakat::query()
            ->with(['yayasan', 'detailFitrah', 'detailMal'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('muzakki.riwayat', compact('transaksi'));
    }

    public function profil(Request $request): View
    {
        return view('muzakki.profil', ['user' => $request->user()]);
    }

    public function updateProfil(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'digits:16', Rule::unique('user', 'nik')->ignore($request->user()->id)],
            'telp' => ['required', 'string', 'max:20'],
            'tggl_lahir' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'provinsi' => ['required', 'string', 'max:100'],
            'kota_kabupaten' => ['required', 'string', 'max:100'],
            'alamat_lengkap' => ['nullable', 'string', 'max:2000'],
            'npwp' => ['nullable', 'string', 'max:25'],
        ]);

        $request->user()->update($data);

        return redirect()->route('muzakki.profil')->with('success', 'Profil Anda diperbarui.');
    }
}
