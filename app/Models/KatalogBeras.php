<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KatalogBeras extends Model
{
    use HasFactory;

    protected $table = 'katalog_beras';

    protected $fillable = [
        'nama_beras',
        'harga_per_kg',
        'stock',
        'pict_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'harga_per_kg' => 'decimal:2',
            'stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function detailFitrah()
    {
        return $this->hasMany(TransaksiFitrah::class, 'katalog_beras_id');
    }
}
