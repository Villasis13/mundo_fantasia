<?php

namespace App\Livewire\Configuracion;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Roles extends Component
{
    use WithPagination;

    // ── Propiedades del formulario ─────────────────────────────
    public string $rolNombre      = '';
    public string $rolDescripcion = '';

    // ── Control modal CRUD ─────────────────────────────────────
    public bool $modoEdicion = false;
    public ?int $idEditar    = null;

    // ── Modal cambio de estado ─────────────────────────────────
    public ?int $idCambiarEstado = null;
    public ?int $nuevoEstado     = null;

    // ── Modal permisos ─────────────────────────────────────────
    public ?int   $idRolPermisos    = null;
    public string $nombreRolPermisos = '';
    public array  $permisosArbol    = [];
    public array  $permisosSeleccionados = [];

    // ── Búsqueda y paginación ──────────────────────────────────
    public string $buscar         = '';
    public int    $porPagina      = 10;
    public string $ordenColumna   = 'id';
    public string $ordenDireccion = 'asc';

    private ?Logs $logs = null;

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('gestion_roles.listar'), 403);
    }

    // ── Validación ─────────────────────────────────────────────
    protected function rules(): array
    {
        $except = $this->idEditar ? ",{$this->idEditar}" : '';
        return [
            'rolNombre'      => "required|string|max:100|unique:roles,name{$except}",
            'rolDescripcion' => 'nullable|string|max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'rolNombre.required' => 'El nombre del rol es obligatorio.',
            'rolNombre.unique'   => 'Este nombre de rol ya está registrado.',
        ];
    }

    // ── Render ──────────────────────────────────────────────────
    public function render()
    {
        $roles = DB::table('roles')
            ->when($this->buscar, fn($q) => $q->where('name', 'like', "%{$this->buscar}%")
                                              ->orWhere('rol_descripcion', 'like', "%{$this->buscar}%"))
            ->orderBy($this->ordenColumna, $this->ordenDireccion)
            ->paginate($this->porPagina);

        $roles->getCollection()->transform(function ($rol) {
            $rol->total_permisos = DB::table('role_has_permissions')->where('role_id', $rol->id)->count();
            return $rol;
        });

        return view('livewire.configuracion.roles', compact('roles'));
    }

    // ── Ordenar ────────────────────────────────────────────────
    public function ordenar(string $columna): void
    {
        if ($this->ordenColumna === $columna) {
            $this->ordenDireccion = $this->ordenDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenColumna   = $columna;
            $this->ordenDireccion = 'asc';
        }
        $this->resetPage();
    }

    // ── Abrir modal nuevo ──────────────────────────────────────
    public function abrirModalNuevo(): void
    {
        $this->limpiarFormulario();
        $this->modoEdicion = false;
        $this->dispatch('abrirModal');
    }

    // ── Abrir modal editar ─────────────────────────────────────
    public function abrirModalEditar(int $id): void
    {
        $this->limpiarFormulario();
        $rol = DB::table('roles')->where('id', $id)->first();

        if (!$rol) {
            session()->flash('error', 'Rol no encontrado.');
            return;
        }

        $this->idEditar       = $rol->id;
        $this->rolNombre      = $rol->name;
        $this->rolDescripcion = $rol->rol_descripcion ?? '';
        $this->modoEdicion    = true;

        $this->dispatch('abrirModal');
    }

    // ── Guardar ────────────────────────────────────────────────
    public function guardar(): void
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'gestion_roles.actualizar' : 'gestion_roles.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        try {
            if ($this->modoEdicion) {
                DB::table('roles')->where('id', $this->idEditar)->update([
                    'name'            => $this->rolNombre,
                    'rol_descripcion' => $this->rolDescripcion ?: null,
                ]);
                $mensaje = 'Rol actualizado correctamente.';
            } else {
                DB::table('roles')->insert([
                    'name'            => $this->rolNombre,
                    'rol_descripcion' => $this->rolDescripcion ?: null,
                    'guard_name'      => 'web',
                    'rol_estado'      => 1,
                ]);
                $mensaje = 'Rol creado correctamente.';
            }

            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $mensaje);

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar el rol.');
        }
    }

    // ── Modal permisos ─────────────────────────────────────────
    public function abrirModalPermisos(int $idRol): void
    {
        $rol = DB::table('roles')->where('id', $idRol)->first();
        if (!$rol) return;

        $this->idRolPermisos     = $idRol;
        $this->nombreRolPermisos = $rol->name;

        // Permisos que ya tiene el rol
        $this->permisosSeleccionados = DB::table('role_has_permissions')
            ->where('role_id', $idRol)
            ->pluck('permission_id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        // Árbol completo de permisos
        $this->permisosArbol = $this->construirArbolPermisos();

        $this->dispatch('abrirModalPermisos');
    }

    private function construirArbolPermisos(): array
    {
        $menus = DB::table('permissions as p')
            ->join('menus', 'menus.id_menu', '=', 'p.permiso_grupo_grupo')
            ->where('p.permiso_estado', 1)
            ->where('p.permiso_grupo', 1)
            ->select('p.id', 'p.name', 'menus.id_menu')
            ->get();

        foreach ($menus as $menu) {
            $menu->sub = DB::table('permissions')
                ->join('submenu as s', 's.id_submenu', '=', 'permissions.permiso_grupo_grupo')
                ->where('s.id_menu', $menu->id_menu)
                ->where('permissions.permiso_estado', 1)
                ->where('permissions.permiso_grupo', 2)
                ->select('permissions.id', 'permissions.name', 's.id_submenu')
                ->get();

            foreach ($menu->sub as $sub) {
                $sub->opciones = DB::table('permissions')
                    ->join('opciones as o', 'o.id_opciones', '=', 'permissions.permiso_grupo_grupo')
                    ->where('o.id_submenu', $sub->id_submenu)
                    ->where('permissions.permiso_estado', 1)
                    ->where('permissions.permiso_grupo', 3)
                    ->select('permissions.id', 'permissions.name', 'o.id_opciones')
                    ->get();

                foreach ($sub->opciones as $op) {
                    $op->acciones = DB::table('permissions')
                        ->where('permiso_grupo_grupo', $op->id_opciones)
                        ->where('permiso_estado', 1)
                        ->where('permiso_grupo', 4)
                        ->select('id', 'name')
                        ->get();
                }
            }
        }

        return json_decode(json_encode($menus), true);
    }

    public function guardarPermisos(): void
    {
        if (!auth()->user()->can('gestion_roles.crear')) {
            $this->dispatch('cerrarModalPermisos');
            session()->flash('error', 'No tienes permiso para asignar permisos a roles.');
            return;
        }

        try {
            $rol = Role::find($this->idRolPermisos);
            if (!$rol) {
                session()->flash('error', 'Rol no encontrado.');
                return;
            }

            $ids = array_map('intval', $this->permisosSeleccionados);

            // Filtramos solo IDs que realmente existen en permissions para guard 'web'
            $idsValidos = DB::table('permissions')
                ->whereIn('id', $ids)
                ->where('guard_name', 'web')
                ->pluck('id')
                ->toArray();

            $rol->syncPermissions($idsValidos);

            $this->idRolPermisos         = null;
            $this->nombreRolPermisos     = '';
            $this->permisosArbol         = [];
            $this->permisosSeleccionados = [];
            $this->dispatch('cerrarModalPermisos');
            session()->flash('success', 'Permisos actualizados correctamente.');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al guardar los permisos.');
        }
    }

    // ── Confirmar cambio de estado ─────────────────────────────
    public function confirmarCambiarEstado(int $id, int $estado): void
    {
        $this->idCambiarEstado = $id;
        $this->nuevoEstado     = $estado;
        $this->dispatch('abrirModalEstado');
    }

    public function cambiarEstado(): void
    {
        if (!auth()->user()->can('gestion_roles.cambiar_estado')) {
            $this->dispatch('cerrarModalEstado');
            session()->flash('error', 'No tienes permiso para cambiar el estado.');
            return;
        }

        try {
            DB::table('roles')
                ->where('id', $this->idCambiarEstado)
                ->update(['rol_estado' => $this->nuevoEstado]);

            $mensaje = $this->nuevoEstado === 1 ? 'Rol habilitado.' : 'Rol deshabilitado.';

            $this->idCambiarEstado = null;
            $this->nuevoEstado     = null;
            $this->dispatch('cerrarModalEstado');
            session()->flash('success', $mensaje);

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al cambiar el estado.');
        }
    }

    // ── Limpiar formulario ─────────────────────────────────────
    public function limpiarFormulario(): void
    {
        $this->reset(['rolNombre', 'rolDescripcion', 'idEditar', 'modoEdicion']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatingBuscar(): void    { $this->resetPage(); }
    public function updatingPorPagina(): void { $this->resetPage(); }
}
