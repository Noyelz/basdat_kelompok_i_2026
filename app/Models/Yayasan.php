<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Yayasan extends Model
{
    use HasFactory;

    protected $table = 'yayasan';

    protected $fillable = [
        'nama',
        'nomor_izin',
        'telp',
        'email',
        'kota_kabupaten',
        'provinsi',
        'alamat_lengkap',
        'kode_pos',
        'bank_id',
        'nama_bank',
        'no_rek',
        'atas_nama_rek',
        'status_verif',
        'is_active',
        'jumlah_mustahik_diajukan',
        'jumlah_mustahik_layak',
        'kebutuhan_saat_ini',
        'kapasitas_diajukan_kg',
        'kapasitas_penerimaan_kg',
        'wilayah_layanan_id',
        'jarak_jalan_km',
        'pj_user_id',
        'pict_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'kapasitas_diajukan_kg' => 'decimal:2',
        ];
    }

    public function getNameAttribute()
    {
        return $this->nama;
    }

    public function getKotaNamaAttribute()
    {
        return $this->kota_kabupaten;
    }

    public function getProvinsiNamaAttribute()
    {
        return $this->provinsi;
    }

    public function pjUser()
    {
        return $this->belongsTo(User::class, 'pj_user_id');
    }

    public function mustahik()
    {
        return $this->hasMany(Mustahik::class, 'yayasan_id');
    }

    public function transaksiZakat()
    {
        return $this->hasMany(TransaksiZakat::class, 'yayasan_id');
    }

    public function penyaluran()
    {
        return $this->hasMany(Penyaluran::class, 'yayasan_id');
    }
}
