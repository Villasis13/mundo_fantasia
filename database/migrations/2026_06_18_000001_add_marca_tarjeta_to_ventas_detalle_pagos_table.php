<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas_detalle_pagos', function (Blueprint $table) {
            $table->string('marca_tarjeta', 50)->nullable()->after('id_tipo_pago');
        });
    }

    public function down(): void
    {
        Schema::table('ventas_detalle_pagos', function (Blueprint $table) {
            $table->dropColumn('marca_tarjeta');
        });
    }
};
