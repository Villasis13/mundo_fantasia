<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('envio_resumen', function (Blueprint $table) {
            $table->id('id_envio_resumen');
            $table->foreignId('id_empresa')->constrained('empresa', 'id_empresa');
            $table->date('envio_resumen_fecha');
            $table->string('envio_resumen_serie');
            $table->string('envio_resumen_correlativo');
            $table->string('envio_resumen_nombreXML');
            $table->string('envio_resumen_nombreCDR')->nullable();
            $table->tinyInteger('envio_resumen_estado');
            $table->string('envio_resumen_estadosunat');
            $table->string('envio_resumen_estadosunat_consulta')->nullable();
            $table->string('envio_resumen_ticket');
            $table->dateTime('envio_sunat_datetime')->nullable();
            $table->string('envio_resumen_codigo_hash', 1000)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('envio_resumen');
    }
};
