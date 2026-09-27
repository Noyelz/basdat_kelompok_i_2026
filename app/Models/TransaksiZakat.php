<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TransaksiZakat extends Model
{
    use HasFactory;

    protected $table = 'transaksi_zakat';

    protected $fillable = [
        'kode_transaksi',
        'user_id',
        'yayasan_id',
        'jenis_zakat',
        'total_nominal',
        'metode_pembayaran',
        'status_pembayaran',
        'payment_gateway_ref',
        'qr_payload',
        'catatan_doa',
        'expired_at',
        'paid_at',
    ];

    public static function expirePending(): void
    {
        $ids = DB::table('transaksi_zakat')
            ->where('status_pembayaran', 'pending')
            ->where('expired_at', '<=', now())
            ->orderBy('id')
            ->limit(100)
            ->pluck('id');

        foreach ($ids as $id) {
            DB::transaction(function () use ($id): void {
                $pesanan = DB::table('transaksi_zakat')->where('id', $id)->lockForUpdate()->first();
                if (! $pesanan || $pesanan->status_pembayaran !== 'pending' || CarbonImmutable::parse($pesanan->expired_at)->isFuture()) {
                    return;
                }

                DB::table('transaksi_zakat')->where('id', $id)->update(['status_pembayaran' => 'expired', 'updated_at' => now()]);
                DB::table('percobaan_pembayaran')->where('transaksi_id', $id)->where('status', 'pending')->update(['status' => 'expired', 'updated_at' => now()]);
                DB::table('reservasi_stok')->where('transaksi_id', $id)->where('status', 'active')->update(['status' => 'expired', 'released_at' => now(), 'updated_at' => now()]);
            });
        }
    }

    protected function casts(): array
    {
        return [
            'total_nominal' => 'decimal:2',
            'expired_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function getNamaPembayarAttribute()
    {
        return $this->user->nama ?? 'Muzakki';
    }

    public function getTotalBayarAttribute()
    {
        return $this->total_nominal;
    }

    public function getStatusAttribute()
    {
        if ($this->status_pembayaran === 'paid') {
            return 'success';
        }

        return $this->status_pembayaran;
    }

    public function getBuktiPembayaranAttribute()
    {
        return $this->qr_payload ?? 'uploads/default.jpg';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function yayasan()
    {
        return $this->belongsTo(Yayasan::class, 'yayasan_id');
    }

    public function detailFitrah()
    {
        return $this->hasOne(TransaksiFitrah::class, 'transaksi_id');
    }

    public function detailMal()
    {
        return $this->hasOne(TransaksiMal::class, 'transaksi_id');
    }
}
