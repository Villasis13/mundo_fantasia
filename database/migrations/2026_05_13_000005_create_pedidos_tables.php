<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->increments('id_pedido');
            $table->unsignedInteger('id_empresa');
            $table->unsignedInteger('id_tienda')->nullable();
            $table->unsignedInteger('id_users');
            $table->unsignedInteger('id_clientes')->nullable();
            $table->string('pedido_numero', 20)->unique();
            $table->string('pedido_cliente_nombre', 255)->nullable();
            $table->string('pedido_cliente_doc', 20)->nullable();
            $table->text('pedido_observacion')->nullable();
            $table->tinyInteger('pedido_estado')->default(0); // 0=pendiente, 1=en_caja, 2=despachado, 3=anulado
            $table->timestamps();
        });

        Schema::create('pedidos_detalle', function (Blueprint $table) {
            $table->increments('id_pedido_detalle');
            $table->unsignedInteger('id_pedido');
            $table->unsignedInteger('id_pro');
            $table->string('pedido_deta_nombre', 255);
            $table->decimal('pedido_deta_cantidad', 10, 2);
            $table->decimal('pedido_deta_precio', 10, 2);
            $table->tinyInteger('pedido_deta_estado')->default(1);
            $table->timestamps();

            $table->foreign('id_pedido')->references('id_pedido')->on('pedidos')->onDelete('cascade');
        });

        // Añadir id_pedido a ventas
        Schema::table('ventas', function (Blueprint $table) {
            $table->unsignedInteger('id_pedido')->nullable()->after('id_profo');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('id_pedido');
        });

        Schema::dropIfExists('pedidos_detalle');
        Schema::dropIfExists('pedidos');
    }
};
