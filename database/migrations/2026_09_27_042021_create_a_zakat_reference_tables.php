<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('gudang', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama');
            $table->string('provinsi', 100);
            $table->string('kota_kabupaten', 100);
            $table->text('alamat_lengkap')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wilayah_layanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudang')->restrictOnDelete();
            $table->string('nama', 100);
            $table->string('provinsi', 100);
            $table->unsignedTinyInteger('hari_batas_fitrah');
            $table->timestamp('batas_checkout_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->unique(['gudang_id', 'provinsi']);
        });

        Schema::create('tarif_pengiriman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wilayah_layanan_id')->constrained('wilayah_layanan')->restrictOnDelete();
            $table->decimal('biaya_tetap', 15, 2)->default(3000);
            $table->decimal('biaya_per_km_kg', 15, 2)->default(8);
            $table->timestamp('berlaku_mulai');
            $table->timestamp('berlaku_sampai')->nullable();
            $table->foreignId('ditetapkan_oleh')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();
            $table->index(['wilayah_layanan_id', 'berlaku_mulai']);
        });

        Schema::create('pengaturan_operasional', function (Blueprint $table) {
            $table->id();
            $table->string('kunci', 100)->unique();
            $table->text('nilai');
            $table->string('satuan', 30)->nullable();
            $table->foreignId('diubah_oleh')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('harga_emas', function (Blueprint $table) {
            $table->id();
            $table->decimal('harga_per_gram', 15, 2);
            $table->string('sumber');
            $table->timestamp('tanggal_sumber');
            $table->foreignId('dicatat_oleh')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();
            $table->index('tanggal_sumber');
        });

        Schema::create('yayasan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nomor_izin', 100)->unique();
            $table->string('telp', 20);
            $table->string('email', 191);
            $table->string('kota_kabupaten', 100);
            $table->string('provinsi', 100);
            $table->text('alamat_lengkap');
            $table->string('kode_pos', 10)->nullable();
            $table->foreignId('bank_id')->nullable()->constrained('bank')->restrictOnDelete();
            $table->string('nama_bank', 100)->nullable();
            $table->string('no_rek', 50)->nullable();
            $table->string('atas_nama_rek')->nullable();
            $table->string('status_verif', 20)->default('pending');
            $table->boolean('is_active')->default(false);
            $table->foreignId('pj_user_id')->unique()->constrained('user')->restrictOnDelete();
            $table->foreignId('wilayah_layanan_id')->nullable()->constrained('wilayah_layanan')->nullOnDelete();
            $table->decimal('jarak_jalan_km', 10, 2)->nullable();
            $table->unsignedInteger('jumlah_mustahik_layak')->default(0);
            $table->string('pict_id')->nullable();
            $table->timestamps();
            $table->index(['status_verif', 'is_active']);
        });

        Schema::create('foto_yayasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yayasan_id')->constrained('yayasan')->cascadeOnDelete();
            $table->foreignId('diunggah_oleh')->constrained('user')->restrictOnDelete();
            $table->string('path_private');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('verifikasi_panti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yayasan_id')->constrained('yayasan')->restrictOnDelete();
            $table->foreignId('panitia_id')->constrained('user')->restrictOnDelete();
            $table->string('hasil', 20);
            $table->unsignedInteger('jumlah_mustahik_layak')->default(0);
            $table->text('catatan')->nullable();
            $table->timestamp('diperiksa_at');
            $table->timestamps();
            $table->index(['yayasan_id', 'diperiksa_at']);
        });

        Schema::create('permohonan_perubahan_rekening', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yayasan_id')->constrained('yayasan')->restrictOnDelete();
            $table->foreignId('diajukan_oleh')->constrained('user')->restrictOnDelete();
            $table->foreignId('bank_id')->constrained('bank')->restrictOnDelete();
            $table->string('no_rek', 50);
            $table->string('atas_nama_rek');
            $table->string('status', 20)->default('pending');
            $table->foreignId('diperiksa_oleh')->nullable()->constrained('user')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamp('diperiksa_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mustahik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yayasan_id')->constrained('yayasan')->restrictOnDelete();
            $table->string('nama')->nullable();
            $table->string('kelamin', 1)->nullable();
            $table->date('tggl_lahir')->nullable();
            $table->string('kategori_asnaf', 30);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mustahik');
        Schema::dropIfExists('permohonan_perubahan_rekening');
        Schema::dropIfExists('verifikasi_panti');
        Schema::dropIfExists('foto_yayasan');
        Schema::dropIfExists('yayasan');
        Schema::dropIfExists('harga_emas');
        Schema::dropIfExists('pengaturan_operasional');
        Schema::dropIfExists('tarif_pengiriman');
        Schema::dropIfExists('wilayah_layanan');
        Schema::dropIfExists('gudang');
        Schema::dropIfExists('bank');
    }
};
