<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursals', function (Blueprint $table) {
            $table->id('id_sucursal');
            $table->foreignId('id_empresa')->references( 'id_empresa')->on('empresa');
            $table->string('sucursal_nombre');
            $table->string('sucursal_direccion', 1000)->nullable();
            $table->tinyInteger('sucursal_estado')->comment('1 - Activo, 0 - Inactivo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursals');
    }
};
