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
        Schema::table('orden_compra', function (Blueprint $table) {
            $table->enum('condicion_pago', ['contado', 'credito'])
                  ->default('contado')
                  ->after('id_tipo_pago');
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra', function (Blueprint $table) {
            $table->dropColumn('condicion_pago');
        });
    }
};
