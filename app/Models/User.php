<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'user';

    protected $fillable = [
        'nik',
        'nama',
        'telp',
        'email',
        'pass_hash',
        'tggl_lahir',
        'gender',
        'kota_kabupaten',
        'provinsi',
        'alamat_lengkap',
        'npwp',
        'role',
        'is_active',
        'must_change_password',
        'created_by_admin_id',
    ];

    protected $hidden = [
        'pass_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'tggl_lahir' => 'date',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Override autentikasi password ke kolom pass_hash
     */
    public function getAuthPasswordName(): string
    {
        return 'pass_hash';
    }

    public function getAuthPassword(): string
    {
        return (string) $this->pass_hash;
    }

    /**
     * Accessor & Mutator agar kompatibel dengan kode yang memanggil 'name' dan 'password'
     */
    public function getNameAttribute()
    {
        return $this->nama;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['nama'] = $value;
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['pass_hash'] = Hash::needsRehash($value)
            ? Hash::make($value)
            : $value;
    }

    public function getKotaNamaAttribute()
    {
        return $this->kota_kabupaten;
    }

    public function getProvinsiNamaAttribute()
    {
        return $this->provinsi;
    }

    public function getAlamatAttribute()
    {
        return $this->alamat_lengkap;
    }

    /**
     * Relasi Antar Tabel
     */
    public function yayasan()
    {
        return $this->hasOne(Yayasan::class, 'pj_user_id');
    }

    public function transaksiZakat()
    {
        return $this->hasMany(TransaksiZakat::class, 'user_id');
    }
}
