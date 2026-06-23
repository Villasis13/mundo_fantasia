<?php

namespace App\Livewire\Configuracion;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Sucursales extends Component
{
    use WithPagination;

    // ── Contexto empresa padre ──────────────────────────────────
    public int    $idEmpresa     = 0;
    public string $nombreEmpresa = '';

    // ── Propiedades formulario ──────────────────────────────────
    public string $sucursalNombre    = '';
    public string $sucursalDireccion = '';
    public int    $sucursalTipo      = 2;

    // ── Control modal ───────────────────────────────────────────
    public bool $modoEdicion = false;
    public ?int $idEditar    = null;

    // ── Modal cambio de estado ──────────────────────────────────
    public ?int $idCambiarEstado = null;
    public ?int $nuevoEstado     = null;

    // ── Búsqueda y paginación ───────────────────────────────────
    public string $buscar         = '';
    public int    $porPagina      = 10;
    public string $ordenColumna   = 'id_sucursal';
    public string $ordenDireccion = 'asc';

    private ?Logs $logs = null;

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    public function mount(int $idEmpresa = 0): void
    {
        abort_if(!auth()->user()->can('sucursal_opcion.listar'), 403);

        $this->idEmpresa = $idEmpresa;
        if ($idEmpresa > 0) {
            $empresa = DB::table('empresa')->where('id_empresa', $idEmpresa)->first();
            $this->nombreEmpresa = $empresa?->empresa_razon_social ?? '';
        }
    }

    // ── Validaciones ────────────────────────────────────────────
    protected function rules(): array
    {
        return [
            'sucursalNombre'    => 'required|string|max:255',
            'sucursalDireccion' => 'nullable|string|max:1000',
            'sucursalTipo'      => 'required|integer|in:1,2,3',
        ];
    }

    protected function messages(): array
    {
        return [
            'sucursalNombre.required' => 'El nombre de la sucursal es obligatorio.',
            'sucursalNombre.max'      => 'El nombre no puede exceder 255 caracteres.',
        ];
    }

    // ── Render ──────────────────────────────────────────────────
    public function render()
    {
        $columnasPermitidas = ['id_sucursal', 'sucursal_nombre'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'id_sucursal';
        $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

        $sucursales = DB::table('sucursals as s')
            ->selectRaw('s.*, (SELECT COUNT(*) FROM caja_numero c WHERE c.id_sucursal = s.id_sucursal AND c.caja_numero_estado = 1) as contar_cajas')
            ->where('s.id_empresa', $this->idEmpresa)
            ->whereNull('s.deleted_at')
            ->where(function ($q) {
                $q->where('s.sucursal_nombre',    'like', "%{$this->buscar}%")
                  ->orWhere('s.sucursal_direccion','like', "%{$this->buscar}%");
            })
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        return view('livewire.configuracion.sucursales', compact('sucursales'));
    }

    // ── Ordenar ─────────────────────────────────────────────────
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

    // ── Abrir modal nueva sucursal ───────────────────────────────
    public function abrirModalNuevo(): void
    {
        $this->limpiarFormulario();
        $this->modoEdicion = false;
        $this->dispatch('abrirModal');
    }

    // ── Abrir modal editar sucursal ──────────────────────────────
    public function abrirModalEditar(int $id): void
    {
        $this->limpiarFormulario();

        $suc = DB::table('sucursals')->where('id_sucursal', $id)->whereNull('deleted_at')->first();
        if (!$suc) {
            session()->flash('error', 'Sucursal no encontrada.');
            return;
        }

        $this->idEditar          = $suc->id_sucursal;
        $this->sucursalNombre    = $suc->sucursal_nombre;
        $this->sucursalDireccion = $suc->sucursal_direccion ?? '';
        $this->sucursalTipo      = (int) ($suc->sucursal_tipo ?? 2);
        $this->modoEdicion       = true;
        $this->dispatch('abrirModal');
    }

    // ── Guardar sucursal ─────────────────────────────────────────
    public function guardar(): void
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'sucursal_opcion.actualizar' : 'sucursal_opcion.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        DB::beginTransaction();
        try {
            $datos = [
                'sucursal_nombre'    => $this->sucursalNombre,
                'sucursal_direccion' => $this->sucursalDireccion ?: null,
                'sucursal_tipo'      => $this->sucursalTipo,
                'sucursal_estado'    => 1,
                'updated_at'         => now(),
            ];

            if ($this->modoEdicion) {
                DB::table('sucursals')
                    ->where('id_sucursal', $this->idEditar)
                    ->update($datos);
            } else {
                $datos['id_empresa'] = $this->idEmpresa;
                $datos['created_at'] = now();
                DB::table('sucursals')->insert($datos);
            }

            DB::commit();
            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $this->modoEdicion
                ? 'Sucursal actualizada correctamente.'
                : 'Sucursal creada correctamente.'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar la sucursal.');
        }
    }

    // ── Confirmar cambio de estado ───────────────────────────────
    public function confirmarCambiarEstado(int $id, int $estado): void
    {
        $this->idCambiarEstado = $id;
        $this->nuevoEstado     = $estado;
        $this->dispatch('abrirModalEstado');
    }

    // ── Cambiar estado ───────────────────────────────────────────
    public function cambiarEstado(): void
    {
        if (!auth()->user()->can('sucursal_opcion.cambiar_estado')) {
            $this->dispatch('cerrarModalEstado');
            session()->flash('error', 'No tienes permiso para cambiar el estado.');
            return;
        }

        DB::beginTransaction();
        try {
            DB::table('sucursals')
                ->where('id_sucursal', $this->idCambiarEstado)
                ->update(['sucursal_estado' => $this->nuevoEstado, 'updated_at' => now()]);

            // Cascada: desactivar cajas cuando se desactiva la sucursal
            if ((int) $this->nuevoEstado === 0) {
                DB::table('caja_numero')
                    ->where('id_sucursal', $this->idCambiarEstado)
                    ->update(['caja_numero_estado' => 0, 'updated_at' => now()]);
            }

            DB::commit();

            $this->idCambiarEstado = null;
            $this->nuevoEstado     = null;
            $this->dispatch('cerrarModalEstado');
            session()->flash('success', 'Estado actualizado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al cambiar el estado.');
        }
    }

    // ── Limpiar formulario ───────────────────────────────────────
    public function limpiarFormulario(): void
    {
        $this->reset(['sucursalNombre', 'sucursalDireccion', 'idEditar', 'modoEdicion']);
        $this->sucursalTipo = 2;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatingBuscar(): void    { $this->resetPage(); }
    public function updatingPorPagina(): void { $this->resetPage(); }
}
