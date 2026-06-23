<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_compra', function (Blueprint $table) {
            $table->id('id_nota_compra');
            $table->bigInteger('id_empresa')->nullable();
            $table->unsignedBigInteger('id_proveedores');
            $table->unsignedBigInteger('id_orden_compra')->nullable();
            $table->unsignedBigInteger('id_almacen')->nullable();
            $table->unsignedBigInteger('id_users');
            $table->enum('tipo_nota', ['NC', 'DB']);
            $table->string('nota_numero', 20);
            $table->string('nota_numero_doc', 100)->nullable();
            $table->date('nota_fecha');
            $table->text('nota_motivo')->nullable();
            $table->decimal('nota_total', 10, 2)->default(0);
            $table->tinyInteger('nota_afecta_stock')->default(0);
            $table->enum('nota_estado', ['pendiente', 'aprobado', 'anulado'])->default('pendiente');
            $table->text('nota_observacion')->nullable();
            $table->timestamps();

            $table->index(['id_empresa', 'tipo_nota', 'nota_estado']);
            $table->index('id_proveedores');
            $table->index('id_orden_compra');
        });

        Schema::create('notas_compra_detalle', function (Blueprint $table) {
            $table->id('id_nota_detalle');
            $table->unsignedBigInteger('id_nota_compra');
            $table->unsignedBigInteger('id_pro')->nullable();
            $table->string('detalle_descripcion', 250)->nullable();
            $table->double('detalle_cantidad', 8, 2)->default(0);
            $table->decimal('detalle_precio', 10, 2)->default(0);
            $table->decimal('detalle_total', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('id_nota_compra')
                ->references('id_nota_compra')->on('notas_compra')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_compra_detalle');
        Schema::dropIfExists('notas_compra');
    }
};
