<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // 1 = stock descontado de ps_stock al crear/editar; 0 = pedido creado antes de esta feature
            $table->tinyInteger('pedido_stock_reservado')->default(0)->after('pedido_estado');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('pedido_stock_reservado');
        });
    }
};
