<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacen_producto', function (Blueprint $table) {
            $table->bigIncrements('id_ap');
            $table->unsignedInteger('id_tienda');   // tienda con tienda_tipo=3 (almacén)
            $table->unsignedBigInteger('id_pro');
            $table->decimal('ap_stock', 12, 4)->default(0);
            $table->decimal('ap_precio_costo', 12, 4)->nullable();
            $table->tinyInteger('ap_estado')->default(1);
            $table->timestamps();

            $table->unique(['id_tienda', 'id_pro']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almacen_producto');
    }
};
