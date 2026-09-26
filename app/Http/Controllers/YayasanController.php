<?php

namespace App\Http\Controllers;

use App\Models\Penyaluran;
use App\Models\Yayasan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class YayasanController extends Controller
{
    public function dashboard()
    {
        $yayasan = Yayasan::where('pj_user_id', Auth::id())->first();
        
        // Jika akun PJ belum terhubung dengan data yayasan, hubungkan/buatkan otomatis
        if (!$yayasan && Auth::user()->role === 'pj') {
            $yayasan = Yayasan::firstOrCreate(
                ['pj_user_id' => Auth::id()],
                [
                    'nama'           => 'Yayasan ' . Auth::user()->nama,
                    'nomor_izin'     => 'YYS/' . date('Y') . '/' . Auth::id(),
                    'telp'           => Auth::user()->telp ?? '081200000000',
                    'email'          => Auth::user()->email,
                    'kota_kabupaten' => Auth::user()->kota_kabupaten ?? 'Surabaya',
                    'provinsi'       => Auth::user()->provinsi ?? 'Jawa Timur',
                    'alamat_lengkap' => Auth::user()->alamat_lengkap ?? 'Surabaya',
                    'nama_bank'      => 'BSI',
                    'no_rek'         => '1234567890',
                    'atas_nama_rek'  => Auth::user()->nama,
                    'status_verif'   => 'verified',
                    'is_active'      => true,
                ]
            );
        }

        $proposals = collect();

        if ($yayasan) {
            $penyaluranList = Penyaluran::with('mustahik')
                ->where('yayasan_id', $yayasan->id)
                ->latest()
                ->get();

            $proposals = $penyaluranList->map(function ($item) {
                return (object) [
                    'id'                 => $item->id,
                    'judul_program'      => $item->barang ?? ('Penyaluran ' . ucfirst($item->jenis_zakat)),
                    'kebutuhan_dana'     => ($item->jenis_zakat === 'mal' || $item->satuan === 'Rupiah') ? $item->jumlah : 0,
                    'kebutuhan_beras_kg' => ($item->jenis_zakat === 'fitrah' || $item->satuan === 'Kg') ? $item->jumlah : 0,
                    'deskripsi'          => 'Penyaluran untuk mustahik: ' . ($item->mustahik->nama ?? '-'),
                    'status'             => $item->bukti_pict_id ? 'completed' : 'pending',
                    'bukti_penerimaan'   => $item->bukti_pict_id,
                    'created_at'         => $item->created_at,
                ];
            });
        }

        return view('yayasan.dashboard', compact('proposals', 'yayasan'));
    }

    public function storeProposal(Request $request)
    {
        $request->validate([
            'judul_program'      => 'required|string|max:255',
            'kebutuhan_dana'     => 'required|numeric|min:0',
            'kebutuhan_beras_kg' => 'required|numeric|min:0',
            'deskripsi'          => 'required|string',
        ]);

        $yayasan = Yayasan::where('pj_user_id', Auth::id())->first();
        if (!$yayasan) {
            $yayasan = Yayasan::firstOrCreate(
                ['pj_user_id' => Auth::id()],
                [
                    'nama'           => 'Yayasan ' . Auth::user()->nama,
                    'nomor_izin'     => 'YYS/' . date('Y') . '/' . Auth::id(),
                    'telp'           => Auth::user()->telp ?? '081200000000',
                    'email'          => Auth::user()->email,
                    'kota_kabupaten' => Auth::user()->kota_kabupaten ?? 'Surabaya',
                    'provinsi'       => Auth::user()->provinsi ?? 'Jawa Timur',
                    'alamat_lengkap' => Auth::user()->alamat_lengkap ?? 'Surabaya',
                    'nama_bank'      => 'BSI',
                    'no_rek'         => '1234567890',
                    'atas_nama_rek'  => Auth::user()->nama,
                    'status_verif'   => 'verified',
                    'is_active'      => true,
                ]
            );
        }

        // Pastikan ada data mustahik terkait yayasan ini
        $mustahik = \App\Models\Mustahik::firstOrCreate(
            ['yayasan_id' => $yayasan->id, 'nama' => 'Penerima Manfaat ' . $yayasan->nama],
            [
                'kelamin'        => 'L',
                'tggl_lahir'     => '2000-01-01',
                'kategori_asnaf' => 'fakir',
            ]
        );

        $isBeras = $request->kebutuhan_beras_kg > 0;
        Penyaluran::create([
            'yayasan_id'    => $yayasan->id,
            'mustahik_id'   => $mustahik->id,
            'jenis_zakat'   => $isBeras ? 'fitrah' : 'mal',
            'barang'        => $request->judul_program,
            'jumlah'        => $isBeras ? $request->kebutuhan_beras_kg : $request->kebutuhan_dana,
            'satuan'        => $isBeras ? 'Kg' : 'Rupiah',
            'bukti_pict_id' => null,
        ]);

        return back()->with('success', 'Proposal pengajuan berhasil dikirimkan ke Panitia!');
    }

    public function confirmReceipt(Request $request, $id)
    {
        $request->validate([
            'bukti_penerimaan'   => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'is_jumlah_sesuai'   => 'required|boolean',
            'catatan_penerimaan' => 'nullable|string',
        ]);

        $penyaluran = Penyaluran::findOrFail($id);
        $file = $request->file('bukti_penerimaan');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('uploads/bukti'), $filename);

        $penyaluran->update([
            'bukti_pict_id' => 'uploads/bukti/' . $filename,
        ]);

        return back()->with('success', 'Penerimaan berhasil dikonfirmasi!');
    }
}