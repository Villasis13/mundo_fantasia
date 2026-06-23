<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas_detalle', function (Blueprint $table) {
            $table->decimal('pres_factor', 10, 4)->default(1)->after('pres_nombre');
        });
    }

    public function down(): void
    {
        Schema::table('ventas_detalle', function (Blueprint $table) {
            $table->dropColumn('pres_factor');
        });
    }
};
