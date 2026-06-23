<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_compra', function (Blueprint $table) {
            $table->date('fecha_almacenamiento')->nullable()->after('orden_compra_fecha');
            $table->string('moneda', 3)->default('PEN')->after('fecha_almacenamiento');
            $table->text('orden_compra_transportistas')->nullable()->after('orden_compra_guia_transportista');
            $table->decimal('orden_compra_descuento_porcentaje', 5, 2)->default(0)->after('orden_compra_total');
            $table->decimal('orden_compra_descuento_monto', 12, 2)->default(0)->after('orden_compra_descuento_porcentaje');
            $table->decimal('orden_compra_igv_porcentaje', 5, 2)->default(0)->after('orden_compra_descuento_monto');
            $table->decimal('orden_compra_igv_monto', 12, 2)->default(0)->after('orden_compra_igv_porcentaje');
            $table->decimal('orden_compra_percepcion_porcentaje', 5, 2)->default(0)->after('orden_compra_igv_monto');
            $table->decimal('orden_compra_percepcion_monto', 12, 2)->default(0)->after('orden_compra_percepcion_porcentaje');
        });

        Schema::table('orden_compra_detalle', function (Blueprint $table) {
            $table->string('presentacion', 100)->nullable()->after('detalle_orden_nombre_producto');
            $table->decimal('cantidad_x_unidad', 10, 4)->nullable()->after('detalle_compra_cantidad');
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra', function (Blueprint $table) {
            $table->dropColumn([
                'fecha_almacenamiento', 'moneda', 'orden_compra_transportistas',
                'orden_compra_descuento_porcentaje', 'orden_compra_descuento_monto',
                'orden_compra_igv_porcentaje', 'orden_compra_igv_monto',
                'orden_compra_percepcion_porcentaje', 'orden_compra_percepcion_monto',
            ]);
        });

        Schema::table('orden_compra_detalle', function (Blueprint $table) {
            $table->dropColumn(['presentacion', 'cantidad_x_unidad']);
        });
    }
};
