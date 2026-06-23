<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->integer('id_menu')->nullable();
            $table->integer('id_submenu')->nullable();
            $table->foreignId('id_opciones')->nullable()
                ->constrained('opciones', 'id_opciones')
                ->nullOnDelete()->cascadeOnUpdate();
            $table->string('name');
            $table->string('guard_name');
            $table->integer('permiso_estado')->default(1);
            $table->tinyInteger('permiso_grupo')->nullable()
                ->comment('controladores 1 , submenus 2, opciones 3');
            $table->tinyInteger('permiso_grupo_grupo')->nullable();
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
