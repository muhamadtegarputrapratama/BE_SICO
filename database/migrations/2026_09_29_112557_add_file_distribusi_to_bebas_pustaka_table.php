<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bebas_pustaka', 'file_distribusi')) {
            Schema::table('bebas_pustaka', function (Blueprint $table) {
                $table->string('file_distribusi')
                    ->nullable()
                    ->after('file_skripsi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bebas_pustaka', 'file_distribusi')) {
            Schema::table('bebas_pustaka', function (Blueprint $table) {
                $table->dropColumn('file_distribusi');
            });
        }
    }
};
