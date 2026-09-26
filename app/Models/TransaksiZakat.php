<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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