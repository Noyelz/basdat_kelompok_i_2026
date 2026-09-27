<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class HandoverEvidenceController extends Controller
{
    public function index(): View
    {
        $alokasi = DB::table('alokasi_transaksi')
            ->join('transaksi_zakat', 'transaksi_zakat.id', '=', 'alokasi_transaksi.transaksi_id')
            ->join('yayasan', 'yayasan.id', '=', 'alokasi_transaksi.yayasan_id')
            ->leftJoin('bukti_serah_terima as bukti_panitia', function ($join): void {
                $join->on('bukti_panitia.alokasi_transaksi_id', '=', 'alokasi_transaksi.id')->where('bukti_panitia.pihak', 'panitia');
            })
            ->leftJoin('bukti_serah_terima as bukti_pj', function ($join): void {
                $join->on('bukti_pj.alokasi_transaksi_id', '=', 'alokasi_transaksi.id')->where('bukti_pj.pihak', 'yayasan');
            })
            ->orderByDesc('alokasi_transaksi.id')
            ->limit(100)
            ->get(['alokasi_transaksi.*', 'transaksi_zakat.kode_transaksi', 'transaksi_zakat.jenis_zakat', 'yayasan.nama as nama_panti', 'bukti_panitia.id as bukti_panitia_id', 'bukti_pj.id as bukti_pj_id']);

        return view('panitia.bukti', compact('alokasi'));
    }

    public function submitPanitia(Request $request, int $id): RedirectResponse
    {
        return $this->submit($request, $id, 'panitia');
    }

    public function submitPj(Request $request, int $id): RedirectResponse
    {
        return $this->submit($request, $id, 'yayasan');
    }

    public function foto(Request $request, int $id): StreamedResponse
    {
        $bukti = DB::table('bukti_serah_terima')
            ->join('alokasi_transaksi', 'alokasi_transaksi.id', '=', 'bukti_serah_terima.alokasi_transaksi_id')
            ->join('transaksi_zakat', 'transaksi_zakat.id', '=', 'alokasi_transaksi.transaksi_id')
            ->join('yayasan', 'yayasan.id', '=', 'alokasi_transaksi.yayasan_id')
            ->where('bukti_serah_terima.id', $id)
            ->first(['bukti_serah_terima.path_private', 'transaksi_zakat.user_id', 'yayasan.pj_user_id']);
        abort_unless($bukti, 404);

        $user = $request->user();
        abort_unless($user->is_active, 403);
        abort_unless(in_array($user->role, ['panitia', 'admin'], true) || $bukti->user_id === $user->id || $bukti->pj_user_id === $user->id, 403);
        abort_unless(Storage::disk('local')->exists($bukti->path_private), 404);

        return Storage::disk('local')->response($bukti->path_private, null, ['Cache-Control' => 'private, no-store']);
    }

    private function submit(Request $request, int $id, string $pihak): RedirectResponse
    {
        $alokasiAwal = DB::table('alokasi_transaksi')
            ->join('transaksi_zakat', 'transaksi_zakat.id', '=', 'alokasi_transaksi.transaksi_id')
            ->where('alokasi_transaksi.id', $id)
            ->first(['transaksi_zakat.jenis_zakat']);
        abort_unless($alokasiAwal, 404);

        $nilaiKolom = $alokasiAwal->jenis_zakat === 'fitrah' ? 'jumlah_kg' : 'nominal';
        $data = $request->validate([
            $nilaiKolom => ['required', 'decimal:0,2', 'gt:0'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        $path = $request->file('foto')->store('bukti-serah-terima', 'local');
        if (! $path) {
            throw new RuntimeException('Foto bukti gagal disimpan.');
        }

        $pathLama = null;
        try {
            DB::transaction(function () use ($request, $id, $pihak, $data, $path, $nilaiKolom, &$pathLama): void {
                $alokasi = DB::table('alokasi_transaksi')
                    ->join('transaksi_zakat', 'transaksi_zakat.id', '=', 'alokasi_transaksi.transaksi_id')
                    ->join('yayasan', 'yayasan.id', '=', 'alokasi_transaksi.yayasan_id')
                    ->where('alokasi_transaksi.id', $id)
                    ->lockForUpdate()
                    ->first(['alokasi_transaksi.*', 'transaksi_zakat.jenis_zakat', 'transaksi_zakat.status_pembayaran', 'transaksi_zakat.user_id', 'transaksi_zakat.kode_transaksi', 'yayasan.pj_user_id', 'yayasan.nama as nama_panti']);
                abort_unless($alokasi, 404);
                abort_unless($alokasi->status_pembayaran === 'simulated_paid', 422);
                abort_unless($pihak !== 'yayasan' || $alokasi->pj_user_id === $request->user()->id, 403);
                abort_unless($alokasi->status !== 'selesai', 422, 'Penyaluran sudah selesai.');
                abort_unless(in_array($alokasi->status, ['dikirim', 'menunggu_bukti', 'perlu_tinjauan'], true), 422, 'Alokasi belum siap untuk bukti.');
                abort_unless(($alokasi->jenis_zakat === 'fitrah' ? 'jumlah_kg' : 'nominal') === $nilaiKolom, 422);

                $titik = null;
                if ($alokasi->titik_pengiriman_id) {
                    $titik = DB::table('titik_pengiriman')->where('id', $alokasi->titik_pengiriman_id)->lockForUpdate()->first(['id', 'perjalanan_pengiriman_id']);
                    DB::table('perjalanan_pengiriman')->where('id', $titik->perjalanan_pengiriman_id)->lockForUpdate()->first(['id']);
                }

                $sesuai = bccomp($data[$nilaiKolom], $alokasi->$nilaiKolom, 2) === 0;
                $buktiLama = DB::table('bukti_serah_terima')->where('alokasi_transaksi_id', $id)->where('pihak', $pihak)->first();
                $baris = [
                    'diunggah_oleh' => $request->user()->id,
                    'path_private' => $path,
                    'jumlah_kg' => $nilaiKolom === 'jumlah_kg' ? $data[$nilaiKolom] : null,
                    'nominal' => $nilaiKolom === 'nominal' ? $data[$nilaiKolom] : null,
                    'jumlah_sesuai' => $sesuai,
                    'catatan' => $data['catatan'] ?? null,
                    'is_simulation' => true,
                    'updated_at' => now(),
                ];

                if ($buktiLama) {
                    $pathLama = $buktiLama->path_private;
                    DB::table('bukti_serah_terima')->where('id', $buktiLama->id)->update($baris);
                } else {
                    DB::table('bukti_serah_terima')->insert([
                        ...$baris,
                        'alokasi_transaksi_id' => $id,
                        'pihak' => $pihak,
                        'created_at' => now(),
                    ]);
                }

                $bukti = DB::table('bukti_serah_terima')->where('alokasi_transaksi_id', $id)->get(['pihak', 'jumlah_sesuai'])->keyBy('pihak');
                $lengkap = $bukti->has('panitia') && $bukti->has('yayasan');
                $bermasalah = $bukti->contains(fn ($item) => ! $item->jumlah_sesuai);
                $status = $bermasalah ? 'perlu_tinjauan' : ($lengkap ? 'selesai' : 'menunggu_bukti');

                DB::table('alokasi_transaksi')->where('id', $id)->update(['status' => $status, 'updated_at' => now()]);
                DB::table('transaksi_zakat')->where('id', $alokasi->transaksi_id)->update(['status_penyaluran' => $status, 'updated_at' => now()]);

                if ($status === 'selesai' && ! DB::table('penyaluran')->where('alokasi_transaksi_id', $id)->exists()) {
                    DB::table('penyaluran')->insert([
                        'yayasan_id' => $alokasi->yayasan_id,
                        'alokasi_transaksi_id' => $id,
                        'jenis_zakat' => $alokasi->jenis_zakat,
                        'barang' => $alokasi->jenis_zakat === 'fitrah' ? 'Beras zakat fitrah' : 'Dana zakat maal (simulasi)',
                        'jumlah' => $alokasi->$nilaiKolom,
                        'satuan' => $alokasi->jenis_zakat === 'fitrah' ? 'kg' : 'rupiah',
                        'status' => 'selesai',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $payload = json_encode([
                        'kode_transaksi' => $alokasi->kode_transaksi,
                        'transaksi_id' => $alokasi->transaksi_id,
                        'nama_panti' => $alokasi->nama_panti,
                    ], JSON_THROW_ON_ERROR);
                    foreach (array_unique([$alokasi->user_id, $alokasi->pj_user_id]) as $penerimaId) {
                        DB::table('notifikasi_aplikasi')->insert([
                            'user_id' => $penerimaId,
                            'jenis' => 'penyaluran_selesai',
                            'payload' => $payload,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                if ($titik) {
                    if ($pihak === 'panitia') {
                        DB::table('titik_pengiriman')->where('id', $alokasi->titik_pengiriman_id)->whereNull('tiba_at')->update(['tiba_at' => now(), 'updated_at' => now()]);
                    }

                    $belumSelesai = DB::table('alokasi_transaksi')->where('titik_pengiriman_id', $alokasi->titik_pengiriman_id)->where('status', '!=', 'selesai')->exists();
                    if (! $belumSelesai) {
                        DB::table('titik_pengiriman')->where('id', $alokasi->titik_pengiriman_id)->update(['status' => 'diterima', 'diterima_at' => now(), 'updated_at' => now()]);
                        $titikTertunda = DB::table('titik_pengiriman')->where('perjalanan_pengiriman_id', $titik->perjalanan_pengiriman_id)->where('status', '!=', 'diterima')->exists();
                        if (! $titikTertunda) {
                            DB::table('perjalanan_pengiriman')->where('id', $titik->perjalanan_pengiriman_id)->update(['status' => 'selesai', 'updated_at' => now()]);
                        }
                    }
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        if ($pathLama) {
            Storage::disk('local')->delete($pathLama);
        }

        return back()->with('success', 'Bukti tersimpan. Penyaluran selesai hanya jika bukti kedua pihak dan jumlahnya cocok.');
    }
}
