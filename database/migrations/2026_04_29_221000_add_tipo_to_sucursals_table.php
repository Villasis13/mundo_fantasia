<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sucursals', function (Blueprint $table) {
            // 1=Tienda, 2=Sucursal, 3=Almacén
            $table->tinyInteger('sucursal_tipo')->default(2)->after('sucursal_nombre');
        });
    }

    public function down(): void
    {
        Schema::table('sucursals', function (Blueprint $table) {
            $table->dropColumn('sucursal_tipo');
        });
    }
};
