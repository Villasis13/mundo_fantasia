<?php

namespace App\Livewire\Configuracion;

use App\Models\Logs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Grupos extends Component
{
    use WithPagination;
    use WithFileUploads;

    private $logs;

    public function boot()
    {
        $this->logs = new Logs();
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('opcion_gestion_grupos.listar'), 403);
    }

    // ══════════════════════════════════════════════════════════
    //  PROPIEDADES — Formulario
    // ══════════════════════════════════════════════════════════
    public $grupoNombre    = '';
    public $grupoEstado    = '1';

    // ── Control modal ─────────────────────────────────────────
    public $modoEdicion      = false;
    public $idGrupoEditar    = null;
    public $idGrupoEliminar  = null;

    // ── Búsqueda y paginación ─────────────────────────────────
    public $buscar         = '';
    public $porPagina      = 10;
    public $ordenColumna   = 'id_grupo';
    public $ordenDireccion = 'desc';

    // ══════════════════════════════════════════════════════════
    //  VALIDACIONES
    // ══════════════════════════════════════════════════════════
    protected function rules(): array
    {
        return [
            'grupoNombre' => 'required|string|max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'grupoNombre.required' => 'El nombre del grupo es obligatorio.',
            'grupoNombre.max'      => 'El nombre no puede superar 255 caracteres.',
        ];
    }

    // ══════════════════════════════════════════════════════════
    //  RENDER
    // ══════════════════════════════════════════════════════════
    public function render()
    {
        $columnasPermitidas = ['id_grupo', 'grupo_nombre'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'id_grupo';
        $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

        $grupos = DB::table('grupos')
            ->where('grupo_estado', '!=', '0')
            ->where('grupo_nombre', 'like', "%{$this->buscar}%")
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        return view('livewire.configuracion.grupos', compact('grupos'));
    }

    // ══════════════════════════════════════════════════════════
    //  CRUD
    // ══════════════════════════════════════════════════════════
    public function ordenar($columna)
    {
        if ($this->ordenColumna === $columna) {
            $this->ordenDireccion = $this->ordenDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenColumna   = $columna;
            $this->ordenDireccion = 'asc';
        }
        $this->resetPage();
    }

    public function abrirModalNuevo()
    {
        $this->limpiarFormulario();
        $this->modoEdicion = false;
        $this->dispatch('abrirModal');
    }

    public function abrirModalEditar($idGrupo)
    {
        $this->limpiarFormulario();

        $grupo = DB::table('grupos')->where('id_grupo', $idGrupo)->first();
        if (!$grupo) {
            session()->flash('error', 'Grupo no encontrado.');
            return;
        }

        $this->idGrupoEditar = $grupo->id_grupo;
        $this->grupoNombre   = $grupo->grupo_nombre;
        $this->grupoEstado   = $grupo->grupo_estado;
        $this->modoEdicion   = true;
        $this->dispatch('abrirModal');
    }

    public function confirmarEliminar($idGrupo)
    {
        $this->idGrupoEliminar = $idGrupo;
        $this->dispatch('abrirModalEliminar');
    }

    public function eliminar()
    {
        try {
            if (!auth()->user()->can('opcion_gestion_grupos.cambiar_estado')) {
                $this->dispatch('cerrarModalEliminar');
                session()->flash('error', 'No tienes permiso para eliminar grupos.');
                return;
            }

            DB::table('grupos')
                ->where('id_grupo', $this->idGrupoEliminar)
                ->update(['grupo_estado' => 0, 'updated_at' => now()]);

            $this->idGrupoEliminar = null;
            $this->dispatch('cerrarModalEliminar');
            session()->flash('success', 'Grupo eliminado correctamente.');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al eliminar el grupo.');
        }
    }

    public function guardar()
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'opcion_gestion_grupos.actualizar' : 'opcion_gestion_grupos.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        try {
            $datos = [
                'grupo_nombre'     => $this->grupoNombre,
                'grupo_estado'     => 1,
                'updated_at'       => now(),
            ];

            if ($this->modoEdicion) {
                DB::table('grupos')->where('id_grupo', $this->idGrupoEditar)->update($datos);
            } else {
                $datos['id_users']        = Auth::id();
                $datos['grupo_microtime'] = microtime(true);
                $datos['created_at']      = now();
                DB::table('grupos')->insert($datos);
            }

            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $this->modoEdicion
                ? 'Grupo actualizado correctamente.'
                : 'Grupo registrado correctamente.'
            );

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar el grupo.');
        }
    }

    public function limpiarFormulario()
    {
        $this->reset(['grupoNombre', 'grupoEstado', 'idGrupoEditar', 'modoEdicion']);
        $this->grupoEstado = '1';
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatingBuscar()    { $this->resetPage(); }
    public function updatingPorPagina() { $this->resetPage(); }
}
