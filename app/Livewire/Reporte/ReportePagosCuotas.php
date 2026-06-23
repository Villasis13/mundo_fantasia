<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class ReportePagosCuotas extends Component
{
    use WithPagination;

    // ── Rol y contexto ────────────────────────────────────────
    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    // ── Servicios privados ────────────────────────────────────
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

    // ── Helpers de rol ────────────────────────────────────────
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
        $idSucursal = (int) session('sucursal_activa_id', 0);
        if (!$idSucursal) return null;
        $id = DB::table('sucursals')->where('id_sucursal', $idSucursal)->value('id_empresa');
        return $id ? (int) $id : null;
    }

    private function resolverIdSucursal(): int
    {
        if ($this->esSuperAdmin() || $this->esAdmin()) {
            return $this->sucursalSeleccionada;
        }
        return (int) session('sucursal_activa_id', 0);
    }

    // ── Lifecycle hooks ───────────────────────────────────────

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        $this->limpiarCliente();
        $this->buscar               = false;
        $this->resetPage();

        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('sucursals')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('sucursal_estado', 1)
                ->whereNull('deleted_at')
                ->orderBy('sucursal_nombre')
                ->get()
            : [];
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->limpiarCliente();
        $this->buscar = false;
        $this->resetPage();
    }

    // ── Filtros ───────────────────────────────────────────────
    #[Validate(['required', 'date'], message: [
        'required' => 'La fecha "Desde" es obligatoria.',
        'date'     => 'Formato de fecha inválido.',
    ])]
    public $desde;

    #[Validate(['required', 'date', 'after_or_equal:desde'], message: [
        'required'       => 'La fecha "Hasta" es obligatoria.',
        'date'           => 'Formato de fecha inválido.',
        'after_or_equal' => 'La fecha "Hasta" debe ser igual o posterior a "Desde".',
    ])]
    public $hasta;

    public string $idCliente           = '';
    public string $buscarCliente      = '';
    public        $clienteSeleccionado = null;
    public bool   $mostrarListaCliente = false;
    public string $estado              = 'todos';
    public bool   $buscar     = false;

    // ── Paginación ────────────────────────────────────────────
    public int $porPagina      = 10;
    public string $ordenColumna   = 'venta_cuota_fecha';
    public string $ordenDireccion = 'asc';

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reporte_caja.listar'), 403);

        $this->desde = now()->startOfMonth()->format('Y-m-d');
        $this->hasta = now()->format('Y-m-d');

        if ($this->esAdmin()) {
            $empresaId = $this->adminEmpresaId();
            if ($empresaId) {
                $this->sucursalesDisponibles = DB::table('sucursals')
                    ->where('id_empresa', $empresaId)
                    ->where('sucursal_estado', 1)
                    ->whereNull('deleted_at')
                    ->orderBy('sucursal_nombre')
                    ->get();

                if (collect($this->sucursalesDisponibles)->count() === 1) {
                    $this->sucursalSeleccionada = collect($this->sucursalesDisponibles)->first()->id_sucursal;
                }
            }
        }
    }

    // ── Buscador de cliente (patrón ubigeo) ──────────────
    public function updatingBuscarCliente(): void
    {
        $this->mostrarListaCliente = true;
    }

    public function seleccionarCliente(int $idCliente, string $label): void
    {
        $this->idCliente           = (string) $idCliente;
        $this->clienteSeleccionado = ['id' => $idCliente, 'label' => $label];
        $this->buscarCliente       = '';
        $this->mostrarListaCliente = false;
        $this->resetPage();
    }

    public function limpiarCliente(): void
    {
        $this->idCliente           = '';
        $this->clienteSeleccionado = null;
        $this->buscarCliente       = '';
        $this->mostrarListaCliente = false;
        $this->resetPage();
    }

    public function listarRegistros(): void
    {
        $this->validateOnly('desde');
        $this->validateOnly('hasta');
        $this->buscar = true;
        $this->resetPage();
    }

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

    public function updatingEstado(): void     { $this->resetPage(); }
    public function updatingPorPagina(): void  { $this->resetPage(); }

    // ── Exportables ───────────────────────────────────────────

    public function imprimirPdf(): void
    {
        try {
            if (!auth()->user()->can('reporte_caja.exportar')) {
                session()->flash('errorGeneral', 'Acceso denegado.');
                return;
            }
            $url = route('reporte.imprimir_pdf_pagos_cuotas', $this->buildExportableParams());
            $this->dispatch('abrirEnlaces', url: $url);
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function imprimirExcel(): void
    {
        try {
            if (!auth()->user()->can('reporte_caja.exportar')) {
                session()->flash('errorGeneral', 'Acceso denegado.');
                return;
            }
            $url = route('reporte.imprimir_excel_pagos_cuotas', $this->buildExportableParams());
            $this->dispatch('abrirEnlaces', url: $url);
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    private function buildExportableParams(): array
    {
        return [
            'desde'       => $this->desde,
            'hasta'       => $this->hasta,
            'cliente'     => $this->idCliente,
            'estado'      => $this->estado,
            'id_empresa'  => $this->resolverIdEmpresa()  ?? 0,
            'id_sucursal' => $this->resolverIdSucursal(),
        ];
    }

    // ── Render ────────────────────────────────────────────────

    public function render()
    {
        $esSuperAdmin    = $this->esSuperAdmin();
        $esAdmin         = $this->esAdmin();
        $idEmpresaActiva = $this->resolverIdEmpresa();
        $idSucursal      = $this->resolverIdSucursal();

        $empresas = $esSuperAdmin
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')->get()
            : collect();

        // Clientes filtrados por empresa y sucursal para el buscador
        $clientes = collect();
        if ($idEmpresaActiva) {
            $queryClientes = DB::table('clientes as c')
                ->select('c.id_clientes', 'c.cliente_nombre', 'c.cliente_razonsocial', 'c.cliente_numero', 'c.id_tipo_documento')
                ->join('ventas as v', 'v.id_clientes', '=', 'c.id_clientes')
                ->where('c.cliente_estado', 1)
                ->where('v.id_empresa', $idEmpresaActiva);

            if ($idSucursal) {
                $queryClientes->where('v.id_sucursal', $idSucursal);
            }

            // Mostrar 10 primeros al hacer focus; filtrar cuando hay texto
            if ($this->buscarCliente !== '') {
                $queryClientes->where(function ($q) {
                    $q->where('c.cliente_nombre', 'like', '%' . $this->buscarCliente . '%')
                        ->orWhere('c.cliente_razonsocial', 'like', '%' . $this->buscarCliente . '%')
                        ->orWhere('c.cliente_numero', 'like', '%' . $this->buscarCliente . '%');
                });
            }

            $clientes = $queryClientes->distinct()->orderBy('c.cliente_nombre')->limit(10)->get();
        }

        $cuotas  = collect();
        $resumen = null;

        if ($this->buscar && $idEmpresaActiva) {
            $hoy = Carbon::today()->toDateString();

            $columnasPermitidas = [
                'venta_cuota_fecha', 'venta_cuota_importe',
                'venta_cuota_numero', 'cliente_nombre',
            ];
            $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'venta_cuota_fecha';
            $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

            $query = DB::table('ventas_cuotas as vc')
                ->select(
                    'vc.id_ventas_cuotas',
                    'vc.venta_cuota_numero',
                    'vc.venta_cuota_importe',
                    'vc.venta_cuota_fecha',
                    'vc.venta_cuota_pago',
                    'vc.venta_cuota_estado',
                    'v.id_venta',
                    'v.venta_serie',
                    'v.venta_correlativo',
                    'v.venta_tipo',
                    'c.id_clientes',
                    'c.cliente_nombre',
                    'c.cliente_razonsocial',
                    'c.cliente_numero',
                    'c.id_tipo_documento',
                    DB::raw('COALESCE((
                        SELECT SUM(pc2.pagos_cuota_monto)
                        FROM pagos_cuotas pc2
                        WHERE pc2.id_ventas_cuotas = vc.id_ventas_cuotas
                        AND pc2.deleted_at IS NULL
                    ), 0) as total_pagado'),
                    DB::raw('(
                        SELECT pc3.pagos_cuota_fecha
                        FROM pagos_cuotas pc3
                        WHERE pc3.id_ventas_cuotas = vc.id_ventas_cuotas
                        AND pc3.deleted_at IS NULL
                        ORDER BY pc3.pagos_cuota_fecha DESC
                        LIMIT 1
                    ) as ultimo_pago'),
                    DB::raw('(
                        SELECT tp.tipo_pago_nombre
                        FROM pagos_cuotas pc4
                        JOIN tipo_pago tp ON tp.id_tipo_pago = pc4.id_tipo_pago
                        WHERE pc4.id_ventas_cuotas = vc.id_ventas_cuotas
                        AND pc4.deleted_at IS NULL
                        ORDER BY pc4.pagos_cuota_fecha DESC
                        LIMIT 1
                    ) as tipo_pago_nombre')
                )
                ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
                ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
                ->where('vc.venta_cuota_estado', 1)
                ->where('v.id_empresa', $idEmpresaActiva)
                ->whereBetween('vc.venta_cuota_fecha', [$this->desde, $this->hasta]);

            if ($idSucursal) {
                $query->where('v.id_sucursal', $idSucursal);
            }

            if ($this->idCliente) {
                $query->where('v.id_clientes', $this->idCliente);
            }

            switch ($this->estado) {
                case 'pagadas':
                    $query->where('vc.venta_cuota_pago', 1);
                    break;
                case 'vencidas':
                    $query->where('vc.venta_cuota_pago', 0)
                        ->where('vc.venta_cuota_fecha', '<', $hoy);
                    break;
                case 'por_vencer':
                    $query->where('vc.venta_cuota_pago', 0)
                        ->whereBetween('vc.venta_cuota_fecha', [$hoy, Carbon::today()->addDays(7)->toDateString()]);
                    break;
                case 'pendientes':
                    $query->where('vc.venta_cuota_pago', 0);
                    break;
            }

            $cuotas = $query
                ->orderBy($columna === 'cliente_nombre' ? 'c.cliente_nombre' : "vc.{$columna}", $direccion)
                ->paginate($this->porPagina);

            // ── Resumen ───────────────────────────────────────
            $queryResumen = DB::table('ventas_cuotas as vc')
                ->select(
                    DB::raw('SUM(CASE WHEN vc.venta_cuota_pago = 1 THEN vc.venta_cuota_importe ELSE 0 END) as total_pagado'),
                    DB::raw('SUM(CASE WHEN vc.venta_cuota_pago = 0 AND vc.venta_cuota_fecha < ? THEN vc.venta_cuota_importe ELSE 0 END) as total_vencido'),
                    DB::raw('SUM(CASE WHEN vc.venta_cuota_pago = 0 THEN vc.venta_cuota_importe ELSE 0 END) as total_pendiente'),
                    DB::raw('COUNT(CASE WHEN vc.venta_cuota_pago = 1 THEN 1 END) as cant_pagadas'),
                    DB::raw('COUNT(CASE WHEN vc.venta_cuota_pago = 0 AND vc.venta_cuota_fecha < ? THEN 1 END) as cant_vencidas'),
                    DB::raw('COUNT(CASE WHEN vc.venta_cuota_pago = 0 THEN 1 END) as cant_pendientes'),
                )
                ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
                ->where('vc.venta_cuota_estado', 1)
                ->where('v.id_empresa', $idEmpresaActiva)
                ->whereBetween('vc.venta_cuota_fecha', [$this->desde, $this->hasta])
                ->addBinding([$hoy, $hoy], 'select');

            if ($idSucursal) {
                $queryResumen->where('v.id_sucursal', $idSucursal);
            }

            if ($this->idCliente) {
                $queryResumen->where('v.id_clientes', $this->idCliente);
            }

            $resumen = $queryResumen->first();
        }

        return view('livewire.reporte.reporte-pagos-cuotas', compact(
            'esSuperAdmin', 'esAdmin', 'idEmpresaActiva',
            'empresas', 'clientes',
            'cuotas', 'resumen'
        ));
    }
}
