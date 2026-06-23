<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas_detalle', function (Blueprint $table) {
            $table->id('id_venta_detalle');
            $table->foreignId('id_venta')->constrained('ventas', 'id_venta')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('id_pro');
            $table->decimal('venta_detalle_precio_ref', 10, 2)->nullable()
                ->comment('Precio de referencia seleccionado');
            $table->decimal('venta_detalle_valor_unitario', 10, 2)->default(0.00);
            $table->decimal('venta_detalle_precio_unitario', 10, 2);
            $table->string('venta_detalle_nombre_producto', 200);
            $table->double('venta_detalle_cantidad');
            $table->decimal('venta_detalle_total_igv', 10, 2);
            $table->integer('venta_detalle_porcentaje_igv')->default(0);
            $table->decimal('venta_detalle_total_icbper', 10, 2)->default(0.00);
            $table->decimal('venta_detalle_valor_total', 10, 2)->default(0.00);
            $table->decimal('venta_detalle_importe_total', 10, 2)->default(0.00);
            $table->timestamps();

            $table->index('id_pro', 'id_comanda_detalle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas_detalle');
    }
};
