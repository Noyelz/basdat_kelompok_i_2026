<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penyaluran extends Model
{
    use HasFactory;

    protected $table = 'penyaluran';

    protected $fillable = [
        'yayasan_id',
        'mustahik_id',
        'jenis_zakat',
        'barang',
        'jumlah',
        'satuan',
        'bukti_pict_id',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
        ];
    }

    public function yayasan()
    {
        return $this->belongsTo(Yayasan::class, 'yayasan_id');
    }

    public function mustahik()
    {
        return $this->belongsTo(Mustahik::class, 'mustahik_id');
    }

    public function getJumlahUangDimintaAttribute()
    {
        return ($this->jenis_zakat === 'mal' || $this->satuan === 'Rupiah') ? $this->jumlah : 0;
    }

    public function getJumlahBerasDimintaKgAttribute()
    {
        return ($this->jenis_zakat === 'fitrah' || $this->satuan === 'Kg') ? $this->jumlah : 0;
    }

    public function getKeteranganAttribute()
    {
        return $this->barang;
    }

    public function getStatusAttribute()
    {
        return $this->bukti_pict_id ? 'disalurkan' : 'pending';
    }
}
