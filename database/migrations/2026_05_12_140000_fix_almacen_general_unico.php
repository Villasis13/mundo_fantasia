<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hacer id_empresa nullable (el almacén principal no pertenece a una empresa específica)
        Schema::table('almacen', function (Blueprint $table) {
            $table->unsignedInteger('id_empresa')->nullable()->change();
        });

        // Borrar los 3 registros por empresa y dejar solo uno global
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('almacen')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::table('almacen')->insert([
            'id_empresa'     => null,
            'almacen_nombre' => 'Almacén Principal',
            'almacen_estado' => 1,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('almacen')->truncate();
    }
};
