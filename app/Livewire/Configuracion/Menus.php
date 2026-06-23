<?php

namespace App\Livewire\Configuracion;

use App\Models\Logs;
use App\Service\PermisoService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Menus extends Component
{
    use WithPagination;

    // ── Propiedades del formulario ─────────────────────────────
    public string $menuNombre      = '';
    public string $menuControlador = '';
    public string $menuIcono       = '';
    public string $menuOrden       = '';
    public bool   $menuMostrar     = false;

    // ── Control modal ──────────────────────────────────────────
    public bool  $modoEdicion = false;
    public ?int  $idEditar    = null;

    // ── Modal cambio de estado ─────────────────────────────────
    public ?int $idCambiarEstado = null;
    public ?int $nuevoEstado     = null;

    // ── Búsqueda y paginación ──────────────────────────────────
    public string $buscar         = '';
    public int    $porPagina      = 10;
    public string $ordenColumna   = 'menu_orden';
    public string $ordenDireccion = 'asc';

    private ?Logs $logs = null;

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    // ── Validación ─────────────────────────────────────────────
    protected function rules(): array
    {
        $exceptId     = $this->idEditar ?? 'NULL';
        $uniqueRegla  = "unique:menus,menu_controlador,{$exceptId},id_menu";

        return [
            'menuNombre'      => 'required|string|max:100',
            'menuControlador' => "required|string|max:100|{$uniqueRegla}",
            'menuIcono'       => 'required|string|max:150',
            'menuOrden'       => 'required|integer|min:1|max:999',
            'menuMostrar'     => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'menuNombre.required'      => 'El nombre del menú es obligatorio.',
            'menuNombre.max'           => 'El nombre no puede superar 100 caracteres.',
            'menuControlador.required' => 'El controlador es obligatorio.',
            'menuControlador.unique'   => 'Este controlador ya está registrado.',
            'menuIcono.required'       => 'El icono es obligatorio.',
            'menuOrden.required'       => 'El orden es obligatorio.',
            'menuOrden.integer'        => 'El orden debe ser un número entero.',
            'menuOrden.min'            => 'El orden mínimo es 1.',
        ];
    }

    // ── Render ──────────────────────────────────────────────────
    public function mount(): void
    {
        abort_if(!auth()->user()->can('gestion_menus.listar'), 403);
    }

    public function render()
    {
        $columnasPermitidas = ['id_menu', 'menu_nombre', 'menu_controlador', 'menu_orden'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'menu_orden';
        $direccion = $this->ordenDireccion === 'desc' ? 'desc' : 'asc';

        $menus = DB::table('menus')
            ->when($this->buscar, function ($q) {
                $q->where('menu_nombre',      'like', "%{$this->buscar}%")
                  ->orWhere('menu_controlador','like', "%{$this->buscar}%");
            })
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        $menus->getCollection()->transform(function ($menu) {
            $menu->contar = DB::table('submenu')->where('id_menu', $menu->id_menu)->count();
            return $menu;
        });

        return view('livewire.configuracion.menus', compact('menus'));
    }

    // ── Ordenar columnas ───────────────────────────────────────
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
        $menu = DB::table('menus')->where('id_menu', $id)->first();

        if (!$menu) {
            session()->flash('error', 'Menú no encontrado.');
            return;
        }

        $this->idEditar        = $menu->id_menu;
        $this->menuNombre      = $menu->menu_nombre;
        $this->menuControlador = $menu->menu_controlador;
        $this->menuIcono       = $menu->menu_icono;
        $this->menuOrden       = (string) $menu->menu_orden;
        $this->menuMostrar     = (bool)   $menu->menu_mostrar;
        $this->modoEdicion     = true;

        $this->dispatch('abrirModal');
    }

    // ── Guardar (crear o editar) ───────────────────────────────
    public function guardar(): void
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'gestion_menus.actualizar' : 'gestion_menus.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        DB::beginTransaction();
        try {
            if ($this->modoEdicion) {
                DB::table('menus')
                    ->where('id_menu', $this->idEditar)
                    ->update([
                        'menu_nombre'      => $this->menuNombre,
                        'menu_controlador' => $this->menuControlador,
                        'menu_icono'       => $this->menuIcono,
                        'menu_orden'       => (int) $this->menuOrden,
                        'menu_mostrar'     => $this->menuMostrar ? 1 : 0,
                        'updated_at'       => now(),
                    ]);

                (new PermisoService())->renombrarMenu($this->idEditar, $this->menuControlador);
                $mensaje = 'Menú actualizado correctamente.';
            } else {
                $idMenu = DB::table('menus')->insertGetId([
                    'menu_nombre'      => $this->menuNombre,
                    'menu_controlador' => $this->menuControlador,
                    'menu_icono'       => $this->menuIcono,
                    'menu_orden'       => (int) $this->menuOrden,
                    'menu_mostrar'     => $this->menuMostrar ? 1 : 0,
                    'menu_estado'      => 1,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                (new PermisoService())->crearMenu($idMenu, $this->menuControlador);
                $mensaje = 'Menú creado correctamente.';
            }

            DB::commit();
            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar el menú.');
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
        if (!auth()->user()->can('gestion_menus.cambiar_estado')) {
            $this->dispatch('cerrarModalEstado');
            session()->flash('error', 'No tienes permiso para cambiar el estado.');
            return;
        }

        try {
            $menu = DB::table('menus')->where('id_menu', $this->idCambiarEstado)->first();

            if (!$menu) {
                session()->flash('error', 'Menú no encontrado.');
                $this->dispatch('cerrarModalEstado');
                return;
            }

            DB::table('menus')
                ->where('id_menu', $this->idCambiarEstado)
                ->update([
                    'menu_estado' => $this->nuevoEstado,
                    'updated_at'  => now(),
                ]);

            $mensaje = $this->nuevoEstado === 1
                ? 'Menú habilitado correctamente.'
                : 'Menú deshabilitado correctamente.';

            $this->idCambiarEstado = null;
            $this->nuevoEstado     = null;
            $this->dispatch('cerrarModalEstado');
            session()->flash('success', $mensaje);

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al cambiar el estado del menú.');
        }
    }

    // ── Limpiar formulario ─────────────────────────────────────
    public function limpiarFormulario(): void
    {
        $this->reset([
            'menuNombre', 'menuControlador', 'menuIcono',
            'menuOrden', 'menuMostrar', 'idEditar', 'modoEdicion',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    // ── Reset paginación al buscar/cambiar página ──────────────
    public function updatingBuscar(): void    { $this->resetPage(); }
    public function updatingPorPagina(): void { $this->resetPage(); }
}
