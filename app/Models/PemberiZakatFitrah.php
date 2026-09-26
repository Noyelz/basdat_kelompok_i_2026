<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemberiZakatFitrah extends Model
{
    use HasFactory;

    protected $table = 'pemberi_zakat_fitrah';

    protected $fillable = [
        'id_kepala',
        'jum_anggota',
    ];

    public function kepalaKeluarga()
    {
        return $this->belongsTo(User::class, 'id_kepala');
    }
}
