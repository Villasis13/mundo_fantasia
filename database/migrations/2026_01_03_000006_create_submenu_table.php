<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submenu', function (Blueprint $table) {
            $table->id('id_submenu');
            $table->foreignId('id_menu')->constrained('menus', 'id_menu');
            $table->string('submenu_nombre');
            $table->string('submenu_funcion');
            $table->tinyInteger('submenu_mostrar');
            $table->integer('submenu_orden');
            $table->tinyInteger('submenu_estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submenu');
    }
};
