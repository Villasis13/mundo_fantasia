<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monedas', function (Blueprint $table) {
            $table->id('id_moneda');
            $table->string('moneda');
            $table->string('abreviado');
            $table->string('abrstandar');
            $table->string('simbolo');
            $table->tinyInteger('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monedas');
    }
};
