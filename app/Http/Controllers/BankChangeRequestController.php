<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankChangeRequestController extends Controller
{
    public function index(): View
    {
        $permohonan = DB::table('permohonan_perubahan_rekening as permohonan')
            ->join('yayasan', 'yayasan.id', '=', 'permohonan.yayasan_id')
            ->join('bank', 'bank.id', '=', 'permohonan.bank_id')
            ->join('user', 'user.id', '=', 'permohonan.diajukan_oleh')
            ->select('permohonan.*', 'yayasan.nama as nama_panti', 'yayasan.nama_bank as bank_lama', 'yayasan.no_rek as rekening_lama', 'yayasan.atas_nama_rek as pemilik_lama', 'bank.nama as bank_baru', 'bank.is_active as bank_aktif', 'user.nama as nama_pj')
            ->orderByRaw("CASE WHEN permohonan.status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('permohonan.id')
            ->paginate(20);

        return view('panitia.rekening', compact('permohonan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bank_id' => ['required', 'integer', Rule::exists('bank', 'id')->where('is_active', true)],
            'no_rek' => ['required', 'string', 'regex:/^[0-9]{6,30}$/'],
            'atas_nama_rek' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $panti = DB::table('yayasan')->where('pj_user_id', $request->user()->id)->lockForUpdate()->first();
            abort_unless($panti, 404, 'Daftarkan panti sebelum mengajukan perubahan rekening.');
            abort_if(DB::table('permohonan_perubahan_rekening')->where('yayasan_id', $panti->id)->where('status', 'pending')->exists(), 409, 'Permohonan sebelumnya masih menunggu pemeriksaan.');
            abort_if((int) $panti->bank_id === (int) $data['bank_id'] && $panti->no_rek === $data['no_rek'] && $panti->atas_nama_rek === $data['atas_nama_rek'], 422, 'Rekening yang diajukan sama dengan rekening aktif.');
            abort_unless(DB::table('bank')->where('id', $data['bank_id'])->where('is_active', true)->exists(), 422);

            $id = DB::table('permohonan_perubahan_rekening')->insertGetId([
                'yayasan_id' => $panti->id,
                'diajukan_oleh' => $request->user()->id,
                'bank_id' => $data['bank_id'],
                'no_rek' => $data['no_rek'],
                'atas_nama_rek' => $data['atas_nama_rek'],
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('audit_log')->insert([
                'actor_user_id' => $request->user()->id,
                'aksi' => 'rekening.diajukan',
                'jenis_objek' => 'permohonan_perubahan_rekening',
                'objek_id' => $id,
                'detail' => json_encode(['yayasan_id' => $panti->id], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Permohonan rekening dikirim. Rekening aktif belum berubah sampai disetujui panitia.');
    }

    public function review(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'hasil' => ['required', Rule::in(['approved', 'rejected'])],
            'catatan' => ['required_if:hasil,rejected', 'nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $id, $data): void {
            $permohonan = DB::table('permohonan_perubahan_rekening')->where('id', $id)->lockForUpdate()->first();
            abort_unless($permohonan, 404);
            abort_unless($permohonan->status === 'pending', 409, 'Permohonan ini sudah diperiksa.');
            $panti = DB::table('yayasan')->where('id', $permohonan->yayasan_id)->lockForUpdate()->first();
            abort_unless($panti, 404);

            if ($data['hasil'] === 'approved') {
                $bank = DB::table('bank')->where('id', $permohonan->bank_id)->where('is_active', true)->first(['nama']);
                abort_unless($bank, 422, 'Bank tujuan sudah tidak aktif. Tolak permohonan dan minta PJ mengajukan ulang.');
                DB::table('yayasan')->where('id', $panti->id)->update([
                    'bank_id' => $permohonan->bank_id,
                    'nama_bank' => $bank->nama,
                    'no_rek' => $permohonan->no_rek,
                    'atas_nama_rek' => $permohonan->atas_nama_rek,
                    'updated_at' => now(),
                ]);
            }

            DB::table('permohonan_perubahan_rekening')->where('id', $id)->update([
                'status' => $data['hasil'],
                'catatan' => $data['catatan'] ?? null,
                'diperiksa_oleh' => $request->user()->id,
                'diperiksa_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('audit_log')->insert([
                'actor_user_id' => $request->user()->id,
                'aksi' => $data['hasil'] === 'approved' ? 'rekening.disetujui' : 'rekening.ditolak',
                'jenis_objek' => 'permohonan_perubahan_rekening',
                'objek_id' => $id,
                'detail' => json_encode(['yayasan_id' => $panti->id], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Hasil pemeriksaan rekening tersimpan.');
    }
}
