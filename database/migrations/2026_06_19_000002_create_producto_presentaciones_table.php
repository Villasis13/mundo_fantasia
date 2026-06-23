<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_presentaciones', function (Blueprint $table) {
            $table->increments('id_pres');
            $table->unsignedBigInteger('id_pro');
            $table->string('pres_nombre', 50);       // 'Unidad', 'Docena', etc.
            $table->string('pres_abreviatura', 10);   // 'Und', 'Doc', etc.
            $table->decimal('pres_factor', 10, 4)->default(1); // 1.0000 base, 12.0000 docena
            $table->decimal('pres_precio_1', 10, 2)->default(0);      // PRECIO público
            $table->decimal('pres_precio_2', 10, 2)->default(0);      // PRECIODIST
            $table->decimal('pres_precio_costo', 12, 4)->default(0);  // PRECOSTO
            $table->tinyInteger('pres_estado')->default(1);
            $table->timestamps();

            $table->foreign('id_pro')->references('id_pro')->on('productos')->onDelete('cascade');
            $table->index('id_pro');
            $table->unique(['id_pro', 'pres_nombre'], 'uq_pres_pro_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_presentaciones');
    }
};
