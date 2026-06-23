<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_venta', function (Blueprint $table) {
            $table->id('id_tipo_venta');
            $table->string('tipo_venta_nombre');
            $table->tinyInteger('tipo_venta_estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_venta');
    }
};
