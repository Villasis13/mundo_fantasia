<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesPermisosSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'superadmin',    'rol_descripcion' => 'Tiene acceso a la gestión total del sistema', 'rol_estado' => 1, 'guard_name' => 'web'],
            ['id' => 2, 'name' => 'Administrador', 'rol_descripcion' => 'Gestión del sistema',                         'rol_estado' => 1, 'guard_name' => 'web'],
            ['id' => 3, 'name' => 'Vendedor',      'rol_descripcion' => 'Gestión de ventas',                           'rol_estado' => 1, 'guard_name' => 'web'],
            ['id' => 4, 'name' => 'Contador',      'rol_descripcion' => 'Reporte de ventas',                           'rol_estado' => 1, 'guard_name' => 'web'],
        ]);

        $permissions = json_decode(file_get_contents(__DIR__ . '/data/permissions.json'), true);
        foreach (array_chunk($permissions, 100) as $chunk) {
            DB::table('permissions')->insert($chunk);
        }

        $rolePerms = json_decode(file_get_contents(__DIR__ . '/data/role_has_permissions.json'), true);
        foreach (array_chunk($rolePerms, 200) as $chunk) {
            DB::table('role_has_permissions')->insert($chunk);
        }

        $modelRoles = json_decode(file_get_contents(__DIR__ . '/data/model_has_roles.json'), true);
        DB::table('model_has_roles')->insert($modelRoles);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
