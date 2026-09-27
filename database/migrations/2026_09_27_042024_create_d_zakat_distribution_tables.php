<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perjalanan_pengiriman', function (Blueprint $table) {
            $table->id();
            $table->string('kode_perjalanan', 50)->unique();
            $table->foreignId('gudang_id')->constrained('gudang')->restrictOnDelete();
            $table->foreignId('panitia_id')->constrained('user')->restrictOnDelete();
            $table->string('kendaraan', 100);
            $table->decimal('kapasitas_kg', 12, 2);
            $table->decimal('ongkir_aktual', 18, 2)->nullable();
            $table->timestamp('jadwal_berangkat');
            $table->timestamp('berangkat_at')->nullable();
            $table->string('status', 30)->default('direncanakan');
            $table->boolean('is_simulation')->default(true);
            $table->timestamps();
        });

        Schema::create('titik_pengiriman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perjalanan_pengiriman_id')->constrained('perjalanan_pengiriman')->restrictOnDelete();
            $table->foreignId('yayasan_id')->constrained('yayasan')->restrictOnDelete();
            $table->unsignedInteger('urutan');
            $table->decimal('jumlah_kg', 12, 2);
            $table->decimal('ongkir_aktual', 18, 2)->nullable();
            $table->string('status', 30)->default('dijadwalkan');
            $table->timestamp('tiba_at')->nullable();
            $table->timestamp('diterima_at')->nullable();
            $table->timestamps();
            $table->unique(['perjalanan_pengiriman_id', 'urutan']);
        });

        Schema::create('alokasi_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi_zakat')->restrictOnDelete();
            $table->foreignId('yayasan_id')->constrained('yayasan')->restrictOnDelete();
            $table->foreignId('titik_pengiriman_id')->nullable()->constrained('titik_pengiriman')->restrictOnDelete();
            $table->decimal('jumlah_kg', 12, 2)->nullable();
            $table->decimal('nominal', 18, 2)->nullable();
            $table->string('status', 30)->default('menunggu_alokasi');
            $table->boolean('is_simulation')->default(true);
            $table->timestamps();
            $table->index(['transaksi_id', 'status']);
        });

        Schema::create('bukti_serah_terima', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alokasi_transaksi_id')->constrained('alokasi_transaksi')->restrictOnDelete();
            $table->foreignId('diunggah_oleh')->constrained('user')->restrictOnDelete();
            $table->string('pihak', 20);
            $table->string('path_private');
            $table->decimal('jumlah_kg', 12, 2)->nullable();
            $table->decimal('nominal', 18, 2)->nullable();
            $table->boolean('jumlah_sesuai')->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('is_simulation')->default(true);
            $table->timestamps();
            $table->unique(['alokasi_transaksi_id', 'pihak']);
        });

        Schema::create('penyaluran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yayasan_id')->constrained('yayasan')->restrictOnDelete();
            $table->foreignId('mustahik_id')->nullable()->constrained('mustahik')->restrictOnDelete();
            $table->foreignId('alokasi_transaksi_id')->nullable()->constrained('alokasi_transaksi')->restrictOnDelete();
            $table->string('jenis_zakat', 10);
            $table->string('barang', 100);
            $table->decimal('jumlah', 18, 2);
            $table->string('satuan', 20);
            $table->string('status', 30)->default('pending');
            $table->string('bukti_pict_id')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('user')->nullOnDelete();
            $table->string('aksi', 100);
            $table->string('jenis_objek', 100);
            $table->unsignedBigInteger('objek_id')->nullable();
            $table->json('detail')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['jenis_objek', 'objek_id']);
        });

        Schema::create('notifikasi_aplikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user')->cascadeOnDelete();
            $table->string('jenis', 50);
            $table->json('payload');
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamp('dikirim_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'dibaca_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_aplikasi');
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('penyaluran');
        Schema::dropIfExists('bukti_serah_terima');
        Schema::dropIfExists('alokasi_transaksi');
        Schema::dropIfExists('titik_pengiriman');
        Schema::dropIfExists('perjalanan_pengiriman');
    }
};
