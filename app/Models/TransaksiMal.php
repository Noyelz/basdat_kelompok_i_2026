<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiMal extends Model
{
    use HasFactory;

    protected $table = 'transaksi_mal';

    protected $fillable = [
        'transaksi_id',
        'nilai_tabungan',
        'emas_gram',
        'harga_emas_per_gram_snapshot',
        'nilai_investasi',
        'nilai_aset_dagang',
        'nilai_piutang',
        'nilai_utang',
        'total_harta_bersih',
        'nilai_nisab',
        'wajib_zakat',
        'persentase_zakat',
        'total_zakat_wajib',
        'bukti_pict_id',
    ];

    protected function casts(): array
    {
        return [
            'nilai_tabungan' => 'decimal:2',
            'emas_gram' => 'decimal:2',
            'harga_emas_per_gram_snapshot' => 'decimal:2',
            'nilai_investasi' => 'decimal:2',
            'nilai_aset_dagang' => 'decimal:2',
            'nilai_piutang' => 'decimal:2',
            'nilai_utang' => 'decimal:2',
            'total_harta_bersih' => 'decimal:2',
            'nilai_nisab' => 'decimal:2',
            'wajib_zakat' => 'boolean',
            'persentase_zakat' => 'decimal:2',
            'total_zakat_wajib' => 'decimal:2',
        ];
    }

    public function transaksi()
    {
        return $this->belongsTo(TransaksiZakat::class, 'transaksi_id');
    }
}
