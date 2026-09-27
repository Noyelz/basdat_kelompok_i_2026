<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preferensi_notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('user')->cascadeOnDelete();
            $table->boolean('ingatkan_fitrah')->default(false);
            $table->boolean('ingatkan_haul_maal')->default(false);
            $table->boolean('email_bukti_penyaluran')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preferensi_notifikasi');
    }
};
