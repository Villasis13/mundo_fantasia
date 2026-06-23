<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas_cuotas', function (Blueprint $table) {
            $table->id('id_ventas_cuotas');
            $table->foreignId('id_venta')->constrained('ventas', 'id_venta')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('id_tipo_pago')->nullable();
            $table->unsignedBigInteger('id_formas_pago')->nullable();
            $table->string('venta_cuota_numero');
            $table->decimal('venta_cuota_importe', 10, 2);
            $table->date('venta_cuota_fecha');
            $table->tinyInteger('venta_cuota_estado');
            $table->tinyInteger('venta_cuota_pago')
                ->comment('1 pago 0 no pago');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas_cuotas');
    }
};
