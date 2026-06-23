<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_detalle', function (Blueprint $table) {
            // Factor de conversión de la presentación (1.0 = unidad, 12.0 = docena, etc.)
            $table->decimal('pres_factor', 10, 4)->default(1)->after('pedido_deta_estado');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_detalle', function (Blueprint $table) {
            $table->dropColumn('pres_factor');
        });
    }
};
