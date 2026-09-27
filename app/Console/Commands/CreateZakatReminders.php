<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:create-zakat-reminders')]
#[Description('Buat pengingat fitrah dan peninjauan haul maal di aplikasi sesuai pilihan pengguna')]
class CreateZakatReminders extends Command
{
    public function handle(): int
    {
        $hariIni = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $tanggalIdulfitri = DB::table('pengaturan_operasional')->where('kunci', 'tanggal_idulfitri')->value('nilai');
        $waktunyaFitrah = $tanggalIdulfitri
            && CarbonImmutable::parse($tanggalIdulfitri, 'Asia/Jakarta')->subDays(14)->isSameDay($hariIni);

        $pemilikHaul = DB::table('subjek_zakat_maal')
            ->whereNull('transaksi_id')
            ->where('is_snapshot', false)
            ->whereNotNull('tanggal_haul')
            ->whereMonth('tanggal_haul', $hariIni->month)
            ->whereDay('tanggal_haul', $hariIni->day)
            ->distinct()
            ->pluck('user_id')
            ->flip();

        $pengguna = DB::table('preferensi_notifikasi')
            ->join('user', 'user.id', '=', 'preferensi_notifikasi.user_id')
            ->where('user.is_active', true)
            ->where(function ($query): void {
                $query->where('preferensi_notifikasi.ingatkan_fitrah', true)
                    ->orWhere('preferensi_notifikasi.ingatkan_haul_maal', true);
            })
            ->orderBy('preferensi_notifikasi.user_id')
            ->get(['preferensi_notifikasi.user_id', 'preferensi_notifikasi.ingatkan_fitrah', 'preferensi_notifikasi.ingatkan_haul_maal']);

        $jumlah = 0;
        foreach ($pengguna as $item) {
            if ($waktunyaFitrah && $item->ingatkan_fitrah) {
                $jumlah += (int) $this->buatJikaBelumAda(
                    $item->user_id,
                    'pengingat_fitrah',
                    ['pesan' => 'Idulfitri sekitar 14 hari lagi. Tinjau kebutuhan zakat fitrah dan tenggat wilayah tujuan sebelum membuat pesanan.', 'tanggal_idulfitri' => $tanggalIdulfitri],
                    $hariIni,
                    'ingatkan_fitrah',
                );
            }

            if ($item->ingatkan_haul_maal && $pemilikHaul->has($item->user_id)) {
                $jumlah += (int) $this->buatJikaBelumAda(
                    $item->user_id,
                    'pengingat_haul_maal',
                    ['pesan' => 'Tanggal haul yang Anda catat berulang hari ini. Tinjau harta dan nisab terbaru; pengingat ini tidak menyatakan zakat sudah wajib.'],
                    $hariIni,
                    'ingatkan_haul_maal',
                );
            }
        }

        $this->info("{$jumlah} pengingat baru dibuat di aplikasi.");

        return self::SUCCESS;
    }

    /** @param array<string, string> $payload */
    private function buatJikaBelumAda(int $userId, string $jenis, array $payload, CarbonImmutable $hariIni, string $kolomPreferensi): bool
    {
        return DB::transaction(function () use ($userId, $jenis, $payload, $hariIni, $kolomPreferensi): bool {
            $preferensi = DB::table('preferensi_notifikasi')->where('user_id', $userId)->lockForUpdate()->first([$kolomPreferensi]);
            if (! $preferensi || ! $preferensi->$kolomPreferensi) {
                return false;
            }

            $awalHariUtc = $hariIni->setTimezone('UTC');
            $akhirHariUtc = $hariIni->addDay()->setTimezone('UTC');
            $sudahAda = DB::table('notifikasi_aplikasi')
                ->where('user_id', $userId)
                ->where('jenis', $jenis)
                ->where('created_at', '>=', $awalHariUtc)
                ->where('created_at', '<', $akhirHariUtc)
                ->exists();
            if ($sudahAda) {
                return false;
            }

            DB::table('notifikasi_aplikasi')->insert([
                'user_id' => $userId,
                'jenis' => $jenis,
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        });
    }
}
