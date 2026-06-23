<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_detalle', function (Blueprint $table) {
            $table->string('pres_nombre', 50)->nullable()->after('pres_factor');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_detalle', function (Blueprint $table) {
            $table->dropColumn('pres_nombre');
        });
    }
};
