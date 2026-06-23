<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sin registros ni FKs: drop y re-add con nombres semánticos
        Schema::table('transferencias_stock', function (Blueprint $table) {
            $table->dropColumn(['id_sucursal_origen', 'id_sucursal_destino']);
        });
        Schema::table('transferencias_stock', function (Blueprint $table) {
            $table->unsignedBigInteger('id_almacen_origen')->after('transferencia_numero');
            $table->unsignedBigInteger('id_tienda_destino')->after('id_almacen_origen');
        });
    }

    public function down(): void
    {
        Schema::table('transferencias_stock', function (Blueprint $table) {
            $table->dropColumn(['id_almacen_origen', 'id_tienda_destino']);
        });
        Schema::table('transferencias_stock', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sucursal_origen')->after('transferencia_numero');
            $table->unsignedBigInteger('id_sucursal_destino')->after('id_sucursal_origen');
        });
    }
};
