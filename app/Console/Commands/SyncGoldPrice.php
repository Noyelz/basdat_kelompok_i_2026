<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

#[Signature('app:sync-gold-price')]
#[Description('Simpan acuan emas dunia dalam rupiah per gram sekali sehari dari Gold API dan Frankfurter')]
class SyncGoldPrice extends Command
{
    private const GRAMS_PER_TROY_OUNCE = '31.1034768';

    public function handle(): int
    {
        if (! config('services.gold_price.enabled')) {
            $this->warn('Sinkronisasi harga emas otomatis dinonaktifkan.');

            return self::SUCCESS;
        }

        $lock = Cache::lock('gold-price:sync', 180);
        if (! $lock->get()) {
            $this->warn('Sinkronisasi harga emas lain masih berjalan.');

            return self::SUCCESS;
        }

        try {
            $tanggalSinkronisasi = CarbonImmutable::now(config('services.gold_price.timezone'))->toDateString();
            if (DB::table('harga_emas')->where('tanggal_sinkronisasi', $tanggalSinkronisasi)->exists()) {
                $this->info('Acuan emas otomatis hari ini sudah tersimpan; tidak mengambil API lagi.');

                return self::SUCCESS;
            }

            $gold = $this->httpClient()->get(config('services.gold_price.gold_url'))->throw()->json();
            $exchange = $this->httpClient()->get(config('services.gold_price.exchange_url'))->throw()->json();

            if (! is_array($gold) || ($gold['symbol'] ?? null) !== 'XAU' || ($gold['currency'] ?? null) !== 'USD') {
                throw new RuntimeException('Respons emas harus berupa XAU dalam USD per troy ounce.');
            }

            if (! is_array($exchange) || ($exchange['base'] ?? null) !== 'USD' || ($exchange['quote'] ?? null) !== 'IDR') {
                throw new RuntimeException('Respons kurs harus berupa USD ke IDR.');
            }

            $hargaUsd = $this->positiveDecimal($gold['price'] ?? null, 'Harga emas USD');
            $kurs = $this->positiveDecimal($exchange['rate'] ?? null, 'Kurs USD/IDR');
            $waktuEmas = $this->goldTimestamp($gold['updatedAt'] ?? null);
            $tanggalKurs = $this->exchangeDate($exchange['date'] ?? null);
            $diambilAt = CarbonImmutable::now('UTC');
            $batasUsia = $diambilAt->subDays((int) config('services.gold_price.max_age_days'));

            if ($waktuEmas->lessThan($batasUsia) || $tanggalKurs->lessThan($batasUsia)) {
                throw new RuntimeException('Data emas atau kurs lebih tua dari tujuh hari; acuan terakhir dipertahankan.');
            }

            $hargaPerGram = bcadd(bcdiv(bcmul($hargaUsd, $kurs, 16), self::GRAMS_PER_TROY_OUNCE, 8), '0.005', 2);
            if (bccomp($hargaPerGram, '0', 2) <= 0 || bccomp($hargaPerGram, '9999999999999.99', 2) > 0) {
                throw new RuntimeException('Hasil konversi harga emas di luar rentang database.');
            }

            DB::table('harga_emas')->insert([
                'harga_per_gram' => $hargaPerGram,
                'sumber' => 'Harga emas dunia: Gold API; kurs USD/IDR: Frankfurter',
                'jenis_sumber' => 'api',
                'harga_usd_per_troy_ounce' => $hargaUsd,
                'kurs_usd_idr' => $kurs,
                'sumber_kurs' => 'Frankfurter (kurs referensi gabungan)',
                'waktu_harga_emas' => $waktuEmas,
                'tanggal_kurs' => $tanggalKurs->toDateString(),
                'diambil_at' => $diambilAt,
                'tanggal_sinkronisasi' => $tanggalSinkronisasi,
                'tanggal_sumber' => $waktuEmas->min($tanggalKurs),
                'dicatat_oleh' => null,
                'created_at' => $diambilAt,
                'updated_at' => $diambilAt,
            ]);

            Log::info('Acuan emas dunia disinkronkan.', [
                'harga_per_gram' => $hargaPerGram,
                'waktu_harga_emas' => $waktuEmas->toIso8601String(),
                'tanggal_kurs' => $tanggalKurs->toDateString(),
            ]);
            $this->info('Acuan emas dunia tersimpan: Rp'.number_format((float) $hargaPerGram, 2, ',', '.').'/gram. Pesanan lama tetap memakai snapshot.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::warning('Sinkronisasi emas gagal; harga terakhir tidak diubah.', ['error' => $exception->getMessage()]);
            $this->error('Sinkronisasi gagal; harga terakhir tidak diubah. '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }

    private function httpClient(): PendingRequest
    {
        return Http::acceptJson()
            ->withUserAgent('PortalZakat/1.0')
            ->connectTimeout(5)
            ->timeout(15)
            ->retry(2, 1000, fn (Throwable $exception): bool => $exception instanceof ConnectionException
                || ($exception instanceof RequestException && ($exception->response->serverError() || $exception->response->status() === 429)));
    }

    private function positiveDecimal(mixed $value, string $label): string
    {
        if (is_float($value) && is_finite($value)) {
            $value = number_format($value, 8, '.', '');
        }

        if ((! is_string($value) && ! is_int($value)) || ! preg_match('/\A\d{1,12}(?:\.\d{1,8})?\z/', (string) $value)) {
            throw new RuntimeException($label.' bukan angka desimal yang valid.');
        }

        $decimal = bcadd((string) $value, '0', 8);
        if (bccomp($decimal, '0', 8) <= 0) {
            throw new RuntimeException($label.' harus lebih dari nol.');
        }

        return $decimal;
    }

    private function goldTimestamp(mixed $value): CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|\+00:00)\z/', $value)) {
            throw new RuntimeException('Timestamp UTC harga emas tidak valid.');
        }

        $timestamp = CarbonImmutable::parse($value)->utc();
        if ($timestamp->format('Y-m-d\TH:i:s') !== substr($value, 0, 19)) {
            throw new RuntimeException('Tanggal atau jam sumber harga emas tidak valid.');
        }

        if ($timestamp->greaterThan(CarbonImmutable::now('UTC')->addMinutes(5))) {
            throw new RuntimeException('Timestamp harga emas berada di masa depan.');
        }

        return $timestamp;
    }

    private function exchangeDate(mixed $value): CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $value)) {
            throw new RuntimeException('Tanggal sumber kurs tidak valid.');
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, 'UTC');
        if (! $date || $date->toDateString() !== $value || $date->isFuture()) {
            throw new RuntimeException('Tanggal sumber kurs tidak valid atau berada di masa depan.');
        }

        return $date;
    }
}
