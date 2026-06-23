<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id('id_menu');
            $table->string('menu_nombre');
            $table->string('menu_controlador');
            $table->string('menu_icono')->nullable();
            $table->string('menu_orden');
            $table->tinyInteger('menu_mostrar');
            $table->tinyInteger('menu_estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
