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
        Schema::table('medida', function (Blueprint $table) {
            $table->string('unidad_codigo', 225)->nullable()->after('medida_codigo_unidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medida', function (Blueprint $table) {
            $table->dropColumn('unidad_codigo');
        });
    }
};
