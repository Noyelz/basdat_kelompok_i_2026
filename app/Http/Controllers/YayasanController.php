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
        $notifikasi = DB::table('notifikasi_aplikasi')
            ->where('user_id', $request->user()->id)
            ->where('jenis', 'penyaluran_selesai')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'payload', 'dibaca_at', 'created_at'])
            ->map(function ($item) {
                $item->detail = json_decode($item->payload, true) ?: [];

                return $item;
            });
        $yayasan = Yayasan::where('pj_user_id', $request->user()->id)->first();
        $bank = DB::table('bank')->where('is_active', true)->orderBy('nama')->get(['id', 'nama']);
        $catatanVerifikasi = $yayasan
            ? DB::table('verifikasi_panti')->where('yayasan_id', $yayasan->id)->latest('id')->value('catatan')
            : null;
        $permohonanRekening = $yayasan
            ? DB::table('permohonan_perubahan_rekening as permohonan')
                ->join('bank', 'bank.id', '=', 'permohonan.bank_id')
                ->where('permohonan.yayasan_id', $yayasan->id)
                ->orderByDesc('permohonan.id')
                ->first(['permohonan.*', 'bank.nama as nama_bank'])
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

        return view('yayasan.dashboard', compact('yayasan', 'bank', 'catatanVerifikasi', 'permohonanRekening', 'alokasi', 'notifikasi'));
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
            'jumlah_mustahik_diajukan' => ['required', 'integer', 'min:1', 'max:100000'],
            'kebutuhan_saat_ini' => ['required', 'string', 'max:2000'],
            'layanan_diminta' => ['required', Rule::in(['maal', 'fitrah_maal'])],
            'kapasitas_diajukan_kg' => ['exclude_unless:layanan_diminta,fitrah_maal', 'required', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'foto' => ['required', 'array', 'min:1', 'max:5'],
            'foto.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'layanan_diminta.required' => 'Pilih layanan maal saja atau fitrah dan maal.',
            'kapasitas_diajukan_kg.required' => 'Kapasitas beras per kunjungan wajib diisi bila panti mengajukan layanan fitrah.',
            'kapasitas_diajukan_kg.gt' => 'Kapasitas beras per kunjungan harus lebih dari nol.',
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
                    'jumlah_mustahik_diajukan' => $data['jumlah_mustahik_diajukan'],
                    'kebutuhan_saat_ini' => $data['kebutuhan_saat_ini'],
                    'kapasitas_diajukan_kg' => $data['kapasitas_diajukan_kg'] ?? null,
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

    public function resubmitPanti(Request $request): RedirectResponse
    {
        $yayasan = Yayasan::where('pj_user_id', $request->user()->id)->firstOrFail();
        abort_unless(in_array($yayasan->status_verif, ['pending', 'rejected'], true), 409, 'Hanya panti pending atau ditolak yang dapat memperbarui pengajuan.');

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nomor_izin' => ['required', 'string', 'max:100', Rule::unique('yayasan', 'nomor_izin')->ignore($yayasan->id)],
            'telp' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:191'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kota_kabupaten' => ['required', 'string', 'max:100'],
            'alamat_lengkap' => ['required', 'string', 'max:2000'],
            'kode_pos' => ['required', 'string', 'max:10'],
            'jumlah_mustahik_diajukan' => ['required', 'integer', 'min:1', 'max:100000'],
            'kebutuhan_saat_ini' => ['required', 'string', 'max:2000'],
            'layanan_diminta' => ['required', Rule::in(['maal', 'fitrah_maal'])],
            'kapasitas_diajukan_kg' => ['exclude_unless:layanan_diminta,fitrah_maal', 'required', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'foto' => ['nullable', 'array', 'min:1', 'max:5'],
            'foto.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'layanan_diminta.required' => 'Pilih layanan maal saja atau fitrah dan maal.',
            'kapasitas_diajukan_kg.required' => 'Kapasitas beras per kunjungan wajib diisi bila panti mengajukan layanan fitrah.',
            'kapasitas_diajukan_kg.gt' => 'Kapasitas beras per kunjungan harus lebih dari nol.',
        ]);

        $storedPaths = [];
        $oldPaths = [];

        try {
            DB::transaction(function () use ($request, $yayasan, $data, &$storedPaths, &$oldPaths): void {
                $locked = Yayasan::whereKey($yayasan->id)->lockForUpdate()->firstOrFail();
                abort_unless(in_array($locked->status_verif, ['pending', 'rejected'], true), 409);

                $locked->update([
                    'nama' => $data['nama'],
                    'nomor_izin' => $data['nomor_izin'],
                    'telp' => $data['telp'],
                    'email' => $data['email'],
                    'provinsi' => $data['provinsi'],
                    'kota_kabupaten' => $data['kota_kabupaten'],
                    'alamat_lengkap' => $data['alamat_lengkap'],
                    'kode_pos' => $data['kode_pos'],
                    'jumlah_mustahik_diajukan' => $data['jumlah_mustahik_diajukan'],
                    'kebutuhan_saat_ini' => $data['kebutuhan_saat_ini'],
                    'kapasitas_diajukan_kg' => $data['kapasitas_diajukan_kg'] ?? null,
                    'status_verif' => 'pending',
                    'is_active' => false,
                    'jumlah_mustahik_layak' => 0,
                    'kapasitas_penerimaan_kg' => null,
                    'wilayah_layanan_id' => null,
                    'jarak_jalan_km' => null,
                ]);

                if ($request->hasFile('foto')) {
                    $oldPaths = DB::table('foto_yayasan')->where('yayasan_id', $locked->id)->pluck('path_private')->all();
                    DB::table('foto_yayasan')->where('yayasan_id', $locked->id)->delete();
                    foreach ($request->file('foto') as $file) {
                        $path = $file->store('panti', 'local');
                        if (! $path) {
                            throw new RuntimeException('Foto panti gagal disimpan.');
                        }

                        $storedPaths[] = $path;
                        DB::table('foto_yayasan')->insert([
                            'yayasan_id' => $locked->id,
                            'diunggah_oleh' => $request->user()->id,
                            'path_private' => $path,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);

            throw $exception;
        }

        Storage::disk('local')->delete($oldPaths);

        return back()->with('success', 'Koreksi panti dikirim kembali untuk pemeriksaan panitia.');
    }
}
