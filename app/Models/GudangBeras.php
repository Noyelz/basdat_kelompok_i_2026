<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GudangBeras extends Model
{
    use HasFactory;

    protected $table = 'gudang_beras';
    protected $fillable = ['total_uang_dikonversi', 'stok_beras_kg'];
}