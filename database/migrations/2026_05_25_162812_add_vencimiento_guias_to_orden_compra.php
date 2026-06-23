<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_compra', function (Blueprint $table) {
            $table->date('orden_compra_fecha_vencimiento')->nullable()->after('orden_compra_fecha_emision_doc');
            $table->string('orden_compra_guia_remitente', 100)->nullable()->after('orden_compra_fecha_vencimiento');
            $table->string('orden_compra_guia_transportista', 100)->nullable()->after('orden_compra_guia_remitente');
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra', function (Blueprint $table) {
            $table->dropColumn([
                'orden_compra_fecha_vencimiento',
                'orden_compra_guia_remitente',
                'orden_compra_guia_transportista',
            ]);
        });
    }
};
