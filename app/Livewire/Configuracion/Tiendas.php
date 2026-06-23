<?php

namespace App\Livewire\Configuracion;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Tiendas extends Component
{
    use WithPagination;

    private $logs;

    public function boot()
    {
        $this->logs = new Logs();
    }

    // ── Contexto empresa ──────────────────────────────────────
    public $idEmpresa     = null;
    public $nombreEmpresa = null;
    public $logoEmpresa   = null;
    public $idGrupo       = null;

    public function mount($idEmpresa = null, $nombreEmpresa = null, $idGrupo = null): void
    {
        abort_if(!auth()->user()->can('opcion_gestion_tiendas.listar'), 403);
        $this->idEmpresa     = $idEmpresa;
        $this->nombreEmpresa = $nombreEmpresa;
        $this->idGrupo       = $idGrupo;

        if ($idEmpresa) {
            $foto = DB::table('empresa')->where('id_empresa', $idEmpresa)->value('empresa_foto');
            $this->logoEmpresa = ($foto && $foto !== 'sin-logo.png') ? $foto : null;
        }
    }

    // ══════════════════════════════════════════════════════════
    //  PROPIEDADES — Formulario
    // ══════════════════════════════════════════════════════════
    public $tiendaNombre      = '';
    public $tiendaCodigo      = '';
    public $tiendaDireccion   = '';
    public $tiendaTelefono    = '';
    public $tiendaResponsable = null;
    public int $tiendaTipo    = 1;

    // ── Control modal ─────────────────────────────────────────
    public $modoEdicion      = false;
    public $idTiendaEditar   = null;
    public $idTiendaEliminar = null;

    // ── Búsqueda y paginación ─────────────────────────────────
    public $buscar         = '';
    public $porPagina      = 10;
    public $ordenColumna   = 't.id_tienda';
    public $ordenDireccion = 'asc';

