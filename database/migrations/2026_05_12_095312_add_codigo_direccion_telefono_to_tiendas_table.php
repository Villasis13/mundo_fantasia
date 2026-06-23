<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $table) {
            $table->string('tienda_codigo', 50)->nullable()->after('tienda_nombre');
            $table->string('tienda_direccion', 500)->nullable()->after('tienda_codigo');
            $table->string('tienda_telefono', 30)->nullable()->after('tienda_direccion');
        });
    }

    public function down(): void
    {
        Schema::table('tiendas', function (Blueprint $table) {
            $table->dropColumn(['tienda_codigo', 'tienda_direccion', 'tienda_telefono']);
        });
    }
};
