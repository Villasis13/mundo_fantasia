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
        Schema::table('autoconsumo', function (Blueprint $table) {
            $table->string('autoconsumo_autorizacion', 200)->nullable()->change();
            $table->text('autoconsumo_motivo')->nullable()->after('autoconsumo_autorizacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('autoconsumo', function (Blueprint $table) {
            $table->dropColumn('autoconsumo_motivo');
        });
    }
};
