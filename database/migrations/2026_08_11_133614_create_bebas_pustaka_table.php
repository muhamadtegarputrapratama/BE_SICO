<?php
// database/migrations/2026_08_11_100000_create_bebas_pustaka_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bebas_pustaka', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('menunggu');
            $table->text('catatan_revisi')->nullable();
            $table->foreignId('direview_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('direview_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bebas_pustaka');
    }
};