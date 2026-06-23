<?php

namespace App\Livewire\Gestion;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Familias extends Component
{
    use WithPagination;

    // ── Formulario ────────────────────────────────────────────
    public string $faNombre       = '';
    public int    $empresaIdModal = 0;

    // ── Control modal CRUD ────────────────────────────────────
    public bool $modoEdicion = false;
    public ?int $idEditar    = null;
    public ?int $idEliminar  = null;

    // ── Búsqueda, filtros y paginación ────────────────────────
    public string $buscar         = '';
    public int    $porPagina      = 10;
    public string $ordenColumna   = 'f.id_fa';
    public string $ordenDireccion = 'desc';
    public int    $filtroEmpresa  = 0;

    private ?Logs $logs         = null;
    private int   $cachedRoleId = 0;

    public function boot(): void
    {
        $this->logs = new Logs();
        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('gestion_familias.listar'), 403);
    }

    // ── Helpers de rol ────────────────────────────────────────
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }
    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->orderBy('us.id_sucursal')
            ->value('s.id_empresa');
        return $id ? (int) $id : null;
    }

    // ── Validación ────────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'faNombre' => 'required|string|max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'faNombre.required' => 'El nombre de la familia es obligatorio.',
            'faNombre.max'      => 'El nombre no puede exceder 255 caracteres.',
        ];
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        $esAdmin        = $this->esAdmin();
        $esSuperAdmin   = $this->esSuperAdmin();
        $adminEmpresaId = $esAdmin ? $this->adminEmpresaId() : null;
        $filtroEmpresa  = $this->filtroEmpresa;

        $columnasPermitidas = ['f.id_fa', 'f.fa_nombre', 'e.empresa_nombrecomercial'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'f.id_fa';
        $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

        $familias = DB::table('familias as f')
            ->selectRaw('f.id_fa, f.fa_nombre, f.fa_estado, f.id_empresa,
                (SELECT COUNT(*) FROM categorias c WHERE c.id_fa = f.id_fa AND c.ca_estado = 1) AS contar_categorias')
            ->where('f.fa_estado', 1)
            ->when($this->buscar, fn($q) => $q->where('f.fa_nombre', 'like', "%{$this->buscar}%"))
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        $empresas = collect();

        return view('livewire.gestion.familias', compact(
            'familias', 'empresas', 'esAdmin', 'esSuperAdmin'
        ));
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
        $familia = DB::table('familias')->where('id_fa', $id)->first();
        if (!$familia) {
            session()->flash('error', 'Familia no encontrada.');
            return;
        }
        $this->idEditar       = $familia->id_fa;
        $this->faNombre       = $familia->fa_nombre;
        $this->empresaIdModal = (int) ($familia->id_empresa ?? 0);
        $this->modoEdicion    = true;
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
        if (!auth()->user()->can('gestion_familias.cambiar_estado')) {
            $this->dispatch('cerrarModalEliminar');
            session()->flash('error', 'No tienes permiso para desactivar familias.');
            return;
        }

        try {
            DB::table('familias')->where('id_fa', $this->idEliminar)->update(['fa_estado' => 0, 'updated_at' => now()]);
            $this->idEliminar = null;
            $this->dispatch('cerrarModalEliminar');
            session()->flash('success', 'Familia eliminada correctamente.');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al eliminar la familia.');
        }
    }

    // ── Guardar ───────────────────────────────────────────────
    public function guardar(): void
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'gestion_familias.actualizar' : 'gestion_familias.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        DB::beginTransaction();
        try {
            if ($this->modoEdicion) {
                DB::table('familias')->where('id_fa', $this->idEditar)->update([
                    'fa_nombre'  => $this->faNombre,
                    'id_empresa' => null,
                    'updated_at' => now(),
                ]);
                $mensaje = 'Familia actualizada correctamente.';
            } else {
                DB::table('familias')->insert([
                    'id_empresa' => null,
                    'fa_nombre'  => $this->faNombre,
                    'fa_estado'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $mensaje = 'Familia creada correctamente.';
            }
            DB::commit();
            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $mensaje);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar la familia.');
        }
    }

    // ── Limpiar formulario ────────────────────────────────────
    public function limpiarFormulario(): void
    {
        $this->reset(['faNombre', 'empresaIdModal', 'idEditar', 'modoEdicion']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatingBuscar(): void        { $this->resetPage(); }
    public function updatingPorPagina(): void     { $this->resetPage(); }
    public function updatingFiltroEmpresa(): void { $this->resetPage(); }
}
