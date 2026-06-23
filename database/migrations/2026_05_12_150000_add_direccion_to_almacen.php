<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('almacen', function (Blueprint $table) {
            $table->string('almacen_direccion', 500)->nullable()->after('almacen_nombre');
        });

        DB::table('almacen')
            ->where('id_almacen', 1)
            ->update(['almacen_direccion' => 'Calle Jose olaya #389']);
    }

    public function down(): void
    {
        Schema::table('almacen', function (Blueprint $table) {
            $table->dropColumn('almacen_direccion');
        });
    }
};
