<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_movimientos', function (Blueprint $table) {
            $table->id('id_caja_movimiento');
            $table->foreignId('id_caja')->constrained('caja', 'id_caja')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('id_users')->constrained('users', 'id_users')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->bigInteger('id_empresa')->nullable();
            $table->foreignId('id_sucursal')->nullable()
                ->constrained('sucursals', 'id_sucursal')->nullOnDelete();
            $table->tinyInteger('tipo')->comment('1=Ingreso, 2=Egreso');
            $table->string('concepto', 300);
            $table->decimal('monto', 10, 2);
            $table->foreignId('id_tipo_pago')->nullable()
                ->constrained('tipo_pago', 'id_tipo_pago')->nullOnDelete();
            $table->string('numero_operacion', 100)->nullable()
                ->comment('Número de operación para transferencia o QR');
            $table->string('observacion', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['id_caja', 'tipo']);
            $table->index('id_empresa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_movimientos');
    }
};
