<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guias_remision', function (Blueprint $table) {
            $table->id('id_guia');
            $table->foreignId('id_empresa')->constrained('empresa', 'id_empresa');
            $table->foreignId('id_sucursal')->nullable()->constrained('sucursals', 'id_sucursal')->nullOnDelete();
            $table->foreignId('id_users')->constrained('users', 'id_users');

            // Referencia opcional al documento origen
            $table->unsignedBigInteger('id_venta')->nullable()
                ->comment('Venta que origina el traslado (motivo 01)');
            $table->unsignedBigInteger('id_orden_compra')->nullable()
                ->comment('OC que origina el traslado (motivo 02)');
            $table->unsignedBigInteger('id_transferencia')->nullable()
                ->comment('Transferencia de stock (motivo 04)');

            // Numeración
            $table->string('guia_serie', 10);               // T001
            $table->string('guia_correlativo', 10);          // 00000001
            $table->string('guia_numero', 20)->virtualAs("CONCAT(guia_serie, '-', guia_correlativo)");

            // Fechas
            $table->date('guia_fecha_emision');
            $table->date('guia_fecha_traslado');

            // Motivo y modalidad (catálogos SUNAT)
            $table->string('guia_motivo_traslado', 2);       // 01=Venta,02=Compra,04=Traslado,99=Otros
            $table->string('guia_modalidad_traslado', 2);    // 01=Público,02=Privado
            $table->string('guia_observaciones', 500)->nullable();

            // Carga
            $table->decimal('guia_peso_bruto', 10, 3)->default(0); // KGM

            // Destinatario
            $table->string('guia_dest_tipo_doc', 1)->default('6'); // 1=DNI,6=RUC
            $table->string('guia_dest_numero_doc', 15);
            $table->string('guia_dest_nombre', 200);
            $table->string('guia_dest_direccion', 300)->nullable();

            // Punto de partida
            $table->string('guia_partida_ubigeo', 6)->nullable();
            $table->string('guia_partida_direccion', 300);

            // Punto de llegada
            $table->string('guia_llegada_ubigeo', 6)->nullable();
            $table->string('guia_llegada_direccion', 300);

            // Transportista (modalidad pública)
            $table->string('guia_transportista_ruc', 15)->nullable();
            $table->string('guia_transportista_nombre', 200)->nullable();
            $table->string('guia_transportista_mtt', 20)->nullable()
                ->comment('N° MTC del transportista');

            // Vehículo y conductor (modalidad privada)
            $table->string('guia_vehiculo_placa', 10)->nullable();
            $table->string('guia_conductor_tipo_doc', 1)->nullable(); // 1=DNI
            $table->string('guia_conductor_numero_doc', 15)->nullable();
            $table->string('guia_conductor_nombre', 200)->nullable();
            $table->string('guia_conductor_licencia', 20)->nullable();

            // Estado SUNAT
            $table->tinyInteger('guia_estado_sunat')->default(0);  // 0=no enviado,1=enviado
            $table->string('guia_ruta_xml', 300)->nullable();
            $table->string('guia_ruta_cdr', 300)->nullable();
            $table->string('guia_respuesta_sunat', 1000)->nullable();
            $table->dateTime('guia_fecha_envio')->nullable();

            // Estado general
            $table->string('guia_estado', 20)->default('borrador'); // borrador,enviado,aceptado,rechazado,anulado

            $table->timestamps();

            $table->index(['id_empresa', 'guia_fecha_emision']);
            $table->index(['id_sucursal', 'guia_fecha_emision']);
            $table->unique(['guia_serie', 'guia_correlativo', 'id_empresa']);
        });

        Schema::create('guias_remision_detalle', function (Blueprint $table) {
            $table->id('id_guia_detalle');
            $table->foreignId('id_guia')->constrained('guias_remision', 'id_guia')->cascadeOnDelete();
            $table->foreignId('id_pro')->constrained('productos', 'id_pro');
            $table->string('detalle_descripcion', 250);
            $table->string('detalle_codigo', 50)->nullable();
            $table->decimal('detalle_cantidad', 10, 3);
            $table->string('detalle_unidad_medida', 6)->default('NIU'); // NIU,KGM,ZZ
            $table->decimal('detalle_peso_unitario', 10, 3)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guias_remision_detalle');
        Schema::dropIfExists('guias_remision');
    }
};
