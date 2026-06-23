<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_compra_detalle', function (Blueprint $table) {
            $table->id('id_detalle_compra');
            $table->foreignId('id_orden_compra')->constrained('orden_compra', 'id_orden_compra');
            $table->foreignId('id_pro')->constrained('productos', 'id_pro');
            $table->string('detalle_orden_nombre_producto', 250)->nullable();
            $table->double('detalle_compra_cantidad', 8, 2);
            $table->double('detalle_compra_cantidad_recibida', 8, 2)->nullable();
            $table->decimal('detalle_compra_precio_compra', 10, 2);
            $table->decimal('detalle_compra_total_pedido', 10, 2);
            $table->string('detalle_compra_tipo_moneda')->nullable();
            $table->decimal('detalle_compra_tipo_cambio', 10, 5)->nullable();
            $table->decimal('detalle_compra_total_dolares', 10, 2)->nullable();
            $table->decimal('detalle_compra_total_pagado', 10, 2)->nullable();
            $table->tinyInteger('detalle_compra_estado');
            $table->decimal('peso', 10, 2)->nullable();
            $table->decimal('flete', 10, 2)->nullable();
            $table->decimal('gasto', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra_detalle');
    }
};
