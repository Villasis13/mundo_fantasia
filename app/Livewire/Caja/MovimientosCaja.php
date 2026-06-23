<?php

namespace App\Livewire\Caja;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class MovimientosCaja extends Component
{
    use WithPagination;

    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    public       $cajas                    = [];
    public int   $idCajaNumeroSeleccionada = 0;
    public       $cajaAbierta             = null;

    public int    $tipo             = 1;
    public string $concepto         = '';
    public string $monto            = '';
    public string $idTipoPago       = '';
    public string $numeroOperacion  = '';
    public string $observacion      = '';

    public string $filtroFecha    = '';
    public int    $porPagina      = 10;
    public string $ordenColumna   = 'id_caja_movimiento';
    public string $ordenDireccion = 'desc';

    public ?int $idEliminar = null;

    private $logs;

    public function boot(): void
    {
        $this->logs = new Logs();
        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }

    private function empresaUsuario(): ?int
    {
        $id = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('t.id_empresa');
        return $id ? (int) $id : null;
    }

    private function resolverIdEmpresa(): ?int
    {
        return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : $this->empresaUsuario();
    }

    private function resolverIdSucursal(): int
    {
        if ($this->esSuperAdmin() || $this->esAdmin()) return $this->sucursalSeleccionada;
        $id = DB::table('user_tienda as ut')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('ut.id_tienda');
        return $id ? (int) $id : 0;
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('movimientos_caja.listar'), 403);

        $this->filtroFecha = now()->format('Y-m-d');

        $empresaId = $this->empresaUsuario();
        if ($empresaId) {
            $this->empresaSeleccionada   = $empresaId;
            $this->sucursalesDisponibles = DB::table('tiendas')
                ->where('id_empresa', $empresaId)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')->get();
        }

        if (!$this->esSuperAdmin() && !$this->esAdmin()) {
            $idTienda = DB::table('user_tienda as ut')
                ->where('ut.id_users', auth()->user()->id_users)
                ->value('ut.id_tienda');
            if ($idTienda) {
                $this->sucursalSeleccionada = (int) $idTienda;
                $this->cargarCajasPorSucursal();
                $cajaHoy = DB::table('caja')
                    ->where('id_users_apertura', auth()->user()->id_users)
                    ->where('caja_fecha', now()->toDateString())
                    ->where('caja_estado', 1)
                    ->first();
                if ($cajaHoy) {
                    $this->idCajaNumeroSeleccionada = (int) $cajaHoy->id_caja_numero;
                    $this->cajaAbierta = $cajaHoy;
                }
            }
        } elseif ($this->esAdmin() && $empresaId) {
            $sucursales = DB::table('tiendas')
                ->where('id_empresa', $empresaId)
                ->where('tienda_estado', 1)
                ->get();
            if ($sucursales->count() === 1) {
                $this->sucursalSeleccionada = $sucursales->first()->id_tienda;
                $this->cargarCajasPorSucursal();
            }
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada     = 0;
        $this->cajas                    = [];
        $this->idCajaNumeroSeleccionada = 0;
        $this->cajaAbierta              = null;
        $this->resetPage();

        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')->get()
            : collect();
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->cajas                    = [];
        $this->idCajaNumeroSeleccionada = 0;
        $this->cajaAbierta              = null;
        $this->resetPage();

        if ($this->sucursalSeleccionada > 0) {
            $this->cargarCajasPorSucursal();
        }
    }

    public function updatedIdCajaNumeroSeleccionada(): void
    {
        $this->cajaAbierta = null;
        $this->resetPage();
        $this->buscarCajaAbierta();
    }

    public function updatingFiltroFecha(): void { $this->resetPage(); }
    public function updatingPorPagina(): void   { $this->resetPage(); }

    private function cargarCajasPorSucursal(): void
    {
        $this->cajas = DB::table('caja_numero')
            ->where('id_tienda', $this->sucursalSeleccionada)
            ->where('caja_numero_estado', 1)
            ->orderBy('caja_numero_nombre')
            ->get();

        if (count($this->cajas) === 1) {
            $this->idCajaNumeroSeleccionada = $this->cajas[0]->id_caja_numero;
            $this->buscarCajaAbierta();
        }
    }

    private function buscarCajaAbierta(): void
    {
        if (!$this->idCajaNumeroSeleccionada) return;

        $this->cajaAbierta = DB::table('caja')
            ->where('id_caja_numero', $this->idCajaNumeroSeleccionada)
            ->where('caja_fecha', now()->toDateString())
            ->where('caja_estado', 1)
            ->first();
    }

    protected function rules(): array
    {
        $rules = [
            'tipo'       => 'required|in:1,2',
            'concepto'   => 'required|string|max:300',
            'monto'      => 'required|numeric|min:0.01',
            'idTipoPago' => 'required|integer',
        ];
        if ($this->idTipoPago && $this->idTipoPago != 1) {
            $rules['numeroOperacion'] = 'required|string|max:100';
        }
        return $rules;
    }

