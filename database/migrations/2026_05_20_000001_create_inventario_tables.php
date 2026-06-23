<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario', function (Blueprint $table) {
            $table->bigIncrements('id_inventario');
            $table->string('inventario_numero', 30)->unique();
            $table->unsignedBigInteger('id_almacen')->nullable();
            $table->unsignedBigInteger('id_tienda')->nullable();
            $table->unsignedBigInteger('id_users');
            $table->date('inventario_fecha');
            $table->enum('inventario_estado', ['borrador', 'confirmado'])->default('borrador');
            $table->text('inventario_observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('inventario_detalle', function (Blueprint $table) {
            $table->bigIncrements('id_inventario_detalle');
            $table->unsignedBigInteger('id_inventario');
            $table->unsignedBigInteger('id_pro');
            $table->decimal('stock_sistema', 12, 4)->default(0);
            $table->decimal('stock_contado', 12, 4)->default(0);
            $table->decimal('diferencia',    12, 4)->default(0); // + sobrante, - faltante
            $table->timestamps();

            $table->foreign('id_inventario')
                ->references('id_inventario')
                ->on('inventario')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_detalle');
        Schema::dropIfExists('inventario');
    }
};
