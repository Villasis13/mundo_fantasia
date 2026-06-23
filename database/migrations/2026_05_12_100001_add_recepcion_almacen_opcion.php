<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insertar nueva opción en el submenú de compras (id=53)
        $idOpcion = DB::table('opciones')->insertGetId([
            'id_submenu'       => 53,
            'opciones_funcion' => 'recepcion_almacen',
            'opciones_nombre'  => 'Recepción De Almacén',
            'opciones_orden'   => 3,
            'opciones_mostrar' => 1,
            'opciones_estado'  => 1,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Permiso de opción
        $permOpcion = DB::table('permissions')->insertGetId([
            'id_menu'             => null,
            'id_submenu'          => null,
            'id_opciones'         => $idOpcion,
            'name'                => 'recepcion_almacen.opcion',
            'guard_name'          => 'web',
            'permiso_estado'      => 1,
            'permiso_grupo'       => 3,
            'permiso_grupo_grupo' => $idOpcion,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Permisos de acción
        $acciones = ['listar', 'crear', 'actualizar', 'cambiar_estado', 'eliminar', 'aprobar', 'exportar'];
        $permIds  = [$permOpcion];
        foreach ($acciones as $accion) {
            $permIds[] = DB::table('permissions')->insertGetId([
                'id_menu'             => null,
                'id_submenu'          => null,
                'id_opciones'         => null,
                'name'                => "recepcion_almacen.{$accion}",
                'guard_name'          => 'web',
                'permiso_estado'      => 1,
                'permiso_grupo'       => 4,
                'permiso_grupo_grupo' => $idOpcion,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        // Asignar todos los permisos al superadmin (role 1) y admin (role 2)
        $inserts = [];
        foreach ($permIds as $pid) {
            $inserts[] = ['permission_id' => $pid, 'role_id' => 1];
            $inserts[] = ['permission_id' => $pid, 'role_id' => 2];
        }
        DB::table('role_has_permissions')->insert($inserts);

        // Limpiar caché de Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->where('name', 'like', 'recepcion_almacen.%')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        DB::table('opciones')
            ->where('opciones_funcion', 'recepcion_almacen')
            ->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
