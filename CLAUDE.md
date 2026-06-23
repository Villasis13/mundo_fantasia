# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Instalar dependencias
composer install

# Levantar base de datos desde cero
php artisan migrate
php artisan db:seed

# Regenerar key (primer setup)
php artisan key:generate

# Limpiar caché de permisos (Spatie) tras cambios en roles/permisos
php artisan cache:clear
php artisan permission:cache-reset

# Revertir y re-correr todas las migraciones
php artisan migrate:fresh --seed
```

No hay suite de tests configurada ni npm/Vite en uso. Bootstrap 5 y FontAwesome se cargan vía CDN en los layouts Blade.

---

## Arquitectura

Sistema ERP multi-empresa/multi-sucursal construido sobre Laravel 10 + Livewire 4. Cada módulo tiene un controlador delgado que solo renderiza vistas; toda la lógica interactiva vive en componentes Livewire.

### Flujo de una request normal

```
routes/web.php
  → Http/Controllers/{Modulo}Controller  (renderiza vista)
    → resources/views/{modulo}/index.blade.php
      → @livewire('modulo.componente')
        → app/Livewire/{Modulo}/{Componente}.php  (toda la lógica)
```

Los controladores solo hacen `return view(...)` con comprobaciones de permiso Spatie. No tienen lógica de negocio.

### PKs no estándar

Ninguna tabla usa `id` como clave primaria. Siempre especificar `$primaryKey`:

| Tabla | PK |
|---|---|
| `users` | `id_users` |
| `ventas` | `id_venta` |
| `productos` | `id_pro` |
| `persona` | `id_persona` |
| `empresa` | `id_empresa` |
| `sucursals` | `id_sucursal` |

---

## Sistema de permisos

Spatie Permission con columnas extra en `permissions`: `id_menu`, `id_submenu`, `id_opciones`, `permiso_grupo` (1=menu, 2=submenu, 3=opción, 4=acción), `permiso_grupo_grupo`.

### Naming de permisos

```
{controlador}.menu          → acceso al menú
{funcion}.submenu           → acceso al submenú
{funcion}.opcion            → acceso a la opción
{funcion}.listar            → acción
{funcion}.crear             → acción
{funcion}.actualizar        → acción
{funcion}.cambiar_estado    → acción
{funcion}.eliminar          → acción
{funcion}.aprobar           → acción
{funcion}.exportar          → acción
```

Las 7 acciones estándar están definidas en `PermisoService::ACCIONES`. Cuando se crea o renombra un menú/submenú/opción desde la UI, `app/Service/PermisoService.php` crea/renombra automáticamente todos los permisos en cascada y los asigna al superadmin. **No crear permisos manualmente en la BD.**

---

## Restricción empresa/sucursal por rol

**Todos los componentes que devuelven datos de negocio** deben implementar este patrón:

```php
// En boot() o mount()
$this->cachedRoleId = (int) DB::table('model_has_roles')
    ->where('model_id', auth()->user()->id_users)
    ->value('role_id');

private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
private function esAdmin(): bool      { return $this->cachedRoleId === 2; }

private function resolverIdEmpresa(): ?int
{
    if ($this->esSuperAdmin()) {
        return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : null;
    }
    if ($this->esAdmin()) {
        return DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->orderBy('us.id_sucursal')->value('s.id_empresa');
    }
    $id = (int) session('sucursal_activa_id', 0);
    return $id ? DB::table('sucursals')->where('id_sucursal', $id)->value('id_empresa') : null;
}

private function aplicarFiltroUbicacion(Builder $query): void
{
    $idEmpresa  = $this->resolverIdEmpresa();
    $idSucursal = $this->resolverIdSucursal();
    if ($idSucursal > 0) {
        $query->where('v.id_sucursal', $idSucursal);
    } elseif ($idEmpresa) {
        $query->where('v.id_empresa', $idEmpresa);
    }
}
```

- **superadmin (role 1)**: ve todas las empresas; filtra desde dropdown en vista.
- **admin (role 2)**: empresa fija desde `user_sucursal`; no ve dropdown de empresa.
- **vendedor (role 3)** y **contador (role 4)**: usan `session('sucursal_activa_id')` que se asigna en `DashboardVendedor.php` al iniciar sesión.

Ver `docs/flujo-empresa-sucursal.md` para el detalle completo.

---

## Seeders

Los seeders están en `database/seeders/`. Los datos grandes (ubigeo, permisos, role_has_permissions, model_has_roles) viven como JSON en `database/seeders/data/`.

Cuando se modifiquen roles, permisos o menús en la BD, re-exportar los JSON afectados con tinker antes de commitear:

```php
// Ejemplo: re-exportar permisos tras cambios
$data = DB::table('permissions')->get()->map(fn($r) => (array)$r)->toArray();
file_put_contents(database_path('seeders/data/permissions.json'), json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
```

Todos los seeders usan `SET FOREIGN_KEY_CHECKS=0/1` porque `TRUNCATE` falla con FK activas en MySQL.

---

## Variables de entorno clave

Además de las estándar de Laravel:

| Variable | Descripción |
|---|---|
| `APP_FACT` | URL del servicio SUNAT (beta o producción) |
| `APP_FACT_LOCAL` | `1` = entorno local, `2` = servidor |
| `APP_CACERT_FACT` | `1` = usar cacert local para cURL, `2` = no |
| `MAIL_BACKUP` | Correo de respaldo para notificaciones internas |
| `MIGO_TOKEN` | Token del servicio Migo (SMS/notificaciones) |
| `ANCHO_LOGO_PDF` / `ALTO_LOGO_PDF` | Dimensiones del logo en PDFs generados |
| `IZQU_LOGO_PDF_A4` / `IZQU_LOGO_PDF_TICKET` | Margen izquierdo del logo en PDF A4 y ticket |
