<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa', function (Blueprint $table) {
            $table->id('id_empresa');
            $table->string('empresa_razon_social');
            $table->string('empresa_nombrecomercial')->nullable();
            $table->string('empresa_descripcion')->nullable();
            $table->string('empresa_ruc', 20);
            $table->string('empresa_domiciliofiscal');
            $table->string('empresa_pais');
            $table->string('empresa_telefono1', 50)->nullable();
            $table->string('empresa_telefono2', 50)->nullable();
            $table->string('empresa_foto')->nullable();
            $table->string('empresa_foto_ticket')->nullable();
            $table->string('empresa_correo')->nullable();
            $table->string('empresa_usuario_sol', 50);
            $table->string('empresa_clave_sol', 50);
            $table->string('empresa_estado');
            $table->text('empresa_ruta_certificado')->nullable();
            $table->text('empresa_clave_certificado')->nullable();
            $table->foreignId('id_ubigeo')->nullable()
                ->constrained('ubigeo', 'id_ubigeo')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa');
    }
};
