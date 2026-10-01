<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'bebas_pustaka';

    public function up(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            if (! Schema::hasColumn($this->table, 'nomor_surat')) {
                $table->string('nomor_surat')->nullable();
            }

            if (! Schema::hasColumn($this->table, 'qr_token')) {
                $table->string('qr_token', 64)->nullable()->unique();
            }

            if (! Schema::hasColumn($this->table, 'file_surat')) {
                $table->string('file_surat')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            // unique index harus dibuang dulu sebelum kolomnya
            if (Schema::hasColumn($this->table, 'qr_token')) {
                $table->dropUnique(['qr_token']);
            }

            $table->dropColumn(
                array_filter(
                    ['nomor_surat', 'qr_token', 'file_surat'],
                    fn ($col) => Schema::hasColumn($this->table, $col)
                )
            );
        });
    }
};