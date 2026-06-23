<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_pago', function (Blueprint $table) {
            $table->id('id_tipo_pago');
            $table->string('tipo_pago_nombre');
            $table->tinyInteger('tipo_pago_estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_pago');
    }
};
