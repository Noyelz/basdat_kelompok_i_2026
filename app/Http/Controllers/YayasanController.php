<?php

namespace App\Http\Controllers;

use App\Models\Yayasan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class YayasanController extends Controller
{
    public function dashboard(Request $request): View
    {
        $yayasan = Yayasan::where('pj_user_id', $request->user()->id)->first();
        $bank = DB::table('bank')->where('is_active', true)->orderBy('nama')->get(['id', 'nama']);
        $catatanVerifikasi = $yayasan
            ? DB::table('verifikasi_panti')->where('yayasan_id', $yayasan->id)->latest('id')->value('catatan')
            : null;
        $alokasi = $yayasan
            ? DB::table('alokasi_transaksi')
                ->join('transaksi_zakat', 'transaksi_zakat.id', '=', 'alokasi_transaksi.transaksi_id')
                ->leftJoin('bukti_serah_terima as bukti_panitia', function ($join): void {
                    $join->on('bukti_panitia.alokasi_transaksi_id', '=', 'alokasi_transaksi.id')->where('bukti_panitia.pihak', 'panitia');
                })
                ->leftJoin('bukti_serah_terima as bukti_pj', function ($join): void {
                    $join->on('bukti_pj.alokasi_transaksi_id', '=', 'alokasi_transaksi.id')->where('bukti_pj.pihak', 'yayasan');
                })
                ->where('alokasi_transaksi.yayasan_id', $yayasan->id)
                ->orderByDesc('alokasi_transaksi.id')
                ->limit(100)
                ->get(['alokasi_transaksi.*', 'transaksi_zakat.kode_transaksi', 'transaksi_zakat.jenis_zakat', 'bukti_panitia.id as bukti_panitia_id', 'bukti_pj.id as bukti_pj_id'])
            : collect();

        return view('yayasan.dashboard', compact('yayasan', 'bank', 'catatanVerifikasi', 'alokasi'));
    }

    public function storePanti(Request $request): RedirectResponse
    {
        abort_if(Yayasan::where('pj_user_id', $request->user()->id)->exists(), 409);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nomor_izin' => ['required', 'string', 'max:100', Rule::unique('yayasan', 'nomor_izin')],
            'telp' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:191'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kota_kabupaten' => ['required', 'string', 'max:100'],
            'alamat_lengkap' => ['required', 'string'],
            'kode_pos' => ['required', 'string', 'max:10'],
            'bank_id' => ['required', Rule::exists('bank', 'id')->where('is_active', true)],
            'no_rek' => ['required', 'string', 'max:50'],
            'atas_nama_rek' => ['required', 'string', 'max:255'],
            'foto' => ['required', 'array', 'min:1', 'max:5'],
            'foto.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $data, &$storedPaths): void {
                $bank = DB::table('bank')->where('id', $data['bank_id'])->where('is_active', true)->first(['nama']);
                abort_unless($bank, 422);

                $yayasan = Yayasan::create([
                    'pj_user_id' => $request->user()->id,
                    'nama' => $data['nama'],
                    'nomor_izin' => $data['nomor_izin'],
                    'telp' => $data['telp'],
                    'email' => $data['email'],
                    'provinsi' => $data['provinsi'],
                    'kota_kabupaten' => $data['kota_kabupaten'],
                    'alamat_lengkap' => $data['alamat_lengkap'],
                    'kode_pos' => $data['kode_pos'],
                    'bank_id' => $data['bank_id'],
                    'nama_bank' => $bank->nama,
                    'no_rek' => $data['no_rek'],
                    'atas_nama_rek' => $data['atas_nama_rek'],
                    'status_verif' => 'pending',
                    'is_active' => false,
                ]);

                foreach ($request->file('foto') as $file) {
                    $path = $file->store('panti', 'local');
                    if (! $path) {
                        throw new RuntimeException('Foto panti gagal disimpan.');
                    }

                    $storedPaths[] = $path;
                    DB::table('foto_yayasan')->insert([
                        'yayasan_id' => $yayasan->id,
                        'diunggah_oleh' => $request->user()->id,
                        'path_private' => $path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);

            throw $exception;
        }

        return redirect()->route('yayasan.dashboard')->with('success', 'Pendaftaran panti dikirim dan menunggu pemeriksaan panitia.');
    }
}
