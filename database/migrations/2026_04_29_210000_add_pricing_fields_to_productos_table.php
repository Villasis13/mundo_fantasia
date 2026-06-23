<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_fac')->nullable()->after('pro_foto');
            $table->string('pro_codigo_interno', 255)->nullable()->after('id_fac');
            $table->decimal('pro_costo_base', 15, 2)->default(0)->after('pro_codigo_interno');
            $table->decimal('pro_flete', 15, 2)->default(0)->after('pro_costo_base');
            $table->decimal('pro_margen_ganancia', 15, 2)->default(0)->after('pro_flete');
            $table->decimal('pro_costo_total', 15, 2)->default(0)->after('pro_margen_ganancia');
            $table->decimal('pro_precio_venta', 15, 2)->default(0)->after('pro_costo_total');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn([
                'id_fac', 'pro_codigo_interno', 'pro_costo_base',
                'pro_flete', 'pro_margen_ganancia', 'pro_costo_total', 'pro_precio_venta',
            ]);
        });
    }
};
