<?php

namespace App\Livewire\Gestion;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Categorias extends Component
{
    use WithPagination;

    // ── Contexto de familia padre ─────────────────────────────
    public int    $idFamilia     = 0;
    public string $nombreFamilia = '';

    // ── Formulario ────────────────────────────────────────────
    public string $caNombre = '';

    // ── Control modal CRUD ────────────────────────────────────
    public bool $modoEdicion = false;
    public ?int $idEditar    = null;
    public ?int $idEliminar  = null;

    // ── Búsqueda y paginación ─────────────────────────────────
    public string $buscar         = '';
    public int    $porPagina      = 10;
    public string $ordenColumna   = 'id_ca';
    public string $ordenDireccion = 'desc';

    private ?Logs $logs = null;

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    public function mount(int $idFamilia = 0): void
    {
        abort_if(!auth()->user()->can('gestion_categorias.listar'), 403);

        $this->idFamilia = $idFamilia;
        if ($idFamilia > 0) {
            $familia = DB::table('familias')->where('id_fa', $idFamilia)->first();
            $this->nombreFamilia = $familia?->fa_nombre ?? '';
        }
    }

    // ── Validación ────────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'caNombre' => 'required|string|max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'caNombre.required' => 'El nombre de la categoría es obligatorio.',
            'caNombre.max'      => 'El nombre no puede exceder 255 caracteres.',
        ];
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        $columnasPermitidas = ['id_ca', 'ca_nombre'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'id_ca';
        $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

        $categorias = DB::table('categorias')
            ->where('id_fa', $this->idFamilia)
            ->where('ca_estado', 1)
            ->when($this->buscar, fn($q) => $q->where('ca_nombre', 'like', "%{$this->buscar}%"))
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        return view('livewire.gestion.categorias', compact('categorias'));
    }

    // ── Ordenar ───────────────────────────────────────────────
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

    // ── Abrir modal nuevo ─────────────────────────────────────
    public function abrirModalNuevo(): void
    {
        $this->limpiarFormulario();
        $this->modoEdicion = false;
        $this->dispatch('abrirModal');
    }

    // ── Abrir modal editar ────────────────────────────────────
    public function abrirModalEditar(int $id): void
    {
        $this->limpiarFormulario();
        $categoria = DB::table('categorias')->where('id_ca', $id)->first();
        if (!$categoria) {
            session()->flash('error', 'Categoría no encontrada.');
            return;
        }
        $this->idEditar    = $categoria->id_ca;
        $this->caNombre    = $categoria->ca_nombre;
        $this->modoEdicion = true;
        $this->dispatch('abrirModal');
    }

    // ── Confirmar eliminar ────────────────────────────────────
    public function confirmarEliminar(int $id): void
    {
        $this->idEliminar = $id;
        $this->dispatch('abrirModalEliminar');
    }

    // ── Eliminar ──────────────────────────────────────────────
    public function eliminar(): void
    {
        if (!auth()->user()->can('gestion_categorias.cambiar_estado')) {
            $this->dispatch('cerrarModalEliminar');
            session()->flash('error', 'No tienes permiso para desactivar categorías.');
            return;
        }

        try {
            DB::table('categorias')->where('id_ca', $this->idEliminar)->update(['ca_estado' => 0, 'updated_at' => now()]);
            $this->idEliminar = null;
            $this->dispatch('cerrarModalEliminar');
            session()->flash('success', 'Categoría eliminada correctamente.');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al eliminar la categoría.');
        }
    }

    // ── Guardar ───────────────────────────────────────────────
    public function guardar(): void
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'gestion_categorias.actualizar' : 'gestion_categorias.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        DB::beginTransaction();
        try {
            if ($this->modoEdicion) {
                DB::table('categorias')->where('id_ca', $this->idEditar)->update([
                    'ca_nombre'  => $this->caNombre,
                    'updated_at' => now(),
                ]);
                $mensaje = 'Categoría actualizada correctamente.';
            } else {
                DB::table('categorias')->insert([
                    'id_fa'      => $this->idFamilia,
                    'ca_nombre'  => $this->caNombre,
                    'ca_estado'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $mensaje = 'Categoría creada correctamente.';
            }
            DB::commit();
            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $mensaje);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar la categoría.');
        }
    }

    // ── Limpiar formulario ────────────────────────────────────
    public function limpiarFormulario(): void
    {
        $this->reset(['caNombre', 'idEditar', 'modoEdicion']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatingBuscar(): void    { $this->resetPage(); }
    public function updatingPorPagina(): void { $this->resetPage(); }
}
