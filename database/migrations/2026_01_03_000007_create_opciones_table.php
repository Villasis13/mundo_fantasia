<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opciones', function (Blueprint $table) {
            $table->id('id_opciones');
            $table->foreignId('id_submenu')->constrained('submenu', 'id_submenu');
            $table->string('opciones_funcion');
            $table->string('opciones_nombre');
            $table->tinyInteger('opciones_orden')->nullable();
            $table->tinyInteger('opciones_mostrar')->nullable();
            $table->tinyInteger('opciones_estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opciones');
    }
};