    // ══════════════════════════════════════════════════════════
    //  VALIDACIONES
    // ══════════════════════════════════════════════════════════
    protected function rules(): array
    {
        return [
            'tiendaNombre' => [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) {
                    $q = DB::table('tiendas')
                        ->where('tienda_nombre', $value)
                        ->where('tienda_estado', '!=', 0)
                        ->when($this->idEmpresa, fn($q) => $q->where('id_empresa', $this->idEmpresa));
                    if ($this->modoEdicion && $this->idTiendaEditar) {
                        $q->where('id_tienda', '!=', $this->idTiendaEditar);
                    }
                    if ($q->exists()) {
                        $fail('Ya existe una sede con ese nombre en esta empresa.');
                    }
                },
            ],
            'tiendaCodigo'    => 'nullable|string|max:50',
            'tiendaDireccion' => 'required|string|max:500',
            'tiendaTelefono'  => 'nullable|string|max:30',
            'tiendaResponsable' => 'nullable|exists:users,id_users',
        ];
    }

    protected function messages(): array
    {
        return [
            'tiendaNombre.required'    => 'El nombre de la sede es obligatorio.',
            'tiendaNombre.max'         => 'El nombre no puede superar 255 caracteres.',
            'tiendaDireccion.required' => 'La dirección es obligatoria.',
            'tiendaDireccion.max'      => 'La dirección no puede superar 500 caracteres.',
        ];
    }

    // ══════════════════════════════════════════════════════════
    //  RENDER
    // ══════════════════════════════════════════════════════════
    public function render()
    {
        $columnasPermitidas = ['t.id_tienda', 't.tienda_nombre'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 't.id_tienda';
        $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

        $tiendas = DB::table('tiendas as t')
            ->leftJoin('users as u', 'u.id_users', '=', 't.id_responsable')
            ->select(
                't.*',
                'u.nombre_users as responsable_nombre',
                DB::raw('(SELECT COUNT(*) FROM caja_numero WHERE id_tienda = t.id_tienda AND caja_numero_estado = 1) as count_cajas')
            )
            ->whereIn('t.tienda_tipo', [1, 2])
            ->whereNull('t.id_tienda_padre')
            ->when($this->idEmpresa, fn($q) => $q->where('t.id_empresa', $this->idEmpresa))
            ->where('t.tienda_nombre', 'like', "%{$this->buscar}%")
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        $usuarios = DB::table('users')
            ->where('users_estado', 1)
            ->orderBy('nombre_users')
            ->get(['id_users', 'nombre_users']);

        return view('livewire.configuracion.tiendas', compact('tiendas', 'usuarios'));
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

    public function abrirModalEditar($idTienda)
    {
        $this->limpiarFormulario();

        $tienda = DB::table('tiendas')->where('id_tienda', $idTienda)->first();
        if (!$tienda) {
            session()->flash('error', 'Tienda no encontrada.');
            return;
        }

        $this->idTiendaEditar   = $tienda->id_tienda;
        $this->tiendaNombre     = $tienda->tienda_nombre;
        $this->tiendaCodigo     = $tienda->tienda_codigo ?? '';
        $this->tiendaDireccion  = $tienda->tienda_direccion ?? '';
        $this->tiendaTelefono   = $tienda->tienda_telefono ?? '';
        $this->tiendaResponsable= $tienda->id_responsable;
        $this->tiendaTipo       = (int) ($tienda->tienda_tipo ?? 1);
        $this->modoEdicion      = true;
        $this->dispatch('abrirModal');
    }

    public function confirmarEliminar($idTienda)
    {
        $this->idTiendaEliminar = $idTienda;
        $this->dispatch('abrirModalEliminar');
    }

    public function eliminar()
    {
        try {
            if (!auth()->user()->can('opcion_gestion_tiendas.cambiar_estado')) {
                $this->dispatch('cerrarModalEliminar');
                session()->flash('error', 'No tienes permiso para deshabilitar sedes.');
                return;
            }

            DB::table('tiendas')
                ->where('id_tienda', $this->idTiendaEliminar)
                ->update(['tienda_estado' => 0, 'updated_at' => now()]);

            $this->idTiendaEliminar = null;
            $this->dispatch('cerrarModalEliminar');
            session()->flash('success', 'Sede deshabilitada correctamente.');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al deshabilitar la sede.');
        }
    }

    public function habilitarTienda($idTienda)
    {
        try {
            if (!auth()->user()->can('opcion_gestion_tiendas.cambiar_estado')) {
                session()->flash('error', 'No tienes permiso para habilitar sedes.');
                return;
            }
            DB::table('tiendas')
                ->where('id_tienda', $idTienda)
                ->update(['tienda_estado' => 1, 'updated_at' => now()]);
            session()->flash('success', 'Sede habilitada correctamente.');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al habilitar la sede.');
        }
    }

    public function guardar()
    {
        $this->validate();

        $permiso = $this->modoEdicion ? 'opcion_gestion_tiendas.actualizar' : 'opcion_gestion_tiendas.crear';
        if (!auth()->user()->can($permiso)) {
            $this->dispatch('cerrarModal');
            session()->flash('error', 'No tienes permiso para realizar esta acción.');
            return;
        }

        try {
            $datos = [
                'tienda_nombre'    => $this->tiendaNombre,
                'tienda_codigo'    => $this->tiendaCodigo ?: null,
                'tienda_direccion' => $this->tiendaDireccion,
                'tienda_telefono'  => $this->tiendaTelefono ?: null,
                'tienda_tipo'      => 1,
                'id_responsable'   => $this->tiendaResponsable ?: null,
                'tienda_estado'    => 1,
                'updated_at'       => now(),
            ];

            if ($this->modoEdicion) {
                DB::table('tiendas')->where('id_tienda', $this->idTiendaEditar)->update($datos);
            } else {
                $datos['id_empresa']       = $this->idEmpresa;
                $datos['tienda_microtime'] = microtime(true);
                $datos['created_at']       = now();
                DB::table('tiendas')->insert($datos);
            }

            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', $this->modoEdicion
                ? 'Sede actualizada correctamente.'
                : 'Sede registrada correctamente.'
            );

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al guardar la sede.');
        }
    }

    public function limpiarFormulario()
    {
        $this->reset(['tiendaNombre', 'tiendaCodigo', 'tiendaDireccion', 'tiendaTelefono',
                      'tiendaResponsable', 'idTiendaEditar', 'modoEdicion']);
        $this->tiendaTipo = 1;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatingBuscar()    { $this->resetPage(); }
    public function updatingPorPagina() { $this->resetPage(); }
}
