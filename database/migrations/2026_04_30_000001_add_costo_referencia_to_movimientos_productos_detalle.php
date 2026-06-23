<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_productos_detalle', function (Blueprint $table) {
            $table->decimal('costo_unitario', 10, 4)->default(0)->after('movimientos_productos_detalle_cantidad');
            $table->unsignedBigInteger('id_referencia')->nullable()->after('costo_unitario')
                ->comment('id_orden_compra, id_transferencia, etc.');
            $table->string('tipo_referencia', 50)->nullable()->after('id_referencia')
                ->comment('compra, transferencia, ajuste, venta');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_productos_detalle', function (Blueprint $table) {
            $table->dropColumn(['costo_unitario', 'id_referencia', 'tipo_referencia']);
        });
    }
};
