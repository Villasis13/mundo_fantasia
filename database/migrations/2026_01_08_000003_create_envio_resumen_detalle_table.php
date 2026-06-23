<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('envio_resumen_detalle', function (Blueprint $table) {
            $table->id('id_envio_resumen_detalle');
            $table->foreignId('id_envio_resumen')
                ->constrained('envio_resumen', 'id_envio_resumen')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('id_venta')->constrained('ventas', 'id_venta')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->tinyInteger('envio_resumen_detalle_condicion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('envio_resumen_detalle');
    }
};
