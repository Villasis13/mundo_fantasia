<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medida', function (Blueprint $table) {
            $table->id('id_medida');
            $table->string('medida_codigo_unidad');
            $table->string('medida_nombre');
            $table->tinyInteger('medida_activo');
            $table->tinyInteger('medida_grupo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medida');
    }
};
