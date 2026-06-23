<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign('ventas_id_sucursal_foreign');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreign('id_sucursal')
                  ->references('id_sucursal')
                  ->on('sucursals')
                  ->onDelete('set null');
        });
    }
};
