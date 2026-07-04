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
            $table->decimal('medida_cantidad', 12, 3)->default(1)->after('unidad_codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medida', function (Blueprint $table) {
            $table->dropColumn('medida_cantidad');
        });
    }
};
