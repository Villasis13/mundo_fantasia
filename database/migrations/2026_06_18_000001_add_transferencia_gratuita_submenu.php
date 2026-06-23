<?php

use App\Service\PermisoService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insertar submenú bajo Gestión de Ventas (id_menu = 15)
        DB::table('submenu')->insert([
            'id_menu'         => 15,
            'submenu_nombre'  => 'Trans. Gratuita',
            'submenu_funcion' => 'transferencia_gratuita',
            'submenu_mostrar' => 1,
            'submenu_orden'   => 74,
            'submenu_estado'  => 1,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $idSubmenu = (int) DB::getPdo()->lastInsertId();

        $topRoles = \Spatie\Permission\Models\Role::whereIn('id', [1, 2])->pluck('name')->toArray();

        // Crear permiso de submenú
        $permSubmenu = \Spatie\Permission\Models\Permission::firstOrCreate(
            ['name' => 'transferencia_gratuita.submenu', 'guard_name' => 'web'],
            [
                'id_menu'             => null,
                'id_submenu'          => $idSubmenu,
                'id_opciones'         => null,
                'permiso_grupo'       => 2,
                'permiso_grupo_grupo' => $idSubmenu,
                'permiso_estado'      => 1,
            ]
        );
        $permSubmenu->assignRole($topRoles);

        // Crear permisos de acción
        foreach (['listar', 'crear'] as $accion) {
            $p = \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => "transferencia_gratuita.{$accion}", 'guard_name' => 'web'],
                [
                    'id_menu'             => null,
                    'id_submenu'          => $idSubmenu,
                    'id_opciones'         => null,
                    'permiso_grupo'       => 4,
                    'permiso_grupo_grupo' => $idSubmenu,
                    'permiso_estado'      => 1,
                ]
            );
            $p->assignRole($topRoles);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('submenu')->where('submenu_funcion', 'transferencia_gratuita')->delete();

        \Spatie\Permission\Models\Permission::whereIn('name', [
            'transferencia_gratuita.submenu',
            'transferencia_gratuita.listar',
            'transferencia_gratuita.crear',
        ])->delete();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
