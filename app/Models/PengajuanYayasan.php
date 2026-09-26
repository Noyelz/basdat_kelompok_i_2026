<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanYayasan extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_yayasans';

    protected $fillable = [
        'user_id',
        'judul_program',
        'kebutuhan_dana',
        'kebutuhan_beras_kg',
        'deskripsi',
        'status',
        'bukti_penerimaan',
        'is_jumlah_sesuai',
        'catatan_penerimaan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}