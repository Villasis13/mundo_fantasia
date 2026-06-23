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
        Schema::table('pedidos', function (Blueprint $table) {
            $table->tinyInteger('pedido_tipo_pago')->nullable()->default(null)->change();
        });
        // Reset existing pedidos to NULL so they show as "sin condición"
        DB::table('pedidos')->whereIn('pedido_estado', [0, 1])->update(['pedido_tipo_pago' => null]);
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->tinyInteger('pedido_tipo_pago')->default(1)->change();
        });
    }
};
