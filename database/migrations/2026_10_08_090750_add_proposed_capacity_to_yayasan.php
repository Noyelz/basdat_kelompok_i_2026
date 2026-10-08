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
        Schema::table('yayasan', function (Blueprint $table) {
            $table->decimal('kapasitas_diajukan_kg', 12, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yayasan', function (Blueprint $table) {
            $table->dropColumn('kapasitas_diajukan_kg');
        });
    }
};