    protected function messages(): array
    {
        return [
            'tipo.required'              => 'Seleccione el tipo de movimiento.',
            'concepto.required'          => 'El concepto es obligatorio.',
            'monto.required'             => 'El monto es obligatorio.',
            'monto.numeric'              => 'El monto debe ser un número válido.',
            'monto.min'                  => 'El monto debe ser mayor a cero.',
            'idTipoPago.required'        => 'Seleccione el medio de pago.',
            'numeroOperacion.required'   => 'El número de operación es obligatorio para este medio de pago.',
        ];
    }

    public function guardar(): void
    {
        if (!auth()->user()->can('movimientos_caja.crear')) {
            session()->flash('error', 'No tienes permiso para registrar movimientos.');
            return;
        }
        if (!$this->cajaAbierta) {
            session()->flash('error', 'No hay una caja abierta para registrar movimientos.');
            return;
        }

        $this->validate();

        DB::beginTransaction();
        try {
            DB::table('caja_movimientos')->insert([
                'id_caja'          => $this->cajaAbierta->id_caja,
                'id_users'         => auth()->user()->id_users,
                'id_empresa'       => $this->resolverIdEmpresa(),
                'id_sucursal'      => $this->resolverIdSucursal() ?: null,
                'tipo'             => (int) $this->tipo,
                'concepto'         => trim($this->concepto),
                'monto'            => (float) $this->monto,
                'id_tipo_pago'     => $this->idTipoPago ?: null,
                'numero_operacion' => trim($this->numeroOperacion) ?: null,
                'observacion'      => trim($this->observacion) ?: null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::commit();
            $this->limpiarFormulario();
            $this->dispatch('cerrarModal');
            session()->flash('success', 'Movimiento registrado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al registrar el movimiento.');
        }
    }

    public function confirmarEliminar(int $id): void
    {
        $this->idEliminar = $id;
        $this->dispatch('abrirModalEliminar');
    }

    public function eliminar(): void
    {
        if (!auth()->user()->can('movimientos_caja.eliminar')) {
            $this->dispatch('cerrarModalEliminar');
            session()->flash('error', 'No tienes permiso para eliminar movimientos.');
            return;
        }

        DB::beginTransaction();
        try {
            DB::table('caja_movimientos')
                ->where('id_caja_movimiento', $this->idEliminar)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);

            DB::commit();
            $this->idEliminar = null;
            $this->dispatch('cerrarModalEliminar');
            session()->flash('success', 'Movimiento eliminado.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al eliminar.');
        }
    }

    public function ordenar(string $columna): void
    {
        $this->ordenDireccion = $this->ordenColumna === $columna
            ? ($this->ordenDireccion === 'asc' ? 'desc' : 'asc')
            : 'desc';
        $this->ordenColumna = $columna;
        $this->resetPage();
    }

    public function limpiarFormulario(): void
    {
        $this->reset(['tipo', 'concepto', 'monto', 'idTipoPago', 'numeroOperacion', 'observacion']);
        $this->tipo = 1;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $tiposPago = DB::table('tipo_pago')
            ->where('tipo_pago_estado', 1)
            ->orderBy('tipo_pago_nombre')
            ->get();

        $movimientos = collect();
        $resumen     = null;

        if ($this->idCajaNumeroSeleccionada) {
            $columnasPermitidas = ['id_caja_movimiento', 'concepto', 'monto', 'created_at'];
            $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'id_caja_movimiento';
            $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

            $ids = DB::table('caja')
                ->where('id_caja_numero', $this->idCajaNumeroSeleccionada)
                ->whereDate('caja_fecha', $this->filtroFecha)
                ->pluck('id_caja');

            $query = DB::table('caja_movimientos as cm')
                ->select('cm.*', 'u.nombre_users', 'tp.tipo_pago_nombre')
                ->join('users as u', 'u.id_users', '=', 'cm.id_users')
                ->leftJoin('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'cm.id_tipo_pago')
                ->whereNull('cm.deleted_at')
                ->whereIn('cm.id_caja', $ids);

            if ($this->filtroFecha) {
                $query->whereDate('cm.created_at', $this->filtroFecha);
            }

            $movimientos = $query->orderBy("cm.{$columna}", $direccion)->paginate($this->porPagina);

            $baseResumen = DB::table('caja_movimientos as cm')
                ->whereNull('cm.deleted_at')
                ->whereIn('cm.id_caja', $ids);

            if ($this->filtroFecha) {
                $baseResumen->whereDate('cm.created_at', $this->filtroFecha);
            }

            $resumen = (object) [
                'total_ingresos' => (clone $baseResumen)->where('tipo', 1)->sum('monto'),
                'total_egresos'  => (clone $baseResumen)->where('tipo', 2)->sum('monto'),
            ];
        }

        return view('livewire.caja.movimientos-caja', compact(
            'esSuperAdmin', 'esAdmin',
            'empresas', 'tiposPago',
            'movimientos', 'resumen'
        ));
    }
}
