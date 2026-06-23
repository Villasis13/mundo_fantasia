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
        Schema::table('transferencias_stock', function (Blueprint $table) {
            $table->text('transferencia_motivo_anulacion')->nullable()->after('transferencia_motivo');
        });
    }

    public function down(): void
    {
        Schema::table('transferencias_stock', function (Blueprint $table) {
            $table->dropColumn('transferencia_motivo_anulacion');
        });
    }
};
