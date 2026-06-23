# Flujo de validación de empresa y sucursal por rol

Este documento describe cómo el sistema restringe el acceso a datos según la empresa y sucursal del usuario autenticado. Este patrón se aplica en **todos los módulos**: ventas, logística, reportes, facturación y configuración.

---

## Tablas involucradas

```
users ──< user_sucursal >── sucursals >── empresa
```

| Tabla | Columnas clave | Descripción |
|---|---|---|
| `users` | `id_users` | Usuario autenticado |
| `user_sucursal` | `id_users`, `id_sucursal` | Asigna un usuario a una o más sucursales |
| `sucursals` | `id_sucursal`, `id_empresa` | Sucursal pertenece a una empresa |
| `empresa` | `id_empresa`, `empresa_estado` | Empresa del sistema |
| `model_has_roles` | `model_id`, `role_id` | Rol del usuario (Spatie) |

Un usuario puede estar asignado a **más de una sucursal** (distintas empresas o misma empresa).

---

## Roles y su nivel de acceso

| Role ID | Nombre | Restricción |
|---|---|---|
| 1 | superadmin | Sin restricción — ve todas las empresas |
| 2 | Administrador | Ve solo datos de su empresa |
| 3 | Vendedor | Ve solo datos de su sucursal activa (sesión) |
| 4 | Contador | Ve solo datos de su empresa (igual que admin) |

---

## Flujo en el login

El login (`app/Livewire/Auth/Login.php`) aplica las siguientes reglas antes de permitir el acceso:

### Superadmin (role_id = 1)

```
1. Verificar credenciales
2. Verificar users_estado = 1
3. Redirigir a /admin  ← sin validar empresa ni plan
```

### Resto de roles

```
1. Verificar credenciales
2. Verificar users_estado = 1
3. Buscar empresa activa via user_sucursal → sucursals → empresa
   ├── Sin empresa asignada  → error: "No tienes empresa asignada"
   └── Empresa encontrada ──> continúa
4. Verificar que empresa tenga plan activo en empresa_planes
   └── Sin plan → error: "Sin plan activo"
5. Verificar que la fecha_fin del plan sea >= hoy
   └── Plan vencido → error: "Plan vencido"
6. Para Vendedor/Contador: guardar sucursal activa en sesión
   session(['sucursal_activa_id' => id_sucursal])
7. Redirigir al dashboard según rol
```

---

## Patrón estándar en componentes Livewire

Todos los componentes que muestran datos de negocio siguen este patrón en `boot()` o `mount()`:

### 1. Obtener y cachear el rol

```php
$this->cachedRoleId = (int) DB::table('model_has_roles')
    ->where('model_id', auth()->user()->id_users)
    ->value('role_id');
```

### 2. Helpers de rol

```php
private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
private function esAdmin(): bool      { return $this->cachedRoleId === 2; }
```

### 3. Resolver ID de empresa

```php
private function resolverIdEmpresa(): ?int
{
    // Superadmin: usa dropdown de selección en la vista
    if ($this->esSuperAdmin()) {
        return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : null;
    }

    // Admin/Contador: empresa fija obtenida desde user_sucursal
    if ($this->esAdmin()) {
        return DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->orderBy('us.id_sucursal')
            ->value('s.id_empresa');
    }

    // Vendedor: empresa deducida de la sucursal activa en sesión
    $idSucursal = (int) session('sucursal_activa_id', 0);
    if (!$idSucursal) return null;
    return DB::table('sucursals')
        ->where('id_sucursal', $idSucursal)
        ->value('id_empresa');
}
```

### 4. Resolver ID de sucursal

```php
private function resolverIdSucursal(): ?int
{
    // Superadmin: puede filtrar por sucursal desde dropdown
    if ($this->esSuperAdmin()) {
        return $this->sucursalSeleccionada > 0 ? $this->sucursalSeleccionada : null;
    }

    // Vendedor: sucursal activa de la sesión
    if (!$this->esAdmin()) {
        return (int) session('sucursal_activa_id', 0) ?: null;
    }

    // Admin: si seleccionó una sucursal concreta, usar esa
    return $this->sucursalSeleccionada > 0 ? $this->sucursalSeleccionada : null;
}
```

