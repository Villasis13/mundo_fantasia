<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_compra', function (Blueprint $table) {
            $table->id('id_orden_compra');
            $table->foreignId('id_solicitante')->constrained('users', 'id_users');
            $table->foreignId('id_aprobacion')->nullable()
                ->constrained('users', 'id_users');
            $table->foreignId('id_proveedores')->constrained('proveedores', 'id_proveedores');
            $table->bigInteger('id_sede')->nullable();
            $table->foreignId('id_sucursal')->nullable()
                ->constrained('sucursals', 'id_sucursal')->nullOnDelete();
            $table->foreignId('id_tipo_pago')->nullable()
                ->constrained('tipo_pago', 'id_tipo_pago');
            $table->bigInteger('id_almacen')->nullable();
            $table->string('orden_compra_observacion')->nullable();
            $table->string('orden_compra_fecha_aprob')->nullable();
            $table->string('orden_compra_titulo');
            $table->integer('orden_compra_activo')->nullable();
            $table->string('orden_compra_numero');
            $table->string('orden_compra_estado');
            $table->dateTime('orden_compra_fecha');
            $table->string('orden_compra_tipo_doc')->nullable();
            $table->string('orden_compra_numero_doc')->nullable();
            $table->string('orden_compra_doc_adjuntado')->nullable();
            $table->date('orden_compra_fecha_emision_doc')->nullable();
            $table->string('orden_compra_doc_cuotas')->nullable();
            $table->dateTime('orden_compra_fecha_recibida')->nullable();
            $table->string('orden_compra_usuario_recibido')->nullable();
            $table->string('orden_compra_codigo')->nullable();
            $table->decimal('orden_compra_total', 10, 2)->nullable();
            $table->string('orden_compra_num_document', 250)->nullable();
            $table->string('orden_compra_nom_prove', 250)->nullable();
            $table->decimal('orden_compra_flete', 10, 2)->default(0.00)
                ->comment('se agrego eso para saber cuanto es el total del flete gasto');
            $table->decimal('orden_compra_gastos_operativos', 10, 2)->default(0.00)
                ->comment('se agrego para sacer el total del gasto operativo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra');
    }
};
