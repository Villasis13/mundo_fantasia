<?php

namespace Database\Seeders;

use App\Service\PermisoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Genera los permisos de acción (permiso_grupo=4) para las opciones
 * que solo tenían el permiso .opcion pero no los permisos de acción.
 *
 * Opciones afectadas:
 *   93  → gestion_de_cajas
 *   98  → opcion_gestion_empresas
 *   99  → gestion_planes
 *  100  → sucursal_opcion
 */
class PermisosAccionFaltantesSeeder extends Seeder
{
    public function run(): void
    {
        $opciones = [
            93  => 'gestion_de_cajas',
            98  => 'opcion_gestion_empresas',
            99  => 'gestion_planes',
            100 => 'sucursal_opcion',
        ];

        $servicio = new PermisoService();

        foreach ($opciones as $idOpcion => $funcion) {
            $existe = DB::table('opciones')->where('id_opciones', $idOpcion)->exists();

            if (!$existe) {
                $this->command->warn("Opcion {$idOpcion} ({$funcion}) no encontrada. Omitida.");
                continue;
            }

            $servicio->crearOpcion($idOpcion, $funcion);
            $this->command->info("Permisos generados para opcion {$idOpcion} ({$funcion}).");
        }
    }
}
