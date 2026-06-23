<?php

namespace App\Livewire\Configuracion;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Almacenes extends Component
{
    use WithPagination;

    private $logs;

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    // ── Contexto ──────────────────────────────────────────────
    public $idTienda      = null;
    public $nombreTienda  = null;
    public $idEmpresa     = null;
    public $nombreEmpresa = null;

    public function mount($idTienda = null, $nombreTienda = null, $idEmpresa = null, $nombreEmpresa = null): void
    {
        abort_if(!auth()->user()->can('opcion_gestion_tiendas.listar'), 403);
        $this->idTienda      = $idTienda;
        $this->nombreTienda  = $nombreTienda;
        $this->idEmpresa     = $idEmpresa;
        $this->nombreEmpresa = $nombreEmpresa;
    }

    // ══════════════════════════════════════════════════════════
    //  PROPIEDADES — Formulario
    // ══════════════════════════════════════════════════════════
    public string $almacenNombre = '';

    // ── Control modal ─────────────────────────────────────────
    public $modoEdicion        = false;
    public $idAlmacenEditar    = null;
    public $idAlmacenEliminar  = null;

    // ── Búsqueda y paginación ─────────────────────────────────
    public $buscar         = '';
    public $porPagina      = 10;
    public $ordenColumna   = 'id_tienda';
    public $ordenDireccion = 'desc';

    // ══════════════════════════════════════════════════════════
    //  VALIDACIONES
    // ══════════════════════════════════════════════════════════
    protected function rules(): array
    {
        return [
            'almacenNombre' => 'required|string|max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'almacenNombre.required' => 'El nombre del almacén es obligatorio.',
            'almacenNombre.max'      => 'El nombre no puede superar 255 caracteres.',
        ];
    }

    // ══════════════════════════════════════════════════════════
    //  RENDER
    // ══════════════════════════════════════════════════════════
    public function render()
    {
        $columnasPermitidas = ['id_tienda', 'tienda_nombre'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'id_tienda';
        $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

        $almacenes = DB::table('tiendas')
            ->where('tienda_estado', '!=', 0)
            ->where('tienda_tipo', 3)
            ->where('id_tienda_padre', $this->idTienda)
            ->where('tienda_nombre', 'like', "%{$this->buscar}%")
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        return view('livewire.configuracion.almacenes', compact('almacenes'));
    }

    // ══════════════════════════════════════════════════════════
    //  CRUD
    // ══════════════════════════════════════════════════════════
    public function ordenar($columna): void
    {
        if ($this->ordenColumna === $columna) {
            $this->ordenDireccion = $this->ordenDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenColumna   = $columna;
            $this->ordenDireccion = 'asc';
        }
        $this->resetPage();
    }

    public function abrirModalNuevo(): void
    {
        $this->limpiarFormulario();
        $this->modoEdicion = false;
        $this->dispatch('abrirModal');
    }

    public function abrirModalEditar($id): void
    {
        $this->limpiarFormulario();

        $almacen = DB::table('tiendas')->where('id_tienda', $id)->first();
        if (!$almacen) {
            session()->flash('error', 'Almacén no encontrado.');
            return;
        }

        $this->idAlmacenEditar = $almacen->id_tienda;
        $this->almacenNombre   = $almacen->tienda_nombre;
        $this->modoEdicion     = true;
        $this->dispatch('abrirModal');
    }

    public function confirmarEliminar($id): void
    {
        $this->idAlmacenEliminar = $id;
        $this->dispatch('abrirModalEliminar');
    }

    public function eliminar(): void
    {
        try {
            if (!auth()->user()->can('gestionar_opcion_tienda.cambiar_estado')) {
                $this->dispatch('cerrarModalEliminar');
                session()->flash('error', 'No tienes permiso para eliminar almacenes.');
                return;
            }

            DB::table('tiendas')
                ->where('id_tienda', $this->idAlmacenEliminar)
                ->update(['tienda_estado' => 0, 'updated_at' => now()]);

            $this->idAlmacenEliminar = null;
            $this->dispatch('cerrarModalEliminar');
            session()->flash('success', 'Almacén eliminado correctamente.');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al eliminar el almacén.');
        }
    }

    public function guardar(): void
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'gestionar_opcion_tienda.actualizar' : 'gestionar_opcion_tienda.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        try {
            $datos = [
                'tienda_nombre' => $this->almacenNombre,
                'tienda_tipo'   => 3,
                'tienda_estado' => 1,
                'updated_at'    => now(),
            ];

            if ($this->modoEdicion) {
                DB::table('tiendas')->where('id_tienda', $this->idAlmacenEditar)->update($datos);
            } else {
                $datos['id_empresa']       = $this->idEmpresa;
                $datos['id_tienda_padre']  = $this->idTienda;
                $datos['tienda_principal'] = 0;
                $datos['tienda_microtime'] = microtime(true);
                $datos['created_at']       = now();
                DB::table('tiendas')->insert($datos);
            }

            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $this->modoEdicion
                ? 'Almacén actualizado correctamente.'
                : 'Almacén registrado correctamente.'
            );

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar el almacén.');
        }
    }

    public function limpiarFormulario(): void
    {
        $this->reset(['almacenNombre', 'idAlmacenEditar', 'modoEdicion']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatingBuscar(): void    { $this->resetPage(); }
    public function updatingPorPagina(): void { $this->resetPage(); }
}
