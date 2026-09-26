<?php

namespace App\Http\Controllers;

use App\Models\KatalogBeras;
use App\Models\TransaksiFitrah;
use App\Models\TransaksiMal;
use App\Models\TransaksiZakat;
use App\Models\Yayasan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MuzakkiController extends Controller
{
    // Dashboard Muzakki
    public function index()
    {
        return view('muzakki.dashboard');
    }

    // Form Zakat Fitrah
    public function showFitrah()
    {
        $hargaPerJiwa = 35000; // Standar 2.5kg / Rp35.000 per jiwa
        return view('muzakki.fitrah', compact('hargaPerJiwa'));
    }

    // Form Zakat Maal
    public function showMaal()
    {
        return view('muzakki.maal');
    }

    // Proses Simpan Transaksi (Fitrah & Maal)
    public function store(Request $request)
    {
        $request->validate([
            'jenis_zakat'      => 'required|in:fitrah,maal,mal',
            'nama_pembayar'    => 'required|string|max:255',
            'umur'             => 'required|numeric|min:1',
            'tanggal_lahir'    => 'required|date',
            'pekerjaan'        => 'required|string|max:255',
            'total_bayar'      => 'required|numeric|min:1000',
            'bukti_pembayaran' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Simpan File Bukti Pembayaran ke folder public/uploads/bukti
        $file = $request->file('bukti_pembayaran');
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
        $file->move(public_path('uploads/bukti'), $filename);

        // Ambil ID yayasan pertama sebagai tujuan zakat
        $yayasan = Yayasan::first();
        if (!$yayasan) {
            // Jika belum ada yayasan di database, buat yayasan default
            $yayasan = Yayasan::create([
                'nama'           => 'Yayasan Zakat Utama',
                'nomor_izin'     => 'KEMENAG/ZAKAT/001',
                'telp'           => '08123456789',
                'email'          => 'yayasan@zakat.com',
                'kota_kabupaten' => 'Surabaya',
                'provinsi'       => 'Jawa Timur',
                'alamat_lengkap' => 'Jl. Zakat Barokah No. 1',
                'nama_bank'      => 'BSI',
                'no_rek'         => '1234567890',
                'atas_nama_rek'  => 'Yayasan Zakat Utama',
                'status_verif'   => 'verified',
                'is_active'      => true,
                'pj_user_id'     => Auth::id(),
            ]);
        }

        $jenisZakatDb = ($request->jenis_zakat === 'maal' || $request->jenis_zakat === 'mal') ? 'mal' : 'fitrah';
        $kodeTransaksi = 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        // Buat Master Transaksi Zakat
        $trx = TransaksiZakat::create([
            'kode_transaksi'    => $kodeTransaksi,
            'user_id'           => Auth::id(),
            'yayasan_id'        => $yayasan->id,
            'jenis_zakat'       => $jenisZakatDb,
            'total_nominal'     => $request->total_bayar,
            'metode_pembayaran' => 'Transfer Manual',
            'status_pembayaran' => 'pending',
            'qr_payload'        => 'uploads/bukti/' . $filename,
        ]);

        // Simpan Detail Transaksi
        if ($jenisZakatDb === 'fitrah') {
            $beras = KatalogBeras::first();
            if (!$beras) {
                $beras = KatalogBeras::create([
                    'nama_beras'   => 'Beras Pandan Wangi',
                    'harga_per_kg' => 14000.00,
                    'stock'        => 500.00,
                    'is_active'    => true,
                ]);
            }

            $jumlahJiwa = (int) ($request->jumlah_keluarga ?? 1);
            TransaksiFitrah::create([
                'transaksi_id'          => $trx->id,
                'katalog_beras_id'      => $beras->id,
                'nama_beras_snapshot'   => $beras->nama_beras,
                'harga_per_kg_snapshot' => $beras->harga_per_kg,
                'bobot_per_jiwa_kg'     => 2.50,
                'jumlah_jiwa'           => $jumlahJiwa,
                'total_beras_kg'        => $jumlahJiwa * 2.50,
                'total_biaya_fitrah'    => $request->total_bayar,
                'bukti_pict_id'         => 'uploads/bukti/' . $filename,
            ]);
        } else {
            // Detail Zakat Mal
            $hargaEmasPerGram = 1350000.00; // Standar snapshot harga emas per gram saat ini
            $nominalEmasRupiah = (float) ($request->nilai_emas ?? 0);
            
            // Kolom emas_gram bertipe DECIMAL(8,2). Jika input berupa rupiah (> 10.000), konversi ke gram:
            $bobotEmasGram = $nominalEmasRupiah > 10000 
                ? round($nominalEmasRupiah / $hargaEmasPerGram, 2) 
                : round($nominalEmasRupiah, 2);

            $nilaiInvestasi = (float) ($request->nilai_investasi ?? 0);
            $asetDagang     = (float) ($request->aset_dagang ?? 0);
            $piutang        = (float) ($request->piutang ?? 0);
            $utang          = (float) ($request->utang ?? 0);

            $totalHartaBersih = ($nominalEmasRupiah + $nilaiInvestasi + $asetDagang + $piutang) - $utang;
            $nilaiNisab = 85 * $hargaEmasPerGram;

            TransaksiMal::create([
                'transaksi_id'                 => $trx->id,
                'nilai_tabungan'               => $nominalEmasRupiah,
                'emas_gram'                    => $bobotEmasGram,
                'harga_emas_per_gram_snapshot' => $hargaEmasPerGram,
                'nilai_investasi'              => $nilaiInvestasi,
                'nilai_aset_dagang'            => $asetDagang,
                'nilai_piutang'                => $piutang,
                'nilai_utang'                  => $utang,
                'total_harta_bersih'           => $totalHartaBersih,
                'nilai_nisab'                  => $nilaiNisab,
                'wajib_zakat'                  => $totalHartaBersih >= $nilaiNisab,
                'persentase_zakat'             => 2.50,
                'total_zakat_wajib'            => (float) $request->total_bayar,
                'bukti_pict_id'                => 'uploads/bukti/' . $filename,
            ]);
        }

        return redirect()->route('muzakki.riwayat')->with('success', 'Pembayaran zakat berhasil dikirim! Menunggu verifikasi panitia.');
    }

    // Halaman Riwayat Transaksi
    public function riwayat()
    {
        $transaksi = TransaksiZakat::where('user_id', Auth::id())->latest()->get();
        return view('muzakki.riwayat', compact('transaksi'));
    }

    // Halaman Profil
    public function profil()
    {
        $user = Auth::user();
        return view('muzakki.profil', compact('user'));
    }

    // Proses Update Profil
    public function updateProfil(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'nama'           => 'required|string|max:255',
            'nik'            => 'required|digits:16',
            'telp'           => 'required|string|max:20',
            'tggl_lahir'     => 'required|date',
            'gender'         => 'required|in:L,P',
            'provinsi'       => 'required|string|max:100',
            'kota_kabupaten' => 'required|string|max:100',
            'alamat_lengkap' => 'nullable|string',
            'npwp'           => 'nullable|string|max:25',
        ], [
            'nama.required'           => 'Nama lengkap wajib diisi.',
            'nik.required'            => 'NIK wajib diisi.',
            'nik.digits'              => 'NIK harus terdiri dari 16 digit angka.',
            'telp.required'           => 'Nomor telepon / WhatsApp wajib diisi.',
            'tggl_lahir.required'     => 'Tanggal lahir wajib diisi.',
            'gender.required'         => 'Jenis kelamin wajib dipilih.',
            'gender.in'               => 'Pilihan jenis kelamin tidak valid.',
            'provinsi.required'       => 'Provinsi wajib dipilih.',
            'kota_kabupaten.required' => 'Kota / Kabupaten wajib dipilih.',
        ]);

        $user->update([
            'nama'           => $request->nama,
            'nik'            => $request->nik,
            'telp'           => $request->telp,
            'tggl_lahir'     => $request->tggl_lahir,
            'gender'         => $request->gender,
            'provinsi'       => $request->provinsi,
            'kota_kabupaten' => $request->kota_kabupaten,
            'alamat_lengkap' => $request->alamat_lengkap ?: null,
            'npwp'           => $request->npwp ?: null,
        ]);

        return redirect()->route('muzakki.profil')->with('success', 'Profil Anda berhasil diperbarui!');
    }
}