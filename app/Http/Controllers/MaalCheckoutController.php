<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaalCheckoutController extends Controller
{
    public function index(Request $request): View
    {
        $hargaEmas = DB::table('harga_emas')->latest('tanggal_sumber')->first();
        $hargaBerlaku = $hargaEmas && CarbonImmutable::parse($hargaEmas->tanggal_sumber)->greaterThanOrEqualTo(now()->subDays(7));
        $panti = DB::table('yayasan')
            ->where('status_verif', 'verified')
            ->where('is_active', true)
            ->where('jumlah_mustahik_layak', '>', 0)
            ->orderBy('provinsi')
            ->orderBy('kota_kabupaten')
            ->get(['id', 'nama', 'kota_kabupaten', 'provinsi', 'jumlah_mustahik_layak']);

        $draftSubjects = DB::table('subjek_zakat_maal')
            ->where('user_id', $request->user()->id)
            ->whereNull('transaksi_id')
            ->where('is_snapshot', false)
            ->orderBy('id')
            ->get(['hubungan', 'nama_tampilan', 'tabungan', 'emas_gram', 'investasi', 'aset_dagang', 'piutang', 'utang', 'tanggal_haul', 'sudah_haul'])
            ->map(fn ($item): array => [...(array) $item, 'sudah_haul' => false])
            ->all();

        return view('muzakki.maal', compact('hargaEmas', 'hargaBerlaku', 'panti', 'draftSubjects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateSubjects($request);
        $data['yayasan_id'] = $request->validate([
            'yayasan_id' => ['nullable', 'integer', Rule::exists('yayasan', 'id')],
        ])['yayasan_id'] ?? null;

        $id = DB::transaction(function () use ($request, $data): int {
            $hargaEmas = DB::table('harga_emas')->latest('tanggal_sumber')->first();
            abort_unless($hargaEmas && CarbonImmutable::parse($hargaEmas->tanggal_sumber)->greaterThanOrEqualTo(now()->subDays(7)), 422, 'Harga emas acuan tidak tersedia atau lebih dari tujuh hari.');

            $pantiLayak = DB::table('yayasan')
                ->where('status_verif', 'verified')
                ->where('is_active', true)
                ->where('jumlah_mustahik_layak', '>', 0);

            if (! empty($data['yayasan_id'])) {
                $panti = (clone $pantiLayak)->where('id', $data['yayasan_id'])->first(['id']);
                abort_unless($panti, 422, 'Panti pilihan belum memenuhi syarat.');
                $otomatis = false;
            } else {
                $pernahMenerima = DB::table('transaksi_zakat')
                    ->where('jenis_zakat', 'mal')
                    ->where('status_pembayaran', 'simulated_paid')
                    ->distinct()
                    ->pluck('yayasan_id');
                $panti = (clone $pantiLayak)->whereNotIn('id', $pernahMenerima)->inRandomOrder()->first(['id'])
                    ?? (clone $pantiLayak)->inRandomOrder()->first(['id']);
                abort_unless($panti, 422, 'Belum ada panti terverifikasi yang dapat dipilih.');
                $otomatis = true;
            }

            $nisab = bcmul('85', $hargaEmas->harga_per_gram, 2);
            $agregat = ['tabungan' => '0', 'emas_gram' => '0', 'investasi' => '0', 'aset_dagang' => '0', 'piutang' => '0', 'utang' => '0', 'harta_bersih' => '0', 'zakat_wajib' => '0'];
            $rincian = [];

            foreach ($data['subjek'] as $subjek) {
                $emasRupiah = bcadd(bcmul($subjek['emas_gram'], $hargaEmas->harga_per_gram, 3), '0.005', 2);
                $jumlahAset = bcadd(bcadd(bcadd($subjek['tabungan'], $emasRupiah, 2), bcadd($subjek['investasi'], $subjek['aset_dagang'], 2), 2), $subjek['piutang'], 2);
                $bersih = bcsub($jumlahAset, $subjek['utang'], 2);
                if (bccomp($bersih, '0', 2) < 0) {
                    $bersih = '0.00';
                }

                $sudahHaul = (bool) ($subjek['sudah_haul'] ?? false);
                $wajib = $sudahHaul && bccomp($bersih, $nisab, 2) >= 0;
                $zakat = $wajib ? bcadd(bcdiv(bcmul($bersih, '2.5', 3), '100', 3), '0.005', 2) : '0.00';
                $rincian[] = [...$subjek, 'harta_bersih' => $bersih, 'zakat_wajib' => $zakat, 'sudah_haul' => $sudahHaul];

                foreach (['tabungan', 'emas_gram', 'investasi', 'aset_dagang', 'piutang', 'utang'] as $kolom) {
                    $agregat[$kolom] = bcadd($agregat[$kolom], $subjek[$kolom], 2);
                }
                $agregat['harta_bersih'] = bcadd($agregat['harta_bersih'], $bersih, 2);
                $agregat['zakat_wajib'] = bcadd($agregat['zakat_wajib'], $zakat, 2);
            }

            if (bccomp($agregat['zakat_wajib'], '0', 2) <= 0) {
                throw ValidationException::withMessages(['subjek' => 'Belum ada zakat maal wajib: periksa nisab dan pernyataan haul setiap pemilik harta. Tidak dibuat pesanan.']);
            }

            $kedaluwarsa = now()->addMinutes(30);
            $token = Str::random(64);
            $transaksiId = DB::table('transaksi_zakat')->insertGetId([
                'kode_transaksi' => 'ZM-'.Str::ulid(),
                'user_id' => $request->user()->id,
                'yayasan_id' => $panti->id,
                'jenis_zakat' => 'mal',
                'harga_pokok_snapshot' => 0,
                'keuntungan_snapshot' => 0,
                'biaya_admin_snapshot' => 0,
                'ongkir_snapshot' => 0,
                'total_nominal' => $agregat['zakat_wajib'],
                'mode_pembayaran' => 'simulation',
                'metode_pembayaran' => 'QR simulasi',
                'status_pembayaran' => 'pending',
                'status_penyaluran' => 'menunggu_alokasi',
                'qr_payload' => Crypt::encryptString($token),
                'expired_at' => $kedaluwarsa,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('transaksi_mal')->insert([
                'transaksi_id' => $transaksiId,
                'harga_emas_id' => $hargaEmas->id,
                'nilai_tabungan' => $agregat['tabungan'],
                'emas_gram' => $agregat['emas_gram'],
                'harga_emas_per_gram_snapshot' => $hargaEmas->harga_per_gram,
                'sumber_harga_emas_snapshot' => $hargaEmas->sumber,
                'nilai_investasi' => $agregat['investasi'],
                'nilai_aset_dagang' => $agregat['aset_dagang'],
                'nilai_piutang' => $agregat['piutang'],
                'nilai_utang' => $agregat['utang'],
                'total_harta_bersih' => $agregat['harta_bersih'],
                'nilai_nisab' => $nisab,
                'wajib_zakat' => true,
                'total_zakat_wajib' => $agregat['zakat_wajib'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($rincian as $subjek) {
                DB::table('subjek_zakat_maal')->insert([
                    'user_id' => $request->user()->id,
                    'transaksi_id' => $transaksiId,
                    'hubungan' => $subjek['hubungan'],
                    'nama_tampilan' => trim($subjek['nama_tampilan'] ?? '') !== '' ? $subjek['nama_tampilan'] : 'Hamba Allah',
                    'tabungan' => $subjek['tabungan'],
                    'emas_gram' => $subjek['emas_gram'],
                    'investasi' => $subjek['investasi'],
                    'aset_dagang' => $subjek['aset_dagang'],
                    'piutang' => $subjek['piutang'],
                    'utang' => $subjek['utang'],
                    'harga_emas_snapshot' => $hargaEmas->harga_per_gram,
                    'nisab_snapshot' => $nisab,
                    'harta_bersih' => $subjek['harta_bersih'],
                    'zakat_wajib' => $subjek['zakat_wajib'],
                    'tanggal_haul' => $subjek['tanggal_haul'] ?? null,
                    'sudah_haul' => $subjek['sudah_haul'],
                    'is_snapshot' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('percobaan_pembayaran')->insert([
                'transaksi_id' => $transaksiId,
                'token_hash' => hash('sha256', $token),
                'status' => 'pending',
                'expired_at' => $kedaluwarsa,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($otomatis) {
                DB::table('audit_log')->insert([
                    'actor_user_id' => $request->user()->id,
                    'aksi' => 'maal.panti_dipilih_otomatis',
                    'jenis_objek' => 'transaksi_zakat',
                    'objek_id' => $transaksiId,
                    'detail' => json_encode(['yayasan_id' => $panti->id], JSON_THROW_ON_ERROR),
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                ]);
            }

            return $transaksiId;
        });

        return redirect()->route('muzakki.payment.show', $id);
    }

    public function saveDraft(Request $request): RedirectResponse
    {
        $data = $this->validateSubjects($request);

        DB::transaction(function () use ($request, $data): void {
            DB::table('user')->where('id', $request->user()->id)->lockForUpdate()->first(['id']);
            DB::table('subjek_zakat_maal')
                ->where('user_id', $request->user()->id)
                ->whereNull('transaksi_id')
                ->where('is_snapshot', false)
                ->delete();

            foreach ($data['subjek'] as $subjek) {
                DB::table('subjek_zakat_maal')->insert([
                    'user_id' => $request->user()->id,
                    'transaksi_id' => null,
                    'hubungan' => $subjek['hubungan'],
                    'nama_tampilan' => trim($subjek['nama_tampilan'] ?? '') !== '' ? $subjek['nama_tampilan'] : 'Hamba Allah',
                    'tabungan' => $subjek['tabungan'],
                    'emas_gram' => $subjek['emas_gram'],
                    'investasi' => $subjek['investasi'],
                    'aset_dagang' => $subjek['aset_dagang'],
                    'piutang' => $subjek['piutang'],
                    'utang' => $subjek['utang'],
                    'tanggal_haul' => $subjek['tanggal_haul'] ?? null,
                    'sudah_haul' => (bool) ($subjek['sudah_haul'] ?? false),
                    'is_snapshot' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return redirect()->route('muzakki.maal')->with('success', 'Draft keluarga disimpan. Harga emas, nisab, dan kewajiban akan dihitung ulang saat checkout.');
    }

    /** @return array{subjek: array<int|string, array<string, mixed>>} */
    private function validateSubjects(Request $request): array
    {
        $uang = ['required', 'decimal:0,2', 'min:0', 'max:999999999999.99'];
        $data = $request->validate([
            'subjek' => ['required', 'array', 'min:1', 'max:10'],
            'subjek.*.hubungan' => ['required', Rule::in(['diri', 'istri', 'anak'])],
            'subjek.*.nama_tampilan' => ['nullable', 'string', 'max:255'],
            'subjek.*.tabungan' => $uang,
            'subjek.*.emas_gram' => ['required', 'decimal:0,2', 'min:0', 'max:100000'],
            'subjek.*.investasi' => $uang,
            'subjek.*.aset_dagang' => $uang,
            'subjek.*.piutang' => $uang,
            'subjek.*.utang' => $uang,
            'subjek.*.tanggal_haul' => ['nullable', 'date', 'before_or_equal:today'],
            'subjek.*.sudah_haul' => ['nullable', 'boolean'],
        ]);

        $hubungan = collect($data['subjek'])->pluck('hubungan');
        if ($hubungan->filter(fn ($value) => $value === 'diri')->count() !== 1 || $hubungan->filter(fn ($value) => $value === 'istri')->count() > 1) {
            throw ValidationException::withMessages(['subjek' => 'Masukkan tepat satu pemilik diri sendiri dan paling banyak satu istri.']);
        }

        return $data;
    }
}
