<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id('id_venta');
            $table->foreignId('id_caja')->nullable()
                ->constrained('caja', 'id_caja');
            $table->foreignId('id_caja_numero')->constrained('caja_numero', 'id_caja_numero')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->bigInteger('id_empresa')->default(1);
            $table->foreignId('id_sucursal')->nullable()
                ->constrained('sucursals', 'id_sucursal');
            $table->bigInteger('id_users')->nullable();
            $table->bigInteger('id_clientes');
            $table->foreignId('id_moneda')->default(1)->constrained('monedas', 'id_moneda')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('venta_tipo_campo', 10, 2)->nullable()
                ->comment('aca se guardara el tipo de cambio');
            $table->tinyInteger('venta_condicion_resumen')->default(1)
                ->comment('1-Registro, 2-Actualizar, 3-baja');
            $table->tinyInteger('venta_tipo_envio')->default(0)
                ->comment('1-directo, 2-resumen diario');
            $table->string('venta_direccion', 200)->nullable();
            $table->string('venta_tipo', 10);
            $table->string('venta_serie', 10)->nullable();
            $table->string('venta_correlativo', 60);
            $table->decimal('venta_descuento_global', 10, 2)->default(0.00);
            $table->decimal('venta_totalgratuita', 10, 2)->default(0.00);
            $table->decimal('venta_totalexonerada', 10, 2)->default(0.00);
            $table->decimal('venta_totalinafecta', 10, 2)->default(0.00);
            $table->decimal('venta_totalgravada', 10, 2)->default(0.00);
            $table->decimal('venta_totaligv', 10, 2)->default(0.00);
            $table->tinyInteger('venta_incluye_igv')->default(1);
            $table->string('venta_porcentaje_igv', 20)->nullable();
            $table->decimal('venta_totaldescuento', 10, 2)->default(0.00);
            $table->decimal('venta_icbper', 10, 2)->default(0.00);
            $table->decimal('venta_total', 10, 2)->default(0.00);
            $table->decimal('venta_pago_cliente', 10, 2)->nullable();
            $table->decimal('venta_vuelto', 10, 2)->nullable();
            $table->dateTime('venta_fecha');
            $table->string('venta_observacion', 500)->nullable();
            $table->string('tipo_documento_modificar', 10)->nullable();
            $table->string('serie_modificar', 20)->nullable();
            $table->string('correlativo_modificar', 50)->nullable();
            $table->string('venta_codigo_motivo_nota', 10)->nullable();
            $table->tinyInteger('venta_estado_sunat')->default(0);
            $table->dateTime('venta_fecha_envio')->nullable();
            $table->string('venta_rutaXML', 200)->nullable();
            $table->string('venta_rutaCDR', 200)->nullable();
            $table->string('venta_respuesta_sunat', 2000)->nullable();
            $table->date('venta_fecha_de_baja')->nullable();
            $table->tinyInteger('anulado_sunat')->default(0);
            $table->tinyInteger('venta_cancelar')->default(1);
            $table->string('venta_seriecorrelativo_notaventa', 100)->nullable()
                ->comment('Aqui se llena cuando se edita una nota de venta');
            $table->string('venta_codigo', 100);
            $table->tinyInteger('cambiar_concepto')->default(1)->comment('1 es NO, 2 es SI');
            $table->string('concepto_nuevo', 300)->nullable();
            $table->tinyInteger('tipo_venta')->nullable()->comment('1 si la venta es en tienda , 2 en web');
            $table->tinyInteger('venta_estado_venta')->default(0);
            $table->bigInteger('id_formas_pago');
            $table->foreignId('id_profo')->nullable()
                ->constrained('proformas', 'id_profo');
            $table->tinyInteger('venta_estado_pago')->nullable();
            $table->string('venta_codigo_hash', 1000)->nullable();
            $table->timestamps();

            $table->index('id_users', 'id_usuario');
            $table->index('id_clientes', 'id_cliente');
            $table->index('id_empresa', 'id_empresa');
            $table->index('id_formas_pago', 'id_formas_pago');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
