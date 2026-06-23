<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_sucursal', function (Blueprint $table) {
            $table->id('id_ps');
            $table->foreignId('id_pro')->constrained('productos', 'id_pro')->cascadeOnDelete();
            $table->foreignId('id_sucursal')->constrained('sucursals', 'id_sucursal')->cascadeOnDelete();
            $table->foreignId('id_tipo_afectacion')->nullable()
                ->constrained('tipo_afectacion', 'id_tipo_afectacion')->nullOnDelete();
            $table->decimal('ps_precio_uni', 10, 2)->default(0.00);
            $table->decimal('ps_precio_uni_2', 10, 2)->default(0.00);
            $table->decimal('ps_precio_uni_3', 10, 2)->default(0.00);
            $table->decimal('ps_stock', 10, 2)->default(0.00);
            $table->decimal('ps_stock_minimo', 10, 2)->default(0.00);
            $table->decimal('ps_porcen_igv', 5, 2)->default(18.00);
            $table->tinyInteger('ps_estado')->default(1);
            $table->timestamps();

            $table->unique(['id_pro', 'id_sucursal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_sucursal');
    }
};
