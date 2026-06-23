<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id('id_proveedores');
            $table->foreignId('id_empresa')->nullable()
                ->constrained('empresa', 'id_empresa')->nullOnDelete();
            $table->bigInteger('id_sede')->default(1);
            $table->foreignId('id_tipo_documento')->constrained('tipo_documento', 'id_tipo_documento');
            $table->string('proveedores_nombre');
            $table->string('proveedores_numero_documento');
            $table->string('proveedores_direccion')->nullable();
            $table->string('proveedores_nombre_contacto')->nullable();
            $table->string('proveedores_cargo')->nullable();
            $table->string('proveedores_telefono')->nullable();
            $table->string('proveedores_correo', 250)->nullable();
            $table->string('proveedores_estado');
            $table->tinyInteger('proveedoes_categoria')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
