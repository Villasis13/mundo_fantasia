<?php

namespace App\Service;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermisoService
{
    const ACCIONES = ['listar', 'crear', 'actualizar', 'cambiar_estado', 'eliminar', 'aprobar','exportar'];

    private Logs $logs;

    public function __construct()
    {
        $this->logs = new Logs();
    }

    // ── Creación ───────────────────────────────────────────────

    public function crearMenu(int $idMenu, string $controlador): void
    {
        $permiso = Permission::firstOrCreate(
            ['name' => "{$controlador}.menu", 'guard_name' => 'web'],
            [
                'id_menu'             => $idMenu,
                'id_submenu'          => null,
                'id_opciones'         => null,
                'permiso_grupo'       => 1,
                'permiso_grupo_grupo' => $idMenu,
                'permiso_estado'      => 1,
            ]
        );
        $permiso->syncRoles('superadmin');
        $this->limpiarCache();
    }

    public function crearSubmenu(int $idSubmenu, string $funcion): void
    {
        $permiso = Permission::firstOrCreate(
            ['name' => "{$funcion}.submenu", 'guard_name' => 'web'],
            [
                'id_menu'             => null,
                'id_submenu'          => $idSubmenu,
                'id_opciones'         => null,
                'permiso_grupo'       => 2,
                'permiso_grupo_grupo' => $idSubmenu,
                'permiso_estado'      => 1,
            ]
        );
        $permiso->syncRoles('superadmin');
        $this->limpiarCache();
    }

    /**
     * Crea el permiso base de la opción (.opcion) más los 6 permisos de acción estándar.
     * Para agregar nuevos tipos de acción en el futuro, solo añade el sufijo a ACCIONES.
     */
    public function crearOpcion(int $idOpcion, string $funcion): void
    {
        $base = Permission::firstOrCreate(
            ['name' => "{$funcion}.opcion", 'guard_name' => 'web'],
            [
                'id_menu'             => null,
                'id_submenu'          => null,
                'id_opciones'         => $idOpcion,
                'permiso_grupo'       => 3,
                'permiso_grupo_grupo' => $idOpcion,
                'permiso_estado'      => 1,
            ]
        );
        $base->syncRoles('superadmin');

        foreach (self::ACCIONES as $accion) {
            $p = Permission::firstOrCreate(
                ['name' => "{$funcion}.{$accion}", 'guard_name' => 'web'],
                [
                    'id_menu'             => null,
                    'id_submenu'          => null,
                    'id_opciones'         => null,
                    'permiso_grupo'       => 4,
                    'permiso_grupo_grupo' => $idOpcion,
                    'permiso_estado'      => 1,
                ]
            );
            $p->syncRoles('superadmin');
        }

        $this->limpiarCache();
    }

    // ── Renombrado en cascada ──────────────────────────────────

    public function renombrarMenu(int $idMenu, string $nuevoControlador): void
    {
        DB::table('permissions')
            ->where('permiso_grupo', 1)
            ->where('permiso_grupo_grupo', $idMenu)
            ->update(['name' => "{$nuevoControlador}.menu"]);

        $this->limpiarCache();
    }

    public function renombrarSubmenu(int $idSubmenu, string $nuevaFuncion): void
    {
        DB::table('permissions')
            ->where('permiso_grupo', 2)
            ->where('permiso_grupo_grupo', $idSubmenu)
            ->update(['name' => "{$nuevaFuncion}.submenu"]);

        $this->limpiarCache();
    }

    /**
     * Al renombrar una opción, conserva el sufijo de cada permiso de acción
     * y solo reemplaza el prefijo (opciones_funcion).
     */
    public function renombrarOpcion(int $idOpcion, string $nuevaFuncion): void
    {
        DB::table('permissions')
            ->where('permiso_grupo', 3)
            ->where('permiso_grupo_grupo', $idOpcion)
            ->update(['name' => "{$nuevaFuncion}.opcion"]);

        $acciones = DB::table('permissions')
            ->where('permiso_grupo', 4)
            ->where('permiso_grupo_grupo', $idOpcion)
            ->get(['id', 'name']);

        foreach ($acciones as $accion) {
            $sufijo = substr(strrchr($accion->name, '.'), 1);
            if (!in_array($sufijo, self::ACCIONES)) {
                continue;
            }
            DB::table('permissions')
                ->where('id', $accion->id)
                ->update(['name' => "{$nuevaFuncion}.{$sufijo}"]);
        }

        $this->limpiarCache();
    }

    // ── Internos ───────────────────────────────────────────────

    private function limpiarCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
