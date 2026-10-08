<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('harga_emas', function (Blueprint $table) {
            $table->string('jenis_sumber', 20)->default('manual');
            $table->decimal('harga_usd_per_troy_ounce', 20, 8)->nullable();
            $table->decimal('kurs_usd_idr', 20, 8)->nullable();
            $table->string('sumber_kurs')->nullable();
            $table->timestamp('waktu_harga_emas')->nullable();
            $table->date('tanggal_kurs')->nullable();
            $table->timestamp('diambil_at')->nullable();
            $table->date('tanggal_sinkronisasi')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('harga_emas', function (Blueprint $table) {
            $table->dropUnique(['tanggal_sinkronisasi']);
            $table->dropColumn([
                'jenis_sumber', 'harga_usd_per_troy_ounce', 'kurs_usd_idr',
                'sumber_kurs', 'waktu_harga_emas', 'tanggal_kurs',
                'diambil_at', 'tanggal_sinkronisasi',
            ]);
        });
    }
};