### 5. Aplicar filtro en queries

```php
private function aplicarFiltroUbicacion(Builder $query): void
{
    $idEmpresa  = $this->resolverIdEmpresa();
    $idSucursal = $this->resolverIdSucursal();

    if ($idSucursal > 0) {
        $query->where('v.id_sucursal', $idSucursal);
    } elseif ($idEmpresa) {
        $query->where('v.id_empresa', $idEmpresa);
    }
    // Si es superadmin sin filtro seleccionado → devuelve todos los datos
}
```

---

## Comportamiento por rol en cada módulo

### Superadmin

- Ve un **dropdown de empresas** en la vista para filtrar.
- Sin selección → ve datos de **todas las empresas**.
- Puede operar en cualquier empresa/sucursal.
- En el módulo de Usuarios: ve todos los usuarios del sistema.

### Administrador

- La empresa se obtiene automáticamente desde `user_sucursal`.
- **No puede cambiar de empresa** — no ve el dropdown.
- Puede **filtrar por sucursal** dentro de su empresa.
- En el módulo de Usuarios: solo ve usuarios de su empresa.

### Vendedor

- La sucursal activa se guarda en `session('sucursal_activa_id')` al iniciar sesión.
- Si tiene **múltiples sucursales asignadas**, selecciona desde un modal en el dashboard.
- Solo ve datos de su sucursal activa.
- Para cambiar de sucursal: ir al dashboard y seleccionar otra (se actualiza la sesión).

### Contador

- Mismo comportamiento que Admin respecto a empresa.
- Acceso restringido a los módulos de reportes y facturación.

---

## Gestión de usuarios multi-sucursal (Vendedor)

Si un vendedor está asignado a más de una sucursal:

```
Login exitoso
    └── DashboardVendedor.mount()
        ├── Obtener sucursales del usuario via user_sucursal
        ├── Si tiene 1 sucursal → session('sucursal_activa_id', id)
        └── Si tiene varias  → mostrar modal de selección
                              → al confirmar: session('sucursal_activa_id', id_elegido)
```

La sesión `sucursal_activa_id` persiste durante toda la sesión. Para cambiar de sucursal el usuario debe volver al dashboard.

---

## Middleware activo

| Middleware | Efecto |
|---|---|
| `auth` | Requiere sesión activa |
| `VerifyUserStatus` | Verifica `users_estado = 1`; si no, cierra sesión y redirige al login |
| Spatie `role` / `permission` | Restringe rutas y opciones del menú según rol y permisos asignados |

---

## Dónde se implementa

El patrón se replica en los siguientes componentes:

- `app/Livewire/Reporte/*.php` — todos los reportes
- `app/Livewire/Facturacion/*.php` — pendientes, resúmenes, historial SUNAT
- `app/Livewire/Ventas/RealizarVenta.php` — ventana de venta
- `app/Livewire/Configuracion/Usuarios.php` — gestión de usuarios
- `app/Http/Controllers/GestionventasController.php`
- `app/Http/Controllers/FacturacionController.php`
- `app/Http/Controllers/ReporteController.php`

---

## Checklist al crear un nuevo módulo

Al crear un nuevo componente Livewire o controlador que devuelva datos de negocio:

- [ ] Cachear `role_id` en `boot()` o `mount()`
- [ ] Implementar `resolverIdEmpresa()` y `resolverIdSucursal()`
- [ ] Añadir `aplicarFiltroUbicacion()` a toda query que devuelva datos de ventas, inventario o clientes
- [ ] Para superadmin: incluir dropdown de empresa/sucursal en la vista
- [ ] Para admin: mostrar nombre de empresa fijo (sin dropdown)
- [ ] Para vendedor: mostrar nombre de sucursal activa (desde sesión)
