<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiFitrah extends Model
{
    use HasFactory;

    protected $table = 'transaksi_fitrah';

    protected $fillable = [
        'transaksi_id',
        'katalog_beras_id',
        'nama_beras_snapshot',
        'harga_per_kg_snapshot',
        'bobot_per_jiwa_kg',
        'jumlah_jiwa',
        'total_beras_kg',
        'total_biaya_fitrah',
        'bukti_pict_id',
    ];

    protected function casts(): array
    {
        return [
            'harga_per_kg_snapshot' => 'decimal:2',
            'bobot_per_jiwa_kg' => 'decimal:2',
            'total_beras_kg' => 'decimal:2',
            'total_biaya_fitrah' => 'decimal:2',
        ];
    }

    public function transaksi()
    {
        return $this->belongsTo(TransaksiZakat::class, 'transaksi_id');
    }

    public function katalogBeras()
    {
        return $this->belongsTo(KatalogBeras::class, 'katalog_beras_id');
    }
}
