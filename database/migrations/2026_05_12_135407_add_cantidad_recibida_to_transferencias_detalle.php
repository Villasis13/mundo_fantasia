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
        Schema::table('transferencias_stock_detalle', function (Blueprint $table) {
            $table->decimal('detalle_cantidad_recibida', 12, 2)->nullable()->after('detalle_cantidad');
        });
    }

    public function down(): void
    {
        Schema::table('transferencias_stock_detalle', function (Blueprint $table) {
            $table->dropColumn('detalle_cantidad_recibida');
        });
    }
};
