<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_productos', function (Blueprint $table) {
            $table->string('concepto', 100)->nullable()->after('movimientos_productos_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_productos', function (Blueprint $table) {
            $table->dropColumn('concepto');
        });
    }
};
