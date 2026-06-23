<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas_anulados', function (Blueprint $table) {
            $table->id('id_venta_anulado');
            $table->date('venta_anulado_fecha');
            $table->string('venta_anulado_serie');
            $table->integer('venta_anulado_correlativo');
            $table->string('venta_anulacion_ticket')->nullable();
            $table->string('venta_anulado_rutaXML')->nullable();
            $table->string('venta_anulado_rutaCDR')->nullable();
            $table->string('venta_anulado_estado_sunat')->nullable();
            $table->foreignId('id_venta')->constrained('ventas', 'id_venta')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('id_users')->constrained('users', 'id_users');
            $table->dateTime('venta_anulado_datetime')->useCurrent();
            $table->tinyInteger('venta_anulado_estado')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas_anulados');
    }
};
