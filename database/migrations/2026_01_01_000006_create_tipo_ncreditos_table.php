<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_ncreditos', function (Blueprint $table) {
            $table->id('id_tipo_ncreditos');
            $table->string('codigo', 10);
            $table->string('tipo_nota_descripcion', 250);
            $table->tinyInteger('estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_ncreditos');
    }
};
