<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla almacen
        Schema::create('almacen', function (Blueprint $table) {
            $table->bigIncrements('id_almacen');
            $table->unsignedInteger('id_empresa');
            $table->string('almacen_nombre', 255);
            $table->tinyInteger('almacen_estado')->default(1);
            $table->timestamps();
        });

        // 2. Insertar "Almacén Principal" para cada empresa
        $empresas = DB::table('empresa')->pluck('id_empresa');
        foreach ($empresas as $idEmpresa) {
            DB::table('almacen')->insert([
                'id_empresa'    => $idEmpresa,
                'almacen_nombre'=> 'Almacén Principal',
                'almacen_estado'=> 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        // 3. Eliminar los registros falsos de tiendas (tienda_tipo=3 sin padre)
        //    que se crearon en la migración anterior
        DB::table('tiendas')
            ->where('tienda_tipo', 3)
            ->whereNull('id_tienda_padre')
            ->delete();

        // 4. Recrear almacen_producto usando id_almacen en lugar de id_tienda
        Schema::drop('almacen_producto');

        Schema::create('almacen_producto', function (Blueprint $table) {
            $table->bigIncrements('id_ap');
            $table->unsignedBigInteger('id_almacen');
            $table->unsignedBigInteger('id_pro');
            $table->decimal('ap_stock', 12, 4)->default(0);
            $table->decimal('ap_precio_costo', 12, 4)->nullable();
            $table->tinyInteger('ap_estado')->default(1);
            $table->timestamps();

            $table->unique(['id_almacen', 'id_pro']);
            $table->foreign('id_almacen')->references('id_almacen')->on('almacen')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almacen_producto');
        Schema::dropIfExists('almacen');

        // Recrear almacen_producto original
        Schema::create('almacen_producto', function (Blueprint $table) {
            $table->bigIncrements('id_ap');
            $table->unsignedInteger('id_tienda');
            $table->unsignedBigInteger('id_pro');
            $table->decimal('ap_stock', 12, 4)->default(0);
            $table->decimal('ap_precio_costo', 12, 4)->nullable();
            $table->tinyInteger('ap_estado')->default(1);
            $table->timestamps();
            $table->unique(['id_tienda', 'id_pro']);
        });
    }
};
