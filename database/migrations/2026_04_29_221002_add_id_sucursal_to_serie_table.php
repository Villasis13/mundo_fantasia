<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('serie', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sucursal')->nullable()->after('id_empresa');
        });
    }

    public function down(): void
    {
        Schema::table('serie', function (Blueprint $table) {
            $table->dropColumn('id_sucursal');
        });
    }
};
