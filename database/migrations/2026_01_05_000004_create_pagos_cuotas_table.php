<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_cuotas', function (Blueprint $table) {
            $table->id('id_pagos_cuota');
            $table->foreignId('id_users')->constrained('users', 'id_users');
            $table->foreignId('id_ventas_cuotas')->constrained('ventas_cuotas', 'id_ventas_cuotas');
            $table->foreignId('id_tipo_pago')->constrained('tipo_pago', 'id_tipo_pago');
            $table->decimal('pagos_cuota_monto', 15, 2);
            $table->date('pagos_cuota_fecha');
            $table->string('pagos_cuota_voucher')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_cuotas');
    }
};
