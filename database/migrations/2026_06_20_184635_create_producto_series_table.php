<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('producto_series', function (Blueprint $table) {
            $table->id('id_producto_serie');
            $table->unsignedBigInteger('id_pro');
            $table->string('numero_serie', 100);
            $table->tinyInteger('estado')->default(1)->comment('1=disponible, 2=vendido, 0=baja');
            $table->unsignedBigInteger('id_venta')->nullable();
            $table->unsignedBigInteger('id_orden_compra')->nullable();
            $table->string('observacion', 255)->nullable();
            $table->unsignedBigInteger('id_users')->nullable();
            $table->timestamps();

            $table->foreign('id_pro')->references('id_pro')->on('productos');
            $table->unique(['id_pro', 'numero_serie']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_series');
    }
};
