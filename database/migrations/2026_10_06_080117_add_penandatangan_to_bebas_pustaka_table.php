<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bebas_pustaka', 'penandatangan')) {
            Schema::table('bebas_pustaka', function (Blueprint $table) {
                $table->string('penandatangan')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bebas_pustaka', 'penandatangan')) {
            Schema::table('bebas_pustaka', function (Blueprint $table) {
                $table->dropColumn('penandatangan');
            });
        }
    }
};