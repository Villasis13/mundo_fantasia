<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_ndebitos', function (Blueprint $table) {
            $table->id('id_tipo_ndebitos');
            $table->string('codigo', 100);
            $table->string('tipo_nota_descripcion');
            $table->tinyInteger('estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_ndebitos');
    }
};
