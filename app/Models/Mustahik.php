<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mustahik extends Model
{
    use HasFactory;

    protected $table = 'mustahik';

    protected $fillable = [
        'yayasan_id',
        'nama',
        'kelamin',
        'tggl_lahir',
        'kategori_asnaf',
    ];

    protected function casts(): array
    {
        return [
            'tggl_lahir' => 'date',
        ];
    }

    public function yayasan()
    {
        return $this->belongsTo(Yayasan::class, 'yayasan_id');
    }

    public function penyaluran()
    {
        return $this->hasMany(Penyaluran::class, 'mustahik_id');
    }
}
