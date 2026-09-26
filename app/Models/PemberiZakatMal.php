<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemberiZakatMal extends Model
{
    use HasFactory;

    protected $table = 'pemberi_zakat_mal';

    protected $fillable = [
        'user_id',
        'kepemilikan',
        'atas_nama',
        'nominal_tabungan',
        'emas_gram',
        'nilai_invest',
        'nilai_aset_dagang',
        'piutang',
        'utang',
        'total_harta_bersih',
        'apakah_wajib_zakat',
    ];

    protected function casts(): array
    {
        return [
            'nominal_tabungan' => 'decimal:2',
            'emas_gram' => 'decimal:2',
            'nilai_invest' => 'decimal:2',
            'nilai_aset_dagang' => 'decimal:2',
            'piutang' => 'decimal:2',
            'utang' => 'decimal:2',
            'total_harta_bersih' => 'decimal:2',
            'apakah_wajib_zakat' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
