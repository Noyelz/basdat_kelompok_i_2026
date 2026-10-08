<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        $panitia = User::query()
            ->where('role', 'panitia')
            ->orderBy('nama')
            ->get(['id', 'nama', 'email', 'is_active', 'created_at']);
        $bank = DB::table('bank')->orderBy('nama')->get(['id', 'kode', 'nama', 'is_active']);
        $wilayah = DB::table('wilayah_layanan')->orderBy('hari_batas_fitrah')->get()
            ->map(function ($item) {
                $item->tarif = DB::table('tarif_pengiriman')
                    ->where('wilayah_layanan_id', $item->id)
                    ->whereNull('berlaku_sampai')
                    ->latest('id')
                    ->first();
                $item->dapat_diaktifkan = $item->batas_checkout_at
                    && CarbonImmutable::parse($item->batas_checkout_at)->isFuture();

                return $item;
            });
        $pengaturan = DB::table('pengaturan_operasional')->pluck('nilai', 'kunci');
        $hargaEmas = DB::table('harga_emas')->latest('id')->first();

        $kontenPublik = collect(LandingController::DEFAULT_CONTENT)
            ->mapWithKeys(fn (string $nilai, string $kunci): array => [$kunci => $pengaturan[$kunci] ?? $nilai]);

        return view('admin.dashboard', compact('panitia', 'bank', 'wilayah', 'pengaturan', 'hargaEmas', 'kontenPublik'));
    }

    public function auditLog(): View
    {
        $riwayat = DB::table('audit_log')
            ->leftJoin('user as pelaku', 'pelaku.id', '=', 'audit_log.actor_user_id')
            ->orderByDesc('audit_log.id')
            ->select('audit_log.id', 'audit_log.aksi', 'audit_log.jenis_objek', 'audit_log.objek_id', 'audit_log.created_at', 'pelaku.nama as nama_pelaku', 'pelaku.role as peran_pelaku')
            ->paginate(30);

        return view('admin.audit', compact('riwayat'));
    }

    public function setLandingContent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'landing_judul' => ['required', 'string', 'max:140'],
            'landing_pengantar' => ['required', 'string', 'max:600'],
            'landing_tentang' => ['required', 'string', 'max:1200'],
            'landing_faq_judul' => ['required', 'string', 'max:180'],
            'landing_faq_jawaban' => ['required', 'string', 'max:800'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            foreach ($data as $kunci => $nilai) {
                DB::table('pengaturan_operasional')->upsert([[
                    'kunci' => $kunci,
                    'nilai' => $nilai,
                    'satuan' => null,
                    'diubah_oleh' => $request->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]], ['kunci'], ['nilai', 'diubah_oleh', 'updated_at']);
            }

            DB::table('audit_log')->insert([
                'actor_user_id' => $request->user()->id,
                'aksi' => 'konten_publik.diubah',
                'jenis_objek' => 'pengaturan_operasional',
                'objek_id' => null,
                'detail' => json_encode(['kunci' => array_keys($data)], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return redirect()->route('admin.dashboard')->with('success', 'Konten halaman depan diperbarui.');
    }

    public function storePanitia(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:191', Rule::unique('user', 'email')],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $panitia = User::create([
                'nama' => $data['nama'],
                'email' => $data['email'],
                'pass_hash' => Hash::make($data['password']),
                'role' => 'panitia',
                'is_active' => true,
                'must_change_password' => true,
                'created_by_admin_id' => $request->user()->id,
            ]);

            DB::table('audit_log')->insert([
                'actor_user_id' => $request->user()->id,
                'aksi' => 'panitia.dibuat',
                'jenis_objek' => 'user',
                'objek_id' => $panitia->id,
                'detail' => json_encode(['email' => $panitia->email], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Akun panitia dibuat. Berikan kata sandi sementara melalui jalur pribadi.');
    }

    public function setPanitiaActive(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'panitia', 404);

        $data = $request->validate(['is_active' => ['required', 'boolean']]);

        DB::transaction(function () use ($user, $request, $data): void {
            $user->update(['is_active' => (bool) $data['is_active']]);

            DB::table('audit_log')->insert([
                'actor_user_id' => $request->user()->id,
                'aksi' => $user->is_active ? 'panitia.diaktifkan' : 'panitia.dinonaktifkan',
                'jenis_objek' => 'user',
                'objek_id' => $user->id,
                'detail' => json_encode(['email' => $user->email], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Status akun panitia diperbarui.');
    }

    public function storeBank(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('bank', 'kode')],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $bankId = DB::table('bank')->insertGetId([
                'kode' => $data['kode'],
                'nama' => $data['nama'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('audit_log')->insert([
                'actor_user_id' => $request->user()->id,
                'aksi' => 'bank.ditambahkan',
                'jenis_objek' => 'bank',
                'objek_id' => $bankId,
                'detail' => json_encode(['kode' => $data['kode'], 'nama' => $data['nama']], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Bank ditambahkan.');
    }

    public function setBankActive(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);

        DB::transaction(function () use ($request, $data, $id): void {
            $bank = DB::table('bank')->where('id', $id)->lockForUpdate()->first(['id', 'kode', 'is_active']);
            abort_unless($bank, 404);

            DB::table('bank')->where('id', $id)->update([
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

            DB::table('audit_log')->insert([
                'actor_user_id' => $request->user()->id,
                'aksi' => $data['is_active'] ? 'bank.diaktifkan' : 'bank.dinonaktifkan',
                'jenis_objek' => 'bank',
                'objek_id' => $id,
                'detail' => json_encode(['kode' => $bank->kode], JSON_THROW_ON_ERROR),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Status bank diperbarui. Rekening panti yang sudah ada tetap tercatat.');
    }

    public function setOperationalSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal_idulfitri' => ['required', 'date_format:Y-m-d'],
            'biaya_admin_rupiah' => ['required', 'integer', 'min:0', 'max:1000000'],
            'persen_keuntungan_beras' => ['required', 'decimal:0,2', 'min:0', 'max:100'],
            'bobot_fitrah_per_jiwa_kg' => ['required', 'decimal:0,2', 'gt:0', 'max:10'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            foreach ($data as $key => $value) {
                DB::table('pengaturan_operasional')->upsert([[
                    'kunci' => $key,
                    'nilai' => (string) $value,
                    'satuan' => $key === 'tanggal_idulfitri' ? 'tanggal' : null,
                    'diubah_oleh' => $request->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]], ['kunci'], ['nilai', 'satuan', 'diubah_oleh', 'updated_at']);
            }

            foreach (DB::table('wilayah_layanan')->get(['id', 'hari_batas_fitrah', 'is_active']) as $wilayah) {
                $batas = CarbonImmutable::parse($data['tanggal_idulfitri'].' 00:00:00', 'Asia/Jakarta')
                    ->subDays($wilayah->hari_batas_fitrah)
                    ->endOfDay()
                    ->utc();

                DB::table('wilayah_layanan')->where('id', $wilayah->id)->update([
                    'batas_checkout_at' => $batas,
                    'is_active' => $batas->isPast() ? false : $wilayah->is_active,
                    'updated_at' => now(),
                ]);
            }

            $pengali = bcadd('1', bcdiv($data['persen_keuntungan_beras'], '100', 6), 6);
            $produk = DB::table('katalog_beras')->orderBy('id')->lockForUpdate()->get(['id', 'harga_per_kg']);
            foreach ($produk as $item) {
                $lotFifo = DB::table('lot_beras')
                    ->where('katalog_beras_id', $item->id)
                    ->where('sisa_kg', '>', 0)
                    ->orderBy('tanggal_masuk')
                    ->orderBy('id')
                    ->first(['harga_beli_per_kg']);
                if (! $lotFifo) {
                    continue;
                }

                $hargaPenawaran = bcadd(bcmul($lotFifo->harga_beli_per_kg, $pengali, 3), '0.005', 2);
                if (bccomp($item->harga_per_kg, $hargaPenawaran, 2) === 0) {
                    continue;
                }

                DB::table('katalog_beras')->where('id', $item->id)->update(['harga_per_kg' => $hargaPenawaran, 'updated_at' => now()]);
                DB::table('riwayat_harga_katalog')->insert([
                    'katalog_beras_id' => $item->id,
                    'harga_pokok_per_kg' => $lotFifo->harga_beli_per_kg,
                    'persen_keuntungan' => $data['persen_keuntungan_beras'],
                    'harga_penawaran_per_kg' => $hargaPenawaran,
                    'berlaku_mulai' => now(),
                    'ditetapkan_oleh' => $request->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Pengaturan tersimpan. Tenggat dan harga katalog untuk pesanan baru dihitung ulang.');
    }

    public function setRegion(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
            'biaya_tetap' => ['required', 'decimal:0,2', 'min:0', 'max:100000000'],
            'biaya_per_km_kg' => ['required', 'decimal:0,2', 'min:0', 'max:1000000'],
        ]);

        DB::transaction(function () use ($request, $data, $id): void {
            $wilayah = DB::table('wilayah_layanan')->where('id', $id)->lockForUpdate()->first();
            abort_unless($wilayah, 404);
            if ($data['is_active'] && (! $wilayah->batas_checkout_at || CarbonImmutable::parse($wilayah->batas_checkout_at)->isPast())) {
                throw ValidationException::withMessages([
                    'is_active' => 'Simpan tanggal Idulfitri di Pengaturan operasional terlebih dahulu, lalu pastikan batas checkout '.$wilayah->nama.' belum lewat.',
                ]);
            }

            DB::table('wilayah_layanan')->where('id', $id)->update([
                'is_active' => (bool) $data['is_active'],
                'updated_at' => now(),
            ]);

            $tarifAktif = DB::table('tarif_pengiriman')
                ->where('wilayah_layanan_id', $id)
                ->whereNull('berlaku_sampai')
                ->latest('id')
                ->first();

            if (! $tarifAktif || bccomp($tarifAktif->biaya_tetap, $data['biaya_tetap'], 2) !== 0 || bccomp($tarifAktif->biaya_per_km_kg, $data['biaya_per_km_kg'], 2) !== 0) {
                DB::table('tarif_pengiriman')->where('wilayah_layanan_id', $id)->whereNull('berlaku_sampai')->update(['berlaku_sampai' => now(), 'updated_at' => now()]);
                DB::table('tarif_pengiriman')->insert([
                    'wilayah_layanan_id' => $id,
                    'biaya_tetap' => $data['biaya_tetap'],
                    'biaya_per_km_kg' => $data['biaya_per_km_kg'],
                    'berlaku_mulai' => now(),
                    'ditetapkan_oleh' => $request->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Status wilayah dan tarif diperbarui.');
    }

    public function storeGoldPrice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'harga_per_gram' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999999.99'],
            'sumber' => ['required', 'string', 'max:255'],
            'tanggal_sumber' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        DB::table('harga_emas')->insert([
            'harga_per_gram' => $data['harga_per_gram'],
            'sumber' => $data['sumber'],
            'tanggal_sumber' => CarbonImmutable::parse($data['tanggal_sumber'].' 12:00:00', 'Asia/Jakarta')->utc(),
            'dicatat_oleh' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Harga emas acuan dicatat untuk transaksi baru.');
    }
}
