<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->call([
            // 1. Catálogos independientes (sin FK)
            UbigeoSeeder::class,
            CatalogosSeeder::class,     // medida, tipo_documento, tipo_pago, tipo_afectacion, tipo_ncreditos, tipo_ndebitos, monedas, tipo_venta, planes

            // 2. Empresa y estructura
            EmpresaSeeder::class,       // empresa → sucursals → empresa_planes

            // 3. Menú y navegación
            MenuSeeder::class,          // menus → submenu → opciones

            // 4. Roles y permisos Spatie (depende de opciones)
            RolesPermisosSeeder::class,              // roles → permissions → role_has_permissions → model_has_roles
            PermisosAccionFaltantesSeeder::class,    // acción permisos faltantes para 4 opciones

            // 5. Usuarios (depende de empresa y persona)
            UsuariosSeeder::class,      // persona → users → user_sucursal

            // 6. Caja y series (depende de sucursals)
            CajaSerieSeeder::class,     // caja_numero → serie
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
