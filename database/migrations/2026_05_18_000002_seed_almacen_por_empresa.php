<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $empresas = DB::table('empresa')->pluck('id_empresa');

        foreach ($empresas as $idEmpresa) {
            $existe = DB::table('almacen')
                ->where('id_empresa', $idEmpresa)
                ->where('almacen_estado', 1)
                ->exists();

            if (!$existe) {
                DB::table('almacen')->insert([
                    'id_empresa'     => $idEmpresa,
                    'almacen_nombre' => 'Almacén Principal',
                    'almacen_estado' => 1,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('almacen')
            ->whereNotNull('id_empresa')
            ->delete();
    }
};
