<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('telp', 20)->nullable();
            $table->string('email', 191)->nullable();
            $table->text('alamat')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('katalog_beras', function (Blueprint $table) {
            $table->id();
            $table->string('nama_beras', 100);
            $table->decimal('harga_per_kg', 15, 2)->default(0);
            $table->decimal('stock', 12, 2)->default(0);
            $table->string('pict_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pembelian_beras', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pembelian', 50)->unique();
            $table->foreignId('vendor_id')->constrained('vendor')->restrictOnDelete();
            $table->foreignId('gudang_id')->constrained('gudang')->restrictOnDelete();
            $table->foreignId('dicatat_oleh')->constrained('user')->restrictOnDelete();
            $table->date('tanggal_pembelian');
            $table->string('nomor_faktur', 100)->nullable();
            $table->string('bukti_path')->nullable();
            $table->string('status', 20)->default('dipesan');
            $table->timestamps();
        });

        Schema::create('lot_beras', function (Blueprint $table) {
            $table->id();
            $table->string('kode_lot', 50)->unique();
            $table->foreignId('pembelian_beras_id')->constrained('pembelian_beras')->restrictOnDelete();
            $table->foreignId('katalog_beras_id')->constrained('katalog_beras')->restrictOnDelete();
            $table->decimal('jumlah_masuk_kg', 12, 2);
            $table->decimal('sisa_kg', 12, 2);
            $table->decimal('harga_beli_per_kg', 15, 2);
            $table->date('tanggal_masuk');
            $table->date('tanggal_kedaluwarsa')->nullable();
            $table->boolean('is_simulation')->default(true);
            $table->timestamps();
            $table->index(['katalog_beras_id', 'tanggal_masuk']);
        });

        Schema::create('mutasi_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_beras_id')->constrained('lot_beras')->restrictOnDelete();
            $table->foreignId('dicatat_oleh')->constrained('user')->restrictOnDelete();
            $table->string('jenis', 30);
            $table->decimal('perubahan_kg', 12, 2);
            $table->decimal('saldo_setelah_kg', 12, 2);
            $table->text('alasan')->nullable();
            $table->string('referensi', 100)->nullable();
            $table->boolean('is_simulation')->default(true);
            $table->timestamps();
            $table->index(['lot_beras_id', 'created_at']);
        });

        Schema::create('riwayat_harga_katalog', function (Blueprint $table) {
            $table->id();
            $table->foreignId('katalog_beras_id')->constrained('katalog_beras')->restrictOnDelete();
            $table->decimal('harga_pokok_per_kg', 15, 2);
            $table->decimal('persen_keuntungan', 5, 2);
            $table->decimal('harga_penawaran_per_kg', 15, 2);
            $table->timestamp('berlaku_mulai');
            $table->foreignId('ditetapkan_oleh')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();
            $table->index(['katalog_beras_id', 'berlaku_mulai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_harga_katalog');
        Schema::dropIfExists('mutasi_stok');
        Schema::dropIfExists('lot_beras');
        Schema::dropIfExists('pembelian_beras');
        Schema::dropIfExists('katalog_beras');
        Schema::dropIfExists('vendor');
    }
};
