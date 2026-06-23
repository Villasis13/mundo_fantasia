<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_movimientos', function (Blueprint $table) {
            $table->dropForeign('caja_movimientos_id_sucursal_foreign');
        });
        Schema::table('caja_numero', function (Blueprint $table) {
            $table->dropForeign('caja_numero_id_sucursal_foreign');
        });
        Schema::table('cuentas_pagar', function (Blueprint $table) {
            $table->dropForeign('cuentas_pagar_id_sucursal_foreign');
        });
        Schema::table('guias_remision', function (Blueprint $table) {
            $table->dropForeign('guias_remision_id_sucursal_foreign');
        });
        Schema::table('producto_sucursal', function (Blueprint $table) {
            $table->dropForeign('producto_sucursal_id_sucursal_foreign');
        });
        Schema::table('user_sucursal', function (Blueprint $table) {
            $table->dropForeign('user_sucursal_id_sucursal_foreign');
        });
    }

    public function down(): void
    {
        // No se recrean: sucursals está vacía y el sistema usa tiendas
    }
};
