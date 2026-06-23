<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_compra', function (Blueprint $table) {
            $table->text('orden_compra_motivo_anulacion')
                  ->nullable()
                  ->after('orden_compra_estado');
        });
    }

    public function down(): void
    {
        Schema::table('orden_compra', function (Blueprint $table) {
            $table->dropColumn('orden_compra_motivo_anulacion');
        });
    }
};
