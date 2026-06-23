<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id('id_clientes');
            $table->foreignId('id_empresa')->nullable()
                ->constrained('empresa', 'id_empresa')->nullOnDelete();
            $table->foreignId('id_tipo_documento')->constrained('tipo_documento', 'id_tipo_documento');
            $table->string('cliente_razonsocial', 500)->nullable();
            $table->string('cliente_nombre')->nullable();
            $table->string('cliente_numero')->nullable();
            $table->string('cliente_correo')->nullable();
            $table->string('cliente_direccion')->nullable();
            $table->string('cliente_direccion_2')->nullable();
            $table->string('cliente_telefono')->nullable();
            $table->dateTime('cliente_fecha');
            $table->tinyInteger('cliente_estado');
            $table->string('cliente_codigo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
