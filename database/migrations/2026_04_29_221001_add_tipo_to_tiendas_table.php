<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $table) {
            // 1=Tienda, 2=Sucursal, 3=Almacén
            $table->tinyInteger('tienda_tipo')->default(1)->after('tienda_nombre');
        });
    }

    public function down(): void
    {
        Schema::table('tiendas', function (Blueprint $table) {
            $table->dropColumn('tienda_tipo');
        });
    }
};
