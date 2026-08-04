<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('autoconsumo', function (Blueprint $table) {
            $table->string('autoconsumo_cod_sunat', 10)->nullable()->after('autoconsumo_motivo');
        });
    }

    public function down(): void
    {
        Schema::table('autoconsumo', function (Blueprint $table) {
            $table->dropColumn('autoconsumo_cod_sunat');
        });
    }
};
