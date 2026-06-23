<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_productos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_almacen')->nullable()->after('id_sucursal');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_productos', function (Blueprint $table) {
            $table->dropColumn('id_almacen');
        });
    }
};
