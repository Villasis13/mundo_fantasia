<?php

namespace App\Livewire\Configuracion;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Planes extends Component
{
    use WithPagination;

    private $logs;

    public function boot()
    {
        $this->logs = new Logs();
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('gestion_planes.listar'), 403);
    }

    // ── Propiedades del formulario ────────────────────────────
    public $planNombre        = '';
    public $planDescripcion   = '';
    public $planPrecio        = '';
    public $planDuracionDias  = '';
    public $planEstado        = 1;

    // ── Control modal ─────────────────────────────────────────
    public $modoEdicion   = false;
    public $idPlanEditar  = null;
    public $idPlanEliminar = null;

    // ── Búsqueda y paginación ─────────────────────────────────
    public $buscar         = '';
    public $porPagina      = 10;
    public $ordenColumna   = 'id_plan';
    public $ordenDireccion = 'desc';

    // ── Validaciones ─────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'planNombre'       => 'required|string|max:100',
            'planDescripcion'  => 'nullable|string|max:255',
            'planPrecio'       => 'required|numeric|min:0',
            'planDuracionDias' => 'required|integer|min:1',
            'planEstado'       => 'required|in:0,1',
        ];
    }

    protected function messages(): array
    {
        return [
            'planNombre.required'       => 'El nombre del plan es obligatorio.',
            'planPrecio.required'       => 'El precio es obligatorio.',
            'planPrecio.numeric'        => 'El precio debe ser un número válido.',
            'planPrecio.min'            => 'El precio no puede ser negativo.',
            'planDuracionDias.required' => 'La duración en días es obligatoria.',
            'planDuracionDias.integer'  => 'La duración debe ser un número entero.',
            'planDuracionDias.min'      => 'La duración debe ser al menos 1 día.',
        ];
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        $columnasPermitidas = ['id_plan', 'plan_nombre', 'plan_precio', 'plan_duracion_dias'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'id_plan';
        $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

        $planes = DB::table('planes')
            ->where('plan_estado', '!=', 0)
            ->where(function ($q) {
                $q->where('plan_nombre',      'like', "%{$this->buscar}%")
                    ->orWhere('plan_descripcion', 'like', "%{$this->buscar}%");
            })
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        return view('livewire.configuracion.planes', compact('planes'));
    }

    // ── Ordenar ───────────────────────────────────────────────
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

    // ── Abrir modal nuevo ─────────────────────────────────────
    public function abrirModalNuevo()
    {
        $this->limpiarFormulario();
        $this->modoEdicion = false;
        $this->dispatch('abrirModal');
    }

    // ── Abrir modal editar ────────────────────────────────────
    public function abrirModalEditar($idPlan)
    {
        $this->limpiarFormulario();

        $plan = DB::table('planes')->where('id_plan', $idPlan)->first();
        if (!$plan) {
            session()->flash('error', 'Plan no encontrado.');
            return;
        }

        $this->idPlanEditar     = $plan->id_plan;
        $this->planNombre       = $plan->plan_nombre;
        $this->planDescripcion  = $plan->plan_descripcion ?? '';
        $this->planPrecio       = $plan->plan_precio;
        $this->planDuracionDias = $plan->plan_duracion_dias;
        $this->planEstado       = $plan->plan_estado;

        $this->modoEdicion = true;
        $this->dispatch('abrirModal');
    }

    // ── Confirmar eliminar ────────────────────────────────────
    public function confirmarEliminar($idPlan)
    {
        $this->idPlanEliminar = $idPlan;
        $this->dispatch('abrirModalEliminar');
    }

    // ── Eliminar (lógico) ─────────────────────────────────────
    public function eliminar()
    {
        try {
            if (!auth()->user()->can('gestion_planes.cambiar_estado')) {
                $this->dispatch('cerrarModalEliminar');
                session()->flash('error', 'No tienes permiso para desactivar planes.');
                return;
            }

            // Verificar que no tenga empresas activas vinculadas
            $tieneVinculadas = DB::table('empresa_planes')
                ->where('id_plan', $this->idPlanEliminar)
                ->where('estado', 1)
                ->exists();

            if ($tieneVinculadas) {
                $this->dispatch('cerrarModalEliminar');
                session()->flash('error', 'No se puede eliminar el plan porque tiene empresas activas vinculadas.');
                return;
            }

            DB::table('planes')
                ->where('id_plan', $this->idPlanEliminar)
                ->update(['plan_estado' => 0, 'updated_at' => now()]);

            $this->idPlanEliminar = null;
            $this->dispatch('cerrarModalEliminar');
            session()->flash('success', 'Plan eliminado correctamente.');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al eliminar el plan.');
        }
    }

    // ── Guardar ───────────────────────────────────────────────
    public function guardar()
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'gestion_planes.actualizar' : 'gestion_planes.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        DB::beginTransaction();
        try {
            $datos = [
                'plan_nombre'        => $this->planNombre,
                'plan_descripcion'   => $this->planDescripcion ?: null,
                'plan_precio'        => $this->planPrecio,
                'plan_duracion_dias' => $this->planDuracionDias,
                'plan_estado'        => $this->planEstado,
                'updated_at'         => now(),
            ];

            if ($this->modoEdicion) {
                DB::table('planes')->where('id_plan', $this->idPlanEditar)->update($datos);
            } else {
                $datos['created_at'] = now();
                DB::table('planes')->insert($datos);
            }

            DB::commit();
            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $this->modoEdicion
                ? 'Plan actualizado correctamente.'
                : 'Plan creado correctamente.'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar el plan.');
        }
    }

    // ── Limpiar formulario ────────────────────────────────────
    public function limpiarFormulario()
    {
        $this->reset(['planNombre', 'planDescripcion', 'planPrecio', 'planDuracionDias', 'idPlanEditar', 'modoEdicion']);
        $this->planEstado = 1;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatingBuscar()    { $this->resetPage(); }
    public function updatingPorPagina() { $this->resetPage(); }
}
