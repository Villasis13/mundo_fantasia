<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas_detalle_pagos', function (Blueprint $table) {
            $table->id('id_venta_detalle_pago');
            $table->foreignId('id_venta')->constrained('ventas', 'id_venta')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('id_tipo_pago');
            $table->decimal('venta_detalle_pago_monto', 10, 2)->default(0.00);
            $table->tinyInteger('venta_detalle_pago_estado')->default(1);
            $table->timestamps();

            $table->index('id_tipo_pago');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas_detalle_pagos');
    }
};
