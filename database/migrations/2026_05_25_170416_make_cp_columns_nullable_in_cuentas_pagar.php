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
        Schema::table('cuentas_pagar', function (Blueprint $table) {
            $table->string('cp_tipo_doc', 50)->nullable()->change();
            $table->string('cp_numero_doc', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cuentas_pagar', function (Blueprint $table) {
            $table->string('cp_tipo_doc', 50)->nullable(false)->change();
            $table->string('cp_numero_doc', 50)->nullable(false)->change();
        });
    }
};
