<?php

namespace App\Livewire\GestionVentas;

use App\Models\Cliente;
use App\Models\General;
use App\Models\Logs;
use App\Models\PagosCuota;
use App\Models\Tipo_pago;
use App\Models\VentaCuota;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class RegistrarPagos extends Component
{
    use WithPagination, WithoutUrlPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    // ── Rol y contexto ────────────────────────────────────────
    private int $cachedRoleId        = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    // ── Servicios privados ────────────────────────────────────
    private $logs;
    private $general;

    public function boot(): void
    {
        $this->logs    = new Logs();
        $this->general = new General();

        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    private function esSuperAdmin(): bool { return $this->cachedRoleId === 1; }
    private function esAdmin(): bool      { return $this->cachedRoleId === 2; }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->orderBy('us.id_sucursal')
            ->value('s.id_empresa');
        return $id ? (int) $id : null;
    }

    private function resolverIdEmpresa(): ?int
    {
        if ($this->esSuperAdmin()) {
            return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : null;
        }
        if ($this->esAdmin()) {
            return $this->adminEmpresaId();
        }
        // Otros roles: derivar empresa desde la sucursal activa en sesión
        $idSucursal = (int) session('sucursal_activa_id', 0);
        if (!$idSucursal) return null;
        $id = DB::table('sucursals')->where('id_sucursal', $idSucursal)->value('id_empresa');
        return $id ? (int) $id : null;
    }

    // ── Filtros de búsqueda ───────────────────────────────────
    public $buscar;

    #[Validate('nullable')]
    #[Validate('date', message: 'La fecha "Desde" no tiene un formato válido.')]
    public $desde;

    #[Validate('nullable')]
    #[Validate('date', message: 'La fecha "Hasta" no tiene un formato válido.')]
    #[Validate('after_or_equal:desde', message: 'La fecha "Hasta" debe ser igual o posterior a la fecha "Desde".')]
    public $hasta;

    #[Validate('nullable|integer|min:1|exists:clientes,id_clientes', message: 'El cliente seleccionado no es válido.')]
    public $idCliente;

    #[Validate('nullable|integer|in:0,1,2', message: 'El estado de pago solo puede ser "Pagado", "Pendiente" o "Todos".')]
    public $estadoPagado;

    // ── Formulario de pago ────────────────────────────────────
    #[Validate('required|integer|exists:tipo_pago,id_tipo_pago', message: 'Debe seleccionar un tipo de pago válido.')]
    public $id_tipo_pago;

    #[Validate('required|numeric|gt:0', message: 'El monto debe ser un número mayor a 0.')]
    public $monto;

    #[Validate('required|date', message: 'Debe ingresar una fecha válida.')]
    public $fecha;

    #[Validate('nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,bmp,tiff|max:5120', message: 'El voucher debe ser un archivo válido (PDF, Word, Excel o imagen). Tamaño máximo: 5MB.')]
    public $voucher;

    #[Validate('required|integer|exists:ventas_cuotas,id_ventas_cuotas', message: 'Debe seleccionar una cuota válida.')]
    public $idVentaCuota;

    public $pagosRealizadosCuota;
    public $montoTotalCuota;
    public $montoPagadoCuota;
    public $montoRestante;

    // ── Lifecycle hooks ───────────────────────────────────────

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        $this->idCliente            = null;

        if ($this->empresaSeleccionada > 0) {
            $this->sucursalesDisponibles = DB::table('sucursals')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('sucursal_estado', 1)
                ->whereNull('deleted_at')
                ->orderBy('sucursal_nombre')
                ->get();
        } else {
            $this->sucursalesDisponibles = collect();
        }

        $this->resetPage();
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->resetPage();
    }

    // ── Mount ─────────────────────────────────────────────────

    public function mount(): void
    {
        abort_if(!auth()->user()->can('registro_pagos.listar'), 403);

        $this->idCliente              = null;
        $this->idVentaCuota           = null;
        $this->estadoPagado           = 2;
        $this->buscar                 = false;
        $this->desde                  = now()->format('Y-m-d');
        $this->hasta                  = now()->format('Y-m-d');
        $this->pagosRealizadosCuota   = [];
        $this->montoTotalCuota        = 0;
        $this->montoPagadoCuota       = 0;
        $this->montoRestante          = 0;

        if ($this->esAdmin()) {
            $empresaId = $this->adminEmpresaId();
            if ($empresaId) {
                $this->sucursalesDisponibles = DB::table('sucursals')
                    ->where('id_empresa', $empresaId)
                    ->where('sucursal_estado', 1)
                    ->whereNull('deleted_at')
                    ->orderBy('sucursal_nombre')
                    ->get();

                if ($this->sucursalesDisponibles->count() === 1) {
                    $this->sucursalSeleccionada = $this->sucursalesDisponibles->first()->id_sucursal;
                }
            }
        }
    }

    // ── Acciones ──────────────────────────────────────────────

    public function listarRegistrosPagos(): void
    {
        $this->validateOnly('desde');
        $this->validateOnly('hasta');
        $this->validateOnly('idCliente');
        $this->validateOnly('estadoPagado');
        $this->resetPage();
        $this->buscar = true;
    }

    public function listarPagosRealizadosCuota($id): void
    {
        try {
            $ventaCuota = VentaCuota::find($id);
            if (!$ventaCuota) {
                session()->flash('error', 'No se encontró información de la cuota a pagar.');
                return;
            }

            $montoTotalCuota = (float) $ventaCuota->venta_cuota_importe;
            $montoPagado     = PagosCuota::where('id_ventas_cuotas', $id)->sum('pagos_cuota_monto');

            $this->montoTotalCuota  = $montoTotalCuota;
            $this->montoPagadoCuota = $montoPagado;
            $this->montoRestante    = $montoTotalCuota - $montoPagado;

            $this->pagosRealizadosCuota = PagosCuota::select(
                    'pagos_cuotas.*',
                    'tp.tipo_pago_nombre',
                    'u.nombre_users'
                )
                ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'pagos_cuotas.id_tipo_pago')
                ->join('users as u', 'u.id_users', '=', 'pagos_cuotas.id_users')
                ->where('pagos_cuotas.id_ventas_cuotas', $id)
                ->get();

            $this->idVentaCuota = $id;
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function guardarPago(): void
    {
        if (!auth()->user()->can('registro_pagos.crear')) {
            session()->flash('error', 'No tienes permiso para registrar pagos.');
            return;
        }

        try {
            $this->validateOnly('idVentaCuota');
            $this->validateOnly('id_tipo_pago');
            $this->validateOnly('monto');
            $this->validateOnly('fecha');
            $this->validateOnly('voucher');

            $ventaCuota = VentaCuota::find($this->idVentaCuota);
            if (!$ventaCuota) {
                session()->flash('error', 'No se encontró información de la cuota a pagar.');
                return;
            }

            $montoIngresa    = (float) $this->monto;
            $montoTotalCuota = (float) $ventaCuota->venta_cuota_importe;
            $montoPagado     = PagosCuota::where('id_ventas_cuotas', $this->idVentaCuota)->sum('pagos_cuota_monto');
            $saldoPagar      = round($montoTotalCuota - $montoPagado, 2);

            if ($montoIngresa > $saldoPagar) {
                session()->flash('error', 'El monto ingresado no puede ser mayor al saldo pendiente.');
                return;
            }

            DB::beginTransaction();

            $pago                      = new PagosCuota();
            $pago->id_users            = Auth::id();
            $pago->id_ventas_cuotas    = $this->idVentaCuota;
            $pago->id_tipo_pago        = $this->id_tipo_pago;
            $pago->pagos_cuota_monto   = $montoIngresa;
            $pago->pagos_cuota_fecha   = $this->fecha;

            if ($this->voucher) {
                $pago->pagos_cuota_voucher = $this->general->save_files(
                    $this->voucher, 'gestionVentas/registroPagos'
                );
            }

            if (!$pago->save()) {
                DB::rollBack();
                session()->flash('error', 'No se pudieron guardar los datos del pago.');
                return;
            }

            $montoPagadoNuevo = $montoPagado + $montoIngresa;
            if (($montoTotalCuota - $montoPagadoNuevo) <= 0) {
                $ventaCuota->venta_cuota_pago = 1;
                $ventaCuota->save();

                $todasPagadas = VentaCuota::where('id_venta', $ventaCuota->id_venta)
                    ->where('venta_cuota_estado', 1)
                    ->where('venta_cuota_pago', 0)
                    ->doesntExist();

                if ($todasPagadas) {
                    DB::table('ventas')
                        ->where('id_venta', $ventaCuota->id_venta)
                        ->update(['venta_estado_pago' => 2, 'updated_at' => now()]);
                }
            }

            DB::commit();

            $this->reset(['id_tipo_pago', 'monto', 'fecha', 'voucher']);
            $this->listarPagosRealizadosCuota($this->idVentaCuota);
            session()->flash('success', 'Pago registrado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al registrar el pago.');
        }
    }

    public function eliminarRegistroPago($idPago): void
    {
        if (!auth()->user()->can('registro_pagos.eliminar')) {
            session()->flash('error', 'No tienes permiso para eliminar pagos.');
            return;
        }

        try {
            DB::beginTransaction();

            $pago = PagosCuota::find($idPago);
            if (!$pago) {
                session()->flash('error', 'No se encontró el pago.');
                return;
            }

            $idVentaCuota = $this->idVentaCuota;

            if ($pago->pagos_cuota_voucher && file_exists($pago->pagos_cuota_voucher)) {
                unlink($pago->pagos_cuota_voucher);
            }

            if (!$pago->delete()) {
                DB::rollBack();
                session()->flash('error', 'No se pudo eliminar el pago.');
                return;
            }

            $ventaCuota = VentaCuota::find($idVentaCuota);
            if ($ventaCuota) {
                $total    = (float) $ventaCuota->venta_cuota_importe;
                $pagado   = PagosCuota::where('id_ventas_cuotas', $idVentaCuota)->sum('pagos_cuota_monto');
                if (($total - $pagado) > 0) {
                    $ventaCuota->venta_cuota_pago = 0;
                    $ventaCuota->save();

                    DB::table('ventas')
                        ->where('id_venta', $ventaCuota->id_venta)
                        ->where('venta_estado_pago', 2)
                        ->update(['venta_estado_pago' => 0, 'updated_at' => now()]);
                }
            }

            DB::commit();
            $this->listarPagosRealizadosCuota($idVentaCuota);
            session()->flash('success', 'Pago eliminado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Ocurrió un error al eliminar el pago.');
        }
    }

    // ── Render ────────────────────────────────────────────────

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();
        $empresaId    = $esAdmin ? $this->adminEmpresaId() : null;

        $registros = collect();

        if ($this->buscar) {
            $query = DB::table('ventas as v')
                ->select(
                    'v.*',
                    'c.id_tipo_documento',
                    'c.cliente_numero',
                    'c.cliente_razonsocial',
                    'm.simbolo',
                    'u.nombre_users',
                    'vc.id_ventas_cuotas',
                    'vc.venta_cuota_numero',
                    'vc.venta_cuota_fecha',
                    'vc.venta_cuota_importe',
                    'vc.venta_cuota_pago'
                )
                ->selectSub(function ($q) {
                    $q->from('pagos_cuotas')
                        ->selectRaw('COALESCE(SUM(pagos_cuota_monto), 0)')
                        ->whereColumn('pagos_cuotas.id_ventas_cuotas', 'vc.id_ventas_cuotas')
                        ->whereNull('pagos_cuotas.deleted_at');
                }, 'monto_pagado')
                ->join('ventas_cuotas as vc', 'v.id_venta', '=', 'vc.id_venta')
                ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
                ->join('monedas as m', 'm.id_moneda', '=', 'v.id_moneda')
                ->join('users as u', 'v.id_users', '=', 'u.id_users')
                ->where('v.anulado_sunat', 0)
                ->where('v.venta_cancelar', 1)
                ->where('v.venta_estado_sunat', 1)
                ->where('vc.venta_cuota_estado', 1);

            // ── Filtrado por sucursal según rol ───────────────
            if ($esSuperAdmin) {
                if (!$this->empresaSeleccionada) {
                    $query->whereRaw('0 = 1');
                } elseif ($this->sucursalSeleccionada) {
                    $query->where('v.id_sucursal', $this->sucursalSeleccionada);
                } else {
                    $empresaSeleccionada = $this->empresaSeleccionada;
                    $query->whereIn('v.id_sucursal', function ($sub) use ($empresaSeleccionada) {
                        $sub->select('id_sucursal')->from('sucursals')
                            ->where('id_empresa', $empresaSeleccionada)
                            ->where('sucursal_estado', 1)
                            ->whereNull('deleted_at');
                    });
                }
            } elseif ($esAdmin) {
                if ($this->sucursalSeleccionada) {
                    $query->where('v.id_sucursal', $this->sucursalSeleccionada);
                } elseif ($empresaId) {
                    $query->whereIn('v.id_sucursal', function ($sub) use ($empresaId) {
                        $sub->select('id_sucursal')->from('sucursals')
                            ->where('id_empresa', $empresaId)
                            ->where('sucursal_estado', 1)
                            ->whereNull('deleted_at');
                    });
                }
            } else {
                $idSucursal = (int) session('sucursal_activa_id', 0);
                if ($idSucursal) {
                    $query->where('v.id_sucursal', $idSucursal);
                } else {
                    $query->whereRaw('0 = 1');
                }
            }

            // ── Filtros adicionales ───────────────────────────
            if ($this->idCliente) {
                $query->where('v.id_clientes', $this->idCliente);
            }
            if ($this->estadoPagado !== null && in_array($this->estadoPagado, [0, 1])) {
                $query->where('vc.venta_cuota_pago', $this->estadoPagado);
            }
            if ($this->desde && $this->hasta) {
                $query->whereBetween(DB::raw('DATE(vc.venta_cuota_fecha)'), [$this->desde, $this->hasta]);
            }

            $registros = $query->orderBy('vc.id_ventas_cuotas', 'asc')->paginate(10);
        }

        $tipoPagos           = Tipo_pago::where('tipo_pago_estado', 1)->get();
        $idEmpresaActiva     = $this->resolverIdEmpresa();
        $clientes            = $idEmpresaActiva
            ? Cliente::where('cliente_estado', 1)->where('id_empresa', $idEmpresaActiva)->get()
            : collect();
        $empresas            = $esSuperAdmin
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')->get()
            : collect();
        $sucursalesDisponibles = collect($this->sucursalesDisponibles);

        return view('livewire.gestion-ventas.registrar-pagos', compact(
            'registros', 'tipoPagos', 'clientes',
            'empresas', 'sucursalesDisponibles',
            'esSuperAdmin', 'esAdmin', 'idEmpresaActiva'
        ));
    }
}
