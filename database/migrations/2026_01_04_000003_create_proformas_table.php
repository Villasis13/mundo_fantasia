<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proformas', function (Blueprint $table) {
            $table->id('id_profo');
            $table->foreignId('id_clientes')->constrained('clientes', 'id_clientes');
            $table->foreignId('id_users')->constrained('users', 'id_users');
            $table->foreignId('id_sucursal')->nullable()
                ->constrained('sucursals', 'id_sucursal')->nullOnDelete();
            $table->tinyInteger('profo_forma_pago');
            $table->string('profo_lugar_entrega', 500)->nullable();
            $table->string('profo_observacion', 1000)->nullable();
            $table->string('profo_serie');
            $table->integer('profo_correlativo');
            $table->date('profo_fecha_emision');
            $table->tinyInteger('profo_estado')->comment('1 activo , 0 inactivo');
            $table->tinyInteger('profo_acti_estado')->comment('0 creado , 1 aprobado para la venta , 2 entregado/vendido 3 rechazado');
            $table->string('profo_microtime')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proformas');
    }
};
