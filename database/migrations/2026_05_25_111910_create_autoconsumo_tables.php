<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autoconsumo', function (Blueprint $table) {
            $table->bigIncrements('id_autoconsumo');
            $table->string('autoconsumo_numero', 30)->unique();
            $table->unsignedBigInteger('id_almacen')->nullable();
            $table->unsignedBigInteger('id_tienda')->nullable();
            $table->string('autoconsumo_area', 100)->default('Administración');
            $table->string('autoconsumo_autorizacion', 200);
            $table->date('autoconsumo_fecha');
            $table->unsignedBigInteger('id_users');
            $table->string('autoconsumo_estado', 20)->default('registrado');
            $table->timestamps();
        });

        Schema::create('autoconsumo_detalle', function (Blueprint $table) {
            $table->bigIncrements('id_autoconsumo_detalle');
            $table->unsignedBigInteger('id_autoconsumo');
            $table->unsignedBigInteger('id_pro');
            $table->decimal('detalle_cantidad', 10, 2);
            $table->decimal('detalle_costo', 10, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autoconsumo_detalle');
        Schema::dropIfExists('autoconsumo');
    }
};
