<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $empresas = DB::table('empresa')->pluck('id_empresa');

        foreach ($empresas as $idEmpresa) {
            $existe = DB::table('tiendas')
                ->where('id_empresa', $idEmpresa)
                ->where('tienda_tipo', 3)
                ->whereNull('id_tienda_padre')
                ->exists();

            if (!$existe) {
                DB::table('tiendas')->insert([
                    'id_empresa'       => $idEmpresa,
                    'id_tienda_padre'  => null,
                    'tienda_nombre'    => 'Almacén Principal',
                    'tienda_tipo'      => 3,
                    'tienda_principal' => 0,
                    'tienda_estado'    => 1,
                    'tienda_microtime' => microtime(true),
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('tiendas')
            ->where('tienda_nombre', 'Almacén Principal')
            ->where('tienda_tipo', 3)
            ->whereNull('id_tienda_padre')
            ->delete();
    }
};
