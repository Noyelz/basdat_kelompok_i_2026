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

                return $item;
            });

        $pantiFitrah = $panti->sortByDesc('fitrah_masuk', SORT_NUMERIC)->take(3);
        $pantiFitrahRendah = $panti->sortBy('fitrah_masuk', SORT_NUMERIC)->take(3);
        $pantiMaal = $panti->sortByDesc('maal_masuk', SORT_NUMERIC)->take(3);
        $pantiMaalRendah = $panti->sortBy('maal_masuk', SORT_NUMERIC)->take(3);
        $pantiSekitar = $panti->where('provinsi', $request->user()->provinsi)->sortByDesc('fitrah_masuk', SORT_NUMERIC)->take(3);
        $tanggalIdulfitri = DB::table('pengaturan_operasional')->where('kunci', 'tanggal_idulfitri')->value('nilai');
        $sisaHari = $tanggalIdulfitri
            ? (int) now('Asia/Jakarta')->startOfDay()->diffInDays(CarbonImmutable::parse($tanggalIdulfitri, 'Asia/Jakarta')->startOfDay(), false)
            : null;

        return view('muzakki.dashboard', compact('ringkasan', 'terbaru', 'pantiFitrah', 'pantiFitrahRendah', 'pantiMaal', 'pantiMaalRendah', 'pantiSekitar', 'tanggalIdulfitri', 'sisaHari'));
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
