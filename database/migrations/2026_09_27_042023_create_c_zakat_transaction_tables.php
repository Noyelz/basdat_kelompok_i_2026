<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemberi_zakat_fitrah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kepala')->unique()->constrained('user')->cascadeOnDelete();
            $table->unsignedInteger('jum_anggota')->default(1);
            $table->timestamps();
        });

        Schema::create('pemberi_zakat_mal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user')->cascadeOnDelete();
            $table->string('kepemilikan', 100)->nullable();
            $table->string('atas_nama')->nullable();
            $table->decimal('nominal_tabungan', 18, 2)->default(0);
            $table->decimal('emas_gram', 10, 2)->default(0);
            $table->decimal('nilai_invest', 18, 2)->default(0);
            $table->decimal('nilai_aset_dagang', 18, 2)->default(0);
            $table->decimal('piutang', 18, 2)->default(0);
            $table->decimal('utang', 18, 2)->default(0);
            $table->decimal('total_harta_bersih', 18, 2)->default(0);
            $table->boolean('apakah_wajib_zakat')->default(false);
            $table->date('tanggal_haul')->nullable();
            $table->timestamps();
        });

        Schema::create('transaksi_zakat', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi', 50)->unique();
            $table->foreignId('user_id')->constrained('user')->restrictOnDelete();
            $table->foreignId('yayasan_id')->constrained('yayasan')->restrictOnDelete();
            $table->string('jenis_zakat', 10);
            $table->decimal('harga_pokok_snapshot', 18, 2)->default(0);
            $table->decimal('keuntungan_snapshot', 18, 2)->default(0);
            $table->decimal('biaya_admin_snapshot', 18, 2)->default(0);
            $table->decimal('ongkir_snapshot', 18, 2)->default(0);
            $table->decimal('total_nominal', 18, 2);
            $table->string('mode_pembayaran', 20)->default('simulation');
            $table->string('metode_pembayaran', 50)->nullable();
            $table->string('status_pembayaran', 25)->default('pending');
            $table->string('status_penyaluran', 30)->default('menunggu_alokasi');
            $table->string('payment_gateway_ref', 100)->nullable();
            $table->text('qr_payload')->nullable();
            $table->text('catatan_doa')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['yayasan_id', 'jenis_zakat', 'status_pembayaran']);
        });

        Schema::create('transaksi_fitrah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->unique()->constrained('transaksi_zakat')->cascadeOnDelete();
            $table->foreignId('katalog_beras_id')->constrained('katalog_beras')->restrictOnDelete();
            $table->string('nama_beras_snapshot', 100);
            $table->decimal('harga_per_kg_snapshot', 15, 2);
            $table->decimal('bobot_per_jiwa_kg', 6, 2)->default(2.50);
            $table->unsignedInteger('jumlah_jiwa');
            $table->decimal('total_beras_kg', 12, 2);
            $table->decimal('total_biaya_fitrah', 18, 2);
            $table->decimal('jarak_jalan_km_snapshot', 10, 2)->nullable();
            $table->string('bukti_pict_id')->nullable();
            $table->timestamps();
        });

        Schema::create('transaksi_mal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->unique()->constrained('transaksi_zakat')->cascadeOnDelete();
            $table->foreignId('harga_emas_id')->nullable()->constrained('harga_emas')->restrictOnDelete();
            $table->decimal('nilai_tabungan', 18, 2)->default(0);
            $table->decimal('emas_gram', 10, 2)->default(0);
            $table->decimal('harga_emas_per_gram_snapshot', 15, 2);
            $table->string('sumber_harga_emas_snapshot')->nullable();
            $table->decimal('nilai_investasi', 18, 2)->default(0);
            $table->decimal('nilai_aset_dagang', 18, 2)->default(0);
            $table->decimal('nilai_piutang', 18, 2)->default(0);
            $table->decimal('nilai_utang', 18, 2)->default(0);
            $table->decimal('total_harta_bersih', 18, 2);
            $table->decimal('nilai_nisab', 18, 2);
            $table->boolean('wajib_zakat');
            $table->decimal('persentase_zakat', 5, 2)->default(2.50);
            $table->decimal('total_zakat_wajib', 18, 2);
            $table->string('bukti_pict_id')->nullable();
            $table->timestamps();
        });

        Schema::create('subjek_zakat_maal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user')->restrictOnDelete();
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksi_zakat')->cascadeOnDelete();
            $table->string('hubungan', 20);
            $table->string('nama_tampilan')->default('Hamba Allah');
            $table->decimal('tabungan', 18, 2)->default(0);
            $table->decimal('emas_gram', 10, 2)->default(0);
            $table->decimal('investasi', 18, 2)->default(0);
            $table->decimal('aset_dagang', 18, 2)->default(0);
            $table->decimal('piutang', 18, 2)->default(0);
            $table->decimal('utang', 18, 2)->default(0);
            $table->decimal('harga_emas_snapshot', 15, 2)->nullable();
            $table->decimal('nisab_snapshot', 18, 2)->nullable();
            $table->decimal('harta_bersih', 18, 2)->default(0);
            $table->decimal('zakat_wajib', 18, 2)->default(0);
            $table->date('tanggal_haul')->nullable();
            $table->boolean('sudah_haul')->default(false);
            $table->boolean('is_snapshot')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_snapshot']);
        });

        Schema::create('reservasi_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi_zakat')->restrictOnDelete();
            $table->foreignId('lot_beras_id')->constrained('lot_beras')->restrictOnDelete();
            $table->decimal('jumlah_kg', 12, 2);
            $table->decimal('harga_beli_per_kg_snapshot', 15, 2);
            $table->string('status', 20)->default('active');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->unique(['transaksi_id', 'lot_beras_id']);
            $table->index(['lot_beras_id', 'status']);
        });

        Schema::create('percobaan_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi_zakat')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('status', 25)->default('pending');
            $table->timestamp('expired_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('percobaan_pembayaran');
        Schema::dropIfExists('reservasi_stok');
        Schema::dropIfExists('subjek_zakat_maal');
        Schema::dropIfExists('transaksi_mal');
        Schema::dropIfExists('transaksi_fitrah');
        Schema::dropIfExists('transaksi_zakat');
        Schema::dropIfExists('pemberi_zakat_mal');
        Schema::dropIfExists('pemberi_zakat_fitrah');
    }
};
