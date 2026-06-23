<?php

namespace App\Livewire\Configuracion;

use App\Models\Logs;
use App\Service\PermisoService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Opciones extends Component
{
    use WithPagination;

    // ── Contexto padre ─────────────────────────────────────────
    public int    $idSubmenu     = 0;
    public int    $idMenu        = 0;
    public string $nombreSubmenu = '';
    public string $nombreMenu    = '';

    // ── Propiedades del formulario ─────────────────────────────
    public string $opcionNombre  = '';
    public string $opcionFuncion = '';
    public string $opcionOrden   = '';
    public bool   $opcionMostrar = false;

    // ── Control modal CRUD ─────────────────────────────────────
    public bool $modoEdicion = false;
    public ?int $idEditar    = null;

    // ── Modal cambio de estado ─────────────────────────────────
    public ?int $idCambiarEstado = null;
    public ?int $nuevoEstado     = null;

    // ── Modal permisos de acción ───────────────────────────────
    public ?int   $idOpcionPermisos     = null;
    public string $nombreOpcionPermisos = '';
    public array  $permisosAccion       = [];
    public string $nuevoPermisoNombre   = '';

    // ── Búsqueda y paginación ──────────────────────────────────
    public string $buscar         = '';
    public int    $porPagina      = 10;
    public string $ordenColumna   = 'opciones_orden';
    public string $ordenDireccion = 'asc';

    private ?Logs $logs = null;

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    public function mount(int $idSubmenu): void
    {
        abort_if(!auth()->user()->can('gestion_opciones.listar'), 403);

        $this->idSubmenu = $idSubmenu;

        $sub = DB::table('submenu')->where('id_submenu', $idSubmenu)->first();
        $this->nombreSubmenu = $sub->submenu_nombre ?? '';

        if ($sub) {
            $this->idMenu     = $sub->id_menu ?? 0;
            $this->nombreMenu = DB::table('menus')
                ->where('id_menu', $sub->id_menu)
                ->value('menu_nombre') ?? '';
        }
    }

    // ── Validación ─────────────────────────────────────────────
    protected function rules(): array
    {
        $exceptId = $this->idEditar ? ",{$this->idEditar},id_opciones" : '';

        return [
            'opcionNombre'  => 'required|string|max:100',
            'opcionFuncion' => "required|string|max:100|unique:opciones,opciones_funcion{$exceptId}",
            'opcionOrden'   => 'required|integer|min:1|max:999',
            'opcionMostrar' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'opcionNombre.required'  => 'El nombre de la opción es obligatorio.',
            'opcionFuncion.required' => 'La función es obligatoria.',
            'opcionFuncion.unique'   => 'Esta función ya está registrada.',
            'opcionOrden.required'   => 'El orden es obligatorio.',
            'opcionOrden.integer'    => 'El orden debe ser un número entero.',
        ];
    }

    // ── Render ──────────────────────────────────────────────────
    public function render()
    {
        $columnasPermitidas = ['id_opciones', 'opciones_nombre', 'opciones_funcion', 'opciones_orden'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'opciones_orden';
        $direccion = $this->ordenDireccion === 'desc' ? 'desc' : 'asc';

        $opciones = DB::table('opciones')
            ->where('id_submenu', $this->idSubmenu)
            ->when($this->buscar, function ($q) {
                $q->where('opciones_nombre',  'like', "%{$this->buscar}%")
                  ->orWhere('opciones_funcion','like', "%{$this->buscar}%");
            })
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        return view('livewire.configuracion.opciones', compact('opciones'));
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

        $op = DB::table('opciones')->where('id_opciones', $id)->first();
        if (!$op) {
            session()->flash('error', 'Opción no encontrada.');
            return;
        }

        $this->idEditar      = $op->id_opciones;
        $this->opcionNombre  = $op->opciones_nombre;
        $this->opcionFuncion = $op->opciones_funcion;
        $this->opcionOrden   = (string) $op->opciones_orden;
        $this->opcionMostrar = (bool)   $op->opciones_mostrar;
        $this->modoEdicion   = true;

        $this->dispatch('abrirModal');
    }

    // ── Guardar ────────────────────────────────────────────────
    public function guardar(): void
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'gestion_opciones.actualizar' : 'gestion_opciones.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        DB::beginTransaction();
        try {
            if ($this->modoEdicion) {
                DB::table('opciones')
                    ->where('id_opciones', $this->idEditar)
                    ->update([
                        'opciones_nombre'  => $this->opcionNombre,
                        'opciones_funcion' => $this->opcionFuncion,
                        'opciones_mostrar' => $this->opcionMostrar ? 1 : 0,
                        'opciones_orden'   => (int) $this->opcionOrden,
                        'updated_at'       => now(),
                    ]);

                (new PermisoService())->renombrarOpcion($this->idEditar, $this->opcionFuncion);
                $mensaje = 'Opción actualizada correctamente.';
            } else {
                $idOpcion = DB::table('opciones')->insertGetId([
                    'id_submenu'       => $this->idSubmenu,
                    'opciones_nombre'  => $this->opcionNombre,
                    'opciones_funcion' => $this->opcionFuncion,
                    'opciones_mostrar' => $this->opcionMostrar ? 1 : 0,
                    'opciones_orden'   => (int) $this->opcionOrden,
                    'opciones_estado'  => 1,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                (new PermisoService())->crearOpcion($idOpcion, $this->opcionFuncion);
                $mensaje = 'Opción creada correctamente.';
            }

            DB::commit();
            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar la opción.');
        }
    }

    // ── Modal permisos de acción ───────────────────────────────
    public function abrirModalPermisos(int $id): void
    {
        $op = DB::table('opciones')->where('id_opciones', $id)->first();
        if (!$op) return;

        $this->idOpcionPermisos     = $id;
        $this->nombreOpcionPermisos = $op->opciones_nombre;
        $this->cargarPermisosAccion();
        $this->dispatch('abrirModalPermisos');
    }

    private function cargarPermisosAccion(): void
    {
        $accionesPredefinidas = PermisoService::ACCIONES;

        $this->permisosAccion = DB::table('permissions')
            ->where('permiso_grupo', 4)
            ->where('permiso_grupo_grupo', $this->idOpcionPermisos)
            ->where('permiso_estado', 1)
            ->select('id', 'name')
            ->get()
            ->map(function ($p) use ($accionesPredefinidas) {
                $sufijo = ($pos = strrpos($p->name, '.')) !== false
                    ? substr($p->name, $pos + 1)
                    : $p->name;

                return [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'esPredefinido' => in_array($sufijo, $accionesPredefinidas),
                ];
            })
            ->toArray();
    }

    public function agregarPermiso(): void
    {
        if (!auth()->user()->can('gestion_opciones.crear')) {
            session()->flash('error', 'No tienes permiso para agregar permisos.');
            return;
        }

        $this->validate([
            'nuevoPermisoNombre' => 'required|string|max:150',
        ], [
            'nuevoPermisoNombre.required' => 'Ingresa el nombre del permiso.',
        ]);

        $nombre = trim($this->nuevoPermisoNombre);

        if (DB::table('permissions')->where('name', $nombre)->exists()) {
            $this->addError('nuevoPermisoNombre', 'Ese permiso ya existe.');
            return;
        }

        try {
            $permiso = \Spatie\Permission\Models\Permission::create([
                'name'                => $nombre,
                'guard_name'          => 'web',
                'id_menu'             => null,
                'id_submenu'          => null,
                'id_opciones'         => null,
                'permiso_grupo'       => 4,
                'permiso_grupo_grupo' => $this->idOpcionPermisos,
                'permiso_estado'      => 1,
            ]);
            $permiso->syncRoles('superadmin');

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            $this->nuevoPermisoNombre = '';
            $this->resetErrorBag('nuevoPermisoNombre');
            $this->cargarPermisosAccion();
            session()->flash('success', 'Permiso agregado correctamente.');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al agregar el permiso.');
        }
    }

    public function eliminarPermiso(int $idPermiso): void
    {
        if (!auth()->user()->can('gestion_opciones.eliminar')) {
            session()->flash('error', 'No tienes permiso para eliminar permisos.');
            return;
        }

        try {
            $permiso = \Spatie\Permission\Models\Permission::findById($idPermiso, 'web');
            $permiso->delete();

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            $this->cargarPermisosAccion();
            session()->flash('success', 'Permiso eliminado correctamente.');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al eliminar el permiso.');
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
        if (!auth()->user()->can('gestion_opciones.cambiar_estado')) {
            $this->dispatch('cerrarModalEstado');
            session()->flash('error', 'No tienes permiso para cambiar el estado.');
            return;
        }

        try {
            DB::table('opciones')
                ->where('id_opciones', $this->idCambiarEstado)
                ->update(['opciones_estado' => $this->nuevoEstado, 'updated_at' => now()]);

            $mensaje = $this->nuevoEstado === 1
                ? 'Opción habilitada correctamente.'
                : 'Opción deshabilitada correctamente.';

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
        $this->reset(['opcionNombre', 'opcionFuncion', 'opcionOrden', 'opcionMostrar', 'idEditar', 'modoEdicion']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatingBuscar(): void    { $this->resetPage(); }
    public function updatingPorPagina(): void { $this->resetPage(); }
}
