<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yayasan', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_mustahik_diajukan')->default(0);
            $table->text('kebutuhan_saat_ini')->nullable();
            $table->decimal('kapasitas_penerimaan_kg', 12, 2)->nullable();
        });

        Schema::table('verifikasi_panti', function (Blueprint $table) {
            $table->decimal('kapasitas_penerimaan_kg', 12, 2)->nullable();
            $table->text('kebutuhan_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('verifikasi_panti', function (Blueprint $table) {
            $table->dropColumn(['kapasitas_penerimaan_kg', 'kebutuhan_snapshot']);
        });

        Schema::table('yayasan', function (Blueprint $table) {
            $table->dropColumn(['jumlah_mustahik_diajukan', 'kebutuhan_saat_ini', 'kapasitas_penerimaan_kg']);
        });
    }
};
