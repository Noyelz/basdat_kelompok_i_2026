<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PantiVerificationController extends Controller
{
    public function index(): View
    {
        $panti = DB::table('yayasan')
            ->join('user', 'user.id', '=', 'yayasan.pj_user_id')
            ->leftJoin('wilayah_layanan', 'wilayah_layanan.id', '=', 'yayasan.wilayah_layanan_id')
            ->select('yayasan.*', 'user.nama as nama_pj', 'wilayah_layanan.nama as nama_wilayah', 'wilayah_layanan.is_active as wilayah_aktif')
            ->orderByRaw("CASE WHEN yayasan.status_verif = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('yayasan.created_at')
            ->paginate(10);

        $foto = DB::table('foto_yayasan')
            ->whereIn('yayasan_id', $panti->pluck('id'))
            ->orderBy('id')
            ->get(['id', 'yayasan_id'])
            ->groupBy('yayasan_id');

        $wilayah = DB::table('wilayah_layanan')
            ->join('gudang', 'gudang.id', '=', 'wilayah_layanan.gudang_id')
            ->where('gudang.is_active', true)
            ->orderBy('wilayah_layanan.provinsi')
            ->get(['wilayah_layanan.id', 'wilayah_layanan.provinsi', 'wilayah_layanan.is_active']);

        return view('panitia.panti', compact('panti', 'foto', 'wilayah'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'hasil' => ['required', Rule::in(['verified', 'rejected'])],
            'jumlah_mustahik_layak' => ['required_if:hasil,verified', 'nullable', 'integer', 'min:1'],
            'fitrah_aktif' => ['required_if:hasil,verified', 'nullable', 'boolean'],
            'wilayah_layanan_id' => ['required_if:fitrah_aktif,1', 'nullable', 'integer', Rule::exists('wilayah_layanan', 'id')],
            'jarak_jalan_km' => ['required_if:fitrah_aktif,1', 'nullable', 'numeric', 'min:0.01', 'max:99999999.99'],
            'kapasitas_penerimaan_kg' => ['required_if:fitrah_aktif,1', 'nullable', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'catatan' => ['required_if:hasil,rejected', 'nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $id, $data): void {
            $panti = DB::table('yayasan')->where('id', $id)->lockForUpdate()->first();
            abort_unless($panti, 404);

            $terverifikasi = $data['hasil'] === 'verified';
            $fitrah = $terverifikasi && (bool) ($data['fitrah_aktif'] ?? false);
            if (! $terverifikasi) {
                $pesananTerbuka = DB::table('transaksi_zakat')
                    ->where('yayasan_id', $id)
                    ->whereIn('status_pembayaran', ['pending', 'simulated_paid'])
                    ->where('status_penyaluran', '!=', 'selesai')
                    ->exists();
                abort_if($pesananTerbuka, 422, 'Panti masih memiliki pesanan terbuka; selesaikan penyaluran sebelum menonaktifkannya.');
            }
            if ($terverifikasi) {
                abort_if($data['jumlah_mustahik_layak'] > $panti->jumlah_mustahik_diajukan || ! $panti->kebutuhan_saat_ini, 422, 'Jumlah yang disetujui tidak boleh melebihi yang diajukan dan kebutuhan PJ harus terisi.');
            }
            if ($fitrah) {
                $wilayah = DB::table('wilayah_layanan')
                    ->join('gudang', 'gudang.id', '=', 'wilayah_layanan.gudang_id')
                    ->where('wilayah_layanan.id', $data['wilayah_layanan_id'])
                    ->where('wilayah_layanan.provinsi', $panti->provinsi)
                    ->where('gudang.is_active', true)
                    ->first(['wilayah_layanan.id']);
                abort_unless($wilayah, 422, 'Wilayah panti harus sesuai dengan provinsi dan gudang yang aktif.');
            }

            DB::table('yayasan')->where('id', $id)->update([
                'status_verif' => $data['hasil'],
                'is_active' => $terverifikasi,
                'jumlah_mustahik_layak' => $terverifikasi ? $data['jumlah_mustahik_layak'] : 0,
                'wilayah_layanan_id' => $fitrah ? $data['wilayah_layanan_id'] : null,
                'jarak_jalan_km' => $fitrah ? $data['jarak_jalan_km'] : null,
                'kapasitas_penerimaan_kg' => $fitrah ? $data['kapasitas_penerimaan_kg'] : null,
                'updated_at' => now(),
            ]);

            DB::table('verifikasi_panti')->insert([
                'yayasan_id' => $id,
                'panitia_id' => $request->user()->id,
                'hasil' => $data['hasil'],
                'jumlah_mustahik_layak' => $terverifikasi ? $data['jumlah_mustahik_layak'] : 0,
                'catatan' => $data['catatan'] ?? null,
                'kapasitas_penerimaan_kg' => $fitrah ? $data['kapasitas_penerimaan_kg'] : null,
                'kebutuhan_snapshot' => $panti->kebutuhan_saat_ini,
                'diperiksa_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('audit_log')->insert([
                'actor_user_id' => $request->user()->id,
                'aksi' => $terverifikasi ? 'panti.diverifikasi' : 'panti.ditolak',
                'jenis_objek' => 'yayasan',
                'objek_id' => $id,
                'detail' => json_encode([
                    'hasil' => $data['hasil'],
                    'jumlah_mustahik_layak' => $terverifikasi ? $data['jumlah_mustahik_layak'] : 0,
                    'fitrah_aktif' => $fitrah,
                ], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Hasil pemeriksaan panti tersimpan.');
    }

    public function foto(Request $request, int $id): StreamedResponse
    {
        $foto = DB::table('foto_yayasan')->where('id', $id)->first();
        abort_unless($foto, 404);

        $role = $request->user()->role;
        abort_unless($request->user()->is_active, 403);
        $milikPj = $role === 'pj' && DB::table('yayasan')
            ->where('id', $foto->yayasan_id)
            ->where('pj_user_id', $request->user()->id)
            ->exists();
        abort_unless(in_array($role, ['panitia', 'admin'], true) || $milikPj, 403);
        abort_unless(Storage::disk('local')->exists($foto->path_private), 404);

        return Storage::disk('local')->response($foto->path_private);
    }
}
