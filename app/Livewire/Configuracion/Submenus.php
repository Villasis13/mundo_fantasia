<?php

namespace App\Livewire\Configuracion;

use App\Models\Logs;
use App\Service\PermisoService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Submenus extends Component
{
    use WithPagination;

    // ── Contexto del menú padre ────────────────────────────────
    public int    $idMenu     = 0;
    public string $nombreMenu = '';

    // ── Propiedades del formulario ─────────────────────────────
    public string $subMenuNombre  = '';
    public string $subMenuFuncion = '';
    public string $subMenuOrden   = '';
    public bool   $subMenuMostrar = false;

    // ── Control modal ──────────────────────────────────────────
    public bool $modoEdicion = false;
    public ?int $idEditar    = null;

    // ── Modal cambio de estado ─────────────────────────────────
    public ?int $idCambiarEstado = null;
    public ?int $nuevoEstado     = null;

    // ── Búsqueda y paginación ──────────────────────────────────
    public string $buscar         = '';
    public int    $porPagina      = 10;
    public string $ordenColumna   = 'submenu_orden';
    public string $ordenDireccion = 'asc';

    private ?Logs $logs = null;

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    public function mount(int $idMenu): void
    {
        abort_if(!auth()->user()->can('gestion_submenus.listar'), 403);

        $this->idMenu     = $idMenu;
        $this->nombreMenu = DB::table('menus')
            ->where('id_menu', $idMenu)
            ->value('menu_nombre') ?? '';
    }

    // ── Validación ─────────────────────────────────────────────
    protected function rules(): array
    {
        $exceptId = $this->idEditar ? ",{$this->idEditar},id_submenu" : '';

        return [
            'subMenuNombre'  => 'required|string|max:100',
            'subMenuFuncion' => "required|string|max:100|unique:submenu,submenu_funcion{$exceptId}",
            'subMenuOrden'   => 'required|integer|min:1|max:999',
            'subMenuMostrar' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'subMenuNombre.required'  => 'El nombre del submenú es obligatorio.',
            'subMenuFuncion.required' => 'La función es obligatoria.',
            'subMenuFuncion.unique'   => 'Esta función ya está registrada en el sistema.',
            'subMenuOrden.required'   => 'El orden es obligatorio.',
            'subMenuOrden.integer'    => 'El orden debe ser un número entero.',
            'subMenuOrden.min'        => 'El orden mínimo es 1.',
        ];
    }

    // ── Render ──────────────────────────────────────────────────
    public function render()
    {
        $columnasPermitidas = ['id_submenu', 'submenu_nombre', 'submenu_funcion', 'submenu_orden'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'submenu_orden';
        $direccion = $this->ordenDireccion === 'desc' ? 'desc' : 'asc';

        $submenus = DB::table('submenu')
            ->where('id_menu', $this->idMenu)
            ->when($this->buscar, function ($q) {
                $q->where('submenu_nombre',  'like', "%{$this->buscar}%")
                  ->orWhere('submenu_funcion','like', "%{$this->buscar}%");
            })
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        $submenus->getCollection()->transform(function ($s) {
            $s->contar = DB::table('opciones')->where('id_submenu', $s->id_submenu)->count();
            return $s;
        });

        return view('livewire.configuracion.submenus', compact('submenus'));
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

        $sub = DB::table('submenu')->where('id_submenu', $id)->first();
        if (!$sub) {
            session()->flash('error', 'Submenú no encontrado.');
            return;
        }

        $this->idEditar       = $sub->id_submenu;
        $this->subMenuNombre  = $sub->submenu_nombre;
        $this->subMenuFuncion = $sub->submenu_funcion;
        $this->subMenuOrden   = (string) $sub->submenu_orden;
        $this->subMenuMostrar = (bool)   $sub->submenu_mostrar;
        $this->modoEdicion    = true;

        $this->dispatch('abrirModal');
    }

    // ── Guardar (crear o editar) ───────────────────────────────
    public function guardar(): void
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'gestion_submenus.actualizar' : 'gestion_submenus.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        DB::beginTransaction();
        try {
            if ($this->modoEdicion) {
                DB::table('submenu')
                    ->where('id_submenu', $this->idEditar)
                    ->update([
                        'submenu_nombre'  => $this->subMenuNombre,
                        'submenu_funcion' => $this->subMenuFuncion,
                        'submenu_mostrar' => $this->subMenuMostrar ? 1 : 0,
                        'submenu_orden'   => (int) $this->subMenuOrden,
                        'updated_at'      => now(),
                    ]);

                (new PermisoService())->renombrarSubmenu($this->idEditar, $this->subMenuFuncion);
                $mensaje = 'Submenú actualizado correctamente.';

            } else {
                $idSubMenu = DB::table('submenu')->insertGetId([
                    'id_menu'         => $this->idMenu,
                    'submenu_nombre'  => $this->subMenuNombre,
                    'submenu_funcion' => $this->subMenuFuncion,
                    'submenu_mostrar' => $this->subMenuMostrar ? 1 : 0,
                    'submenu_orden'   => (int) $this->subMenuOrden,
                    'submenu_estado'  => 1,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                (new PermisoService())->crearSubmenu($idSubMenu, $this->subMenuFuncion);
                $mensaje = 'Submenú creado correctamente.';
            }

            DB::commit();
            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar el submenú.');
        }
    }

    // ── Confirmar cambio de estado ─────────────────────────────
    public function confirmarCambiarEstado(int $id, int $estado): void
    {
        $this->idCambiarEstado = $id;
        $this->nuevoEstado     = $estado;
        $this->dispatch('abrirModalEstado');
    }

    // ── Aplicar cambio de estado ───────────────────────────────
    public function cambiarEstado(): void
    {
        if (!auth()->user()->can('gestion_submenus.cambiar_estado')) {
            $this->dispatch('cerrarModalEstado');
            session()->flash('error', 'No tienes permiso para cambiar el estado.');
            return;
        }

        try {
            DB::table('submenu')
                ->where('id_submenu', $this->idCambiarEstado)
                ->update(['submenu_estado' => $this->nuevoEstado, 'updated_at' => now()]);

            $mensaje = $this->nuevoEstado === 1
                ? 'Submenú habilitado correctamente.'
                : 'Submenú deshabilitado correctamente.';

            $this->idCambiarEstado = null;
            $this->nuevoEstado     = null;
            $this->dispatch('cerrarModalEstado');
            session()->flash('success', $mensaje);

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al cambiar el estado.');
        }
    }

    // ── Limpiar formulario ─────────────────────────────────────
    public function limpiarFormulario(): void
    {
        $this->reset([
            'subMenuNombre', 'subMenuFuncion', 'subMenuOrden',
            'subMenuMostrar', 'idEditar', 'modoEdicion',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatingBuscar(): void    { $this->resetPage(); }
    public function updatingPorPagina(): void { $this->resetPage(); }
}
