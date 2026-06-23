<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_gasto', function (Blueprint $table) {
            $table->increments('id_tipo_gasto');
            $table->string('tipo_gasto_nombre', 150);
            $table->tinyInteger('tipo_gasto_estado')->default(1);
            $table->timestamps();
        });

        Schema::create('gastos', function (Blueprint $table) {
            $table->increments('id_gasto');
            $table->unsignedInteger('id_empresa');
            $table->unsignedInteger('id_tienda')->nullable();
            $table->unsignedInteger('id_caja_numero')->nullable();
            $table->unsignedInteger('id_tipo_gasto');
            $table->unsignedInteger('id_users');
            $table->text('gasto_detalle');
            $table->decimal('gasto_monto', 12, 2)->default(0);
            $table->date('gasto_fecha');
            $table->text('gasto_observacion')->nullable();
            $table->tinyInteger('gasto_estado')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
        Schema::dropIfExists('tipo_gasto');
    }
};
