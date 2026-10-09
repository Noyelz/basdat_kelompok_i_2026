<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['pemberi_zakat_fitrah', 'pemberi_zakat_mal'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("Tabel {$table} masih berisi data; tidak ada tabel yang dihapus.");
            }
        }

        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    /**
     * Restore empty legacy profiles; restoring their data requires a backup.
     */
    public function down(): void
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
    }
};
