<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_pagar', function (Blueprint $table) {
            $table->id('id_cuenta_pagar');
            $table->unsignedBigInteger('id_orden_compra')->nullable()
                ->comment('Orden de compra que origina la obligación');
            $table->foreignId('id_proveedores')->constrained('proveedores', 'id_proveedores')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->bigInteger('id_empresa');
            $table->foreignId('id_sucursal')->nullable()
                ->constrained('sucursals', 'id_sucursal')->nullOnDelete();
            $table->foreignId('id_users_registro')->constrained('users', 'id_users')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->string('cp_numero_doc', 100)->comment('N° de comprobante del proveedor');
            $table->string('cp_tipo_doc', 20)->comment('Factura, Boleta, Liquidación, etc.');
            $table->date('cp_fecha_emision');
            $table->date('cp_fecha_vencimiento');
            $table->decimal('cp_monto_total', 10, 2);
            $table->decimal('cp_monto_pagado', 10, 2)->default(0.00);
            $table->decimal('cp_saldo', 10, 2);
            $table->tinyInteger('cp_estado')->default(1)
                ->comment('0=Anulada, 1=Pendiente, 2=Parcial, 3=Pagada');
            $table->string('cp_observacion', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['id_empresa', 'cp_estado']);
            $table->index(['id_proveedores', 'cp_estado']);
            $table->index('id_orden_compra');

            $table->foreign('id_orden_compra', 'fk_cp_orden_compra')
                ->references('id_orden_compra')->on('orden_compra')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_pagar');
    }
};
