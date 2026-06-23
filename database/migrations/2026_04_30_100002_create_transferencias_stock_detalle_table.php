<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencias_stock_detalle', function (Blueprint $table) {
            $table->id('id_transferencia_detalle');
            $table->unsignedBigInteger('id_transferencia');
            $table->unsignedBigInteger('id_pro');
            $table->decimal('detalle_cantidad', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias_stock_detalle');
    }
};
