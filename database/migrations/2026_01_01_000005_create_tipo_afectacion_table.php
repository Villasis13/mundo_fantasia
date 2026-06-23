<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_afectacion', function (Blueprint $table) {
            $table->id('id_tipo_afectacion');
            $table->char('codigo', 2);
            $table->string('descripcion', 150);
            $table->char('codigo_afectacion', 4);
            $table->char('nombre_afectacion', 3);
            $table->char('tipo_afectacion', 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_afectacion');
    }
};
