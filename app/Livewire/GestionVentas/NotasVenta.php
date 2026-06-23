<?php

namespace App\Livewire\GestionVentas;

use App\Models\Cliente;
use App\Models\General;
use App\Models\Logs;
use App\Models\Ventas;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class NotasVenta extends Component
{
    use WithPagination, WithoutUrlPagination;

    protected $paginationTheme = 'bootstrap';

    // ── Rol y contexto ────────────────────────────────────────
    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    // ── Servicios privados ────────────────────────────────────
    private $logs;
    private $general;
    private $ventas;

    public function boot(): void
    {
        $this->logs    = new Logs();
        $this->general = new General();
        $this->ventas  = new Ventas();

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

    private function resolverIdSucursal(): int
    {
        if (!$this->esSuperAdmin() && !$this->esAdmin()) {
            return (int) session('sucursal_activa_id', 0);
        }
        return $this->sucursalSeleccionada;
    }

    // ── Filtros de búsqueda ───────────────────────────────────
    #[Validate('nullable')]
    #[Validate('date', message: 'La fecha "Desde" no tiene un formato válido.')]
    public $desde;

    #[Validate('nullable')]
    #[Validate('date', message: 'La fecha "Hasta" no tiene un formato válido.')]
    #[Validate('after_or_equal:desde', message: 'La fecha "Hasta" debe ser igual o posterior a la fecha "Desde".')]
    public $hasta;

    #[Validate('nullable|integer|min:1|exists:clientes,id_clientes', message: 'El cliente seleccionado no es válido.')]
    public $idCliente;

    public $idVenta;
    public $buscar;

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
        abort_if(!auth()->user()->can('historial_notas_venta.listar'), 403);

        $this->buscar    = false;
        $this->idCliente = null;
        $this->desde     = now()->format('Y-m-d');
        $this->hasta     = now()->format('Y-m-d');

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

    public function listarRegistros(): void
    {
        $this->validateOnly('desde');
        $this->validateOnly('hasta');
        $this->validateOnly('idCliente');
        $this->resetPage();
        $this->buscar = true;
    }

    public function ponerIdAnularNotaVenta($idVentaNotaVenta): void
    {
        try {
            $this->idVenta = $idVentaNotaVenta;
            $this->resetValidation();
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('errorAnular', 'Ocurrió un error al preparar la anulación.');
        }
    }

    public function anularNotaVenta(): void
    {
        try {
            if (!auth()->user()->can('historial_notas_venta.cambiar_estado')) {
                session()->flash('errorAnular', 'No tienes permiso para anular notas de venta.');
                return;
            }

            DB::beginTransaction();

            $ventaAnular = Ventas::find($this->idVenta);
            if (!$ventaAnular) {
                DB::rollBack();
                session()->flash('errorAnular', 'No se encontró la nota de venta seleccionada.');
                return;
            }

            $ventaAnular->anulado_sunat  = 1;
            $ventaAnular->venta_cancelar = 0;

            if (!$ventaAnular->save()) {
                DB::rollBack();
                session()->flash('errorAnular', 'No fue posible registrar la anulación de la nota de venta.');
                return;
            }

            $detalleVenta = $this->ventas->listar_venta_detalle_x_id_venta($this->idVenta);
            if (empty($detalleVenta)) {
                DB::rollBack();
                session()->flash('errorAnular', 'La nota de venta fue marcada como anulada, pero ocurrió un problema al recuperar el detalle.');
                return;
            }

            $this->general->actualizarStockPorDetalle($detalleVenta, 'sumar');

            DB::commit();
            session()->flash('success', 'La nota de venta fue anulada correctamente.');
            $this->dispatch('hidemodal');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('errorAnular', 'Ocurrió un error inesperado al intentar anular la nota de venta.');
        }
    }

    // ── Exportaciones ─────────────────────────────────────────

    public function imprimirPdf(): void
    {
        try {
            if (!auth()->user()->can('historial_notas_venta.exportar')) {
                session()->flash('error', 'No tienes permiso para exportar reportes.');
                return;
            }

            $this->dispatch('abrirEnlaces', url: route('Gestionventas.imprimir_pdf_reporte_historial_notas_venta', [
                'desde'       => $this->desde,
                'hasta'       => $this->hasta,
                'cliente'     => $this->idCliente,
                'idSucursal'  => $this->resolverIdSucursal() ?: null,
                'idEmpresa'   => $this->esSuperAdmin() ? ($this->empresaSeleccionada ?: null) : null,
            ]));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function imprimirExcel(): void
    {
        try {
            if (!auth()->user()->can('historial_notas_venta.exportar')) {
                session()->flash('error', 'No tienes permiso para exportar reportes.');
                return;
            }

            $this->dispatch('abrirEnlaces', url: route('Gestionventas.imprimir_excel_historial_notas_de_venta', [
                'desde'       => $this->desde,
                'hasta'       => $this->hasta,
                'cliente'     => $this->idCliente,
                'idSucursal'  => $this->resolverIdSucursal() ?: null,
                'idEmpresa'   => $this->esSuperAdmin() ? ($this->empresaSeleccionada ?: null) : null,
            ]));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
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
                    'mo.simbolo',
                    'u.nombre_users',
                    'c.id_tipo_documento',
                    'c.cliente_nombre',
                    'c.cliente_razonsocial',
                    'c.cliente_numero'
                )
                ->join('clientes as c', 'v.id_clientes', '=', 'c.id_clientes')
                ->join('monedas as mo', 'v.id_moneda', '=', 'mo.id_moneda')
                ->join('users as u', 'v.id_users', '=', 'u.id_users')
                ->where('v.venta_tipo', '=', '20');

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
            if ($this->desde && $this->hasta) {
                $query->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$this->desde, $this->hasta]);
            }
            if ($this->idCliente) {
                $query->where('v.id_clientes', $this->idCliente);
            }

            $registros = $query->orderByDesc('v.venta_fecha')->paginate(10);
        }

        $idEmpresaActiva       = $this->resolverIdEmpresa();
        $clientes              = $idEmpresaActiva
            ? Cliente::where('cliente_estado', 1)->where('id_empresa', $idEmpresaActiva)->get()
            : collect();
        $empresas              = $esSuperAdmin
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')->get()
            : collect();
        $sucursalesDisponibles = collect($this->sucursalesDisponibles);
        $role_id               = $this->cachedRoleId;

        return view('livewire.gestion-ventas.notas-venta', compact(
            'clientes', 'registros',
            'empresas', 'sucursalesDisponibles',
            'esSuperAdmin', 'esAdmin', 'role_id', 'idEmpresaActiva'
        ));
    }
}
