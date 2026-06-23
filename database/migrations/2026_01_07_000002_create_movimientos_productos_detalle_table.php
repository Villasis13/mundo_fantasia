<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_productos_detalle', function (Blueprint $table) {
            $table->id('id_movimientos_productos_detalle');
            $table->foreignId('id_movimientos_productos')
                ->constrained('movimientos_productos', 'id_movimientos_productos');
            $table->foreignId('id_pro')->constrained('productos', 'id_pro');
            $table->string('movimientos_productos_detalle_cantidad');
            $table->string('movimientos_productos_detalle_estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_productos_detalle');
    }
};
