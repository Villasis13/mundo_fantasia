<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_compra', function (Blueprint $table) {
            $table->unsignedInteger('id_empresa')->nullable()->after('id_sucursal');
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra', function (Blueprint $table) {
            $table->dropColumn('id_empresa');
        });
    }
};
