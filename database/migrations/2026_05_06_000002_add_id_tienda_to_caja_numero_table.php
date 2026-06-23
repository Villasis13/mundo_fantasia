<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_numero', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tienda')->nullable()->after('id_sucursal');
        });
    }

    public function down(): void
    {
        Schema::table('caja_numero', function (Blueprint $table) {
            $table->dropColumn('id_tienda');
        });
    }
};
