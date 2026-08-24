<?php
// database/migrations/2026_08_11_100100_create_pengajuan_clearing_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_clearing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('bebas_pustaka_id')->constrained('bebas_pustaka')->cascadeOnDelete();
            $table->string('departemen');
            $table->string('program_studi');
            $table->string('file_ktm');
            $table->string('file_bukti_spp');
            $table->string('file_distribusi');
            $table->string('status')->default('menunggu');
            $table->text('catatan_revisi')->nullable();
            $table->foreignId('direview_admin_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('direview_admin_at')->nullable();
            $table->foreignId('disetujui_atasan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disetujui_atasan_at')->nullable();
            $table->string('nomor_surat')->nullable()->unique();
            $table->string('qr_token')->nullable()->unique();
            $table->string('file_surat')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_clearing');
    }
};
