<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_sucursal', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tienda')->nullable()->after('id_sucursal');
            $table->foreign('id_tienda')->references('id_tienda')->on('tiendas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('producto_sucursal', function (Blueprint $table) {
            $table->dropForeign(['id_tienda']);
            $table->dropColumn('id_tienda');
        });
    }
};
