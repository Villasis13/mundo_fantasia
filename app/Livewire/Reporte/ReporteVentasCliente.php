<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class ReporteVentasCliente extends Component
{
    use WithPagination;

    // ── Rol y contexto ────────────────────────────────────────
    private int $cachedRoleId        = 0;
    public int  $empresaSeleccionada = 0;
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
        if ($this->esSuperAdmin() || $this->esAdmin()) {
            return $this->sucursalSeleccionada;
        }
        $id = DB::table('user_tienda as ut')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('ut.id_tienda');
        return $id ? (int) $id : 0;
    }

    private function aplicarFiltroUbicacion(\Illuminate\Database\Query\Builder $query): void
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        if ($idSucursal > 0) {
            $query->where('v.id_sucursal', $idSucursal);
        } elseif ($idEmpresa) {
            $query->where('v.id_empresa', $idEmpresa);
        }
    }

    // ── Lifecycle hooks ───────────────────────────────────────

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada  = 0;
        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get()
            : [];
        $this->limpiarCliente();
        $this->buscar              = false;
        $this->clienteSeleccionado = null;
        $this->resetPage();
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->limpiarCliente();
        $this->buscar              = false;
        $this->clienteSeleccionado = null;
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
    public string $buscarCliente       = '';
    public        $clienteSeleccionadoBuscador = null; // chip del buscador
    public bool   $mostrarListaCliente = false;
    public bool   $buscar              = false;

    // ── Vista detalle ─────────────────────────────────────────
    public $clienteSeleccionado  = null; // id para ver detalle en tabla
    public string $nombreClienteDetalle = '';
    public string $tabDetalle           = 'ventas';

    // ── Paginación ────────────────────────────────────────────
    public int    $porPagina      = 10;
    public string $ordenColumna   = 'venta_fecha';
    public string $ordenDireccion = 'desc';

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reporte_ventas.listar'), 403);

        $this->desde = now()->startOfMonth()->format('Y-m-d');
        $this->hasta = now()->format('Y-m-d');

        $empresaId = $this->empresaUsuario();
        if ($empresaId) {
            $this->empresaSeleccionada   = $empresaId;
            $this->sucursalesDisponibles = DB::table('tiendas')
                ->where('id_empresa', $empresaId)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get();
        }
    }

    // ── Buscador de cliente (patrón ubigeo) ───────────────────

    public function updatingBuscarCliente(): void
    {
        $this->mostrarListaCliente = true;
    }

    public function seleccionarCliente(int $idCliente, string $label): void
    {
        $this->idCliente                  = (string) $idCliente;
        $this->clienteSeleccionadoBuscador = ['id' => $idCliente, 'label' => $label];
        $this->buscarCliente              = '';
        $this->mostrarListaCliente        = false;
        $this->resetPage();
    }

    public function limpiarCliente(): void
    {
        $this->idCliente                  = '';
        $this->clienteSeleccionadoBuscador = null;
        $this->buscarCliente              = '';
        $this->mostrarListaCliente        = false;
        $this->resetPage();
    }

    // ── Acciones principales ──────────────────────────────────

    public function listarRegistros(): void
    {
        $this->validateOnly('desde');
        $this->validateOnly('hasta');
        $this->buscar              = true;
        $this->clienteSeleccionado = null;
        $this->resetPage();
    }

    public function verDetalle($idCliente, string $nombre): void
    {
        $this->clienteSeleccionado  = $idCliente;
        $this->nombreClienteDetalle = $nombre;
        $this->tabDetalle           = 'ventas';
        $this->resetPage();
    }

    public function cerrarDetalle(): void
    {
        $this->clienteSeleccionado  = null;
        $this->nombreClienteDetalle = '';
        $this->resetPage();
    }

    public function cambiarTab(string $tab): void
    {
        $this->tabDetalle = $tab;
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

    public function updatingPorPagina(): void { $this->resetPage(); }

    // ── Exportables ───────────────────────────────────────────

    public function imprimirPdf(): void
    {
        try {
            if (!auth()->user()->can('reporte_ventas.exportar')) {
                session()->flash('errorGeneral', 'Acceso denegado.');
                return;
            }
            $url = route('reporte.imprimir_pdf_ventas_cliente', $this->buildExportableParams());
            $this->dispatch('abrirEnlaces', url: $url);
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function imprimirExcel(): void
    {
        try {
            if (!auth()->user()->can('reporte_ventas.exportar')) {
                session()->flash('errorGeneral', 'Acceso denegado.');
                return;
            }
            $url = route('reporte.imprimir_excel_ventas_cliente', $this->buildExportableParams());
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
            'id_empresa'  => $this->resolverIdEmpresa() ?? 0,
            'id_sucursal' => $this->resolverIdSucursal(),
        ];
    }

    // ── Render ────────────────────────────────────────────────

    public function render()
    {
        $esSuperAdmin    = $this->esSuperAdmin();
        $esAdmin         = $this->esAdmin();
        $idEmpresaActiva = $this->resolverIdEmpresa();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')->get()
            : collect();

        // Clientes filtrados por empresa para el buscador (10 primeros o por texto)
        $clientesBuscador = collect();
        if ($idEmpresaActiva) {
            $idSucursalActiva = $this->resolverIdSucursal();
            $queryClientes = DB::table('clientes as c')
                ->select('c.id_clientes', 'c.cliente_nombre', 'c.cliente_razonsocial',
                    'c.cliente_numero', 'c.id_tipo_documento')
                ->join('ventas as v', 'v.id_clientes', '=', 'c.id_clientes')
                ->where('c.cliente_estado', 1);

            if ($idSucursalActiva > 0) {
                $queryClientes->where('v.id_sucursal', $idSucursalActiva);
            } else {
                $queryClientes->where('v.id_empresa', $idEmpresaActiva);
            }

            if ($this->buscarCliente !== '') {
                $queryClientes->where(function ($q) {
                    $q->where('c.cliente_nombre',      'like', '%' . $this->buscarCliente . '%')
                        ->orWhere('c.cliente_razonsocial','like', '%' . $this->buscarCliente . '%')
                        ->orWhere('c.cliente_numero',     'like', '%' . $this->buscarCliente . '%');
                });
            }

            $clientesBuscador = $queryClientes->distinct()->orderBy('c.cliente_nombre')->limit(10)->get();
        }

        $resumenClientes  = collect();
        $detalleVentas    = collect();
        $detalleProductos = collect();
        $deudaCliente     = collect();
        $totalesResumen   = (object)[
            'total_ventas'    => 0, 'cantidad'       => 0,
            'ticket_promedio' => 0, 'deuda_total'    => 0,
        ];

        if ($this->buscar && $idEmpresaActiva) {

            // ── Resumen por cliente ───────────────────────────
            $queryResumen = DB::table('ventas as v')
                ->select(
                    'c.id_clientes',
                    'c.cliente_nombre',
                    'c.cliente_razonsocial',
                    'c.cliente_numero',
                    'c.id_tipo_documento',
                    DB::raw('SUM(v.venta_total) as total_ventas'),
                    DB::raw('COUNT(v.id_venta) as cantidad'),
                    DB::raw('ROUND(AVG(v.venta_total), 2) as ticket_promedio'),
                    DB::raw("SUM(CASE WHEN v.venta_tipo = '01' THEN v.venta_total ELSE 0 END) as total_facturas"),
                    DB::raw("SUM(CASE WHEN v.venta_tipo = '03' THEN v.venta_total ELSE 0 END) as total_boletas"),
                    DB::raw("SUM(CASE WHEN v.venta_tipo = '20' THEN v.venta_total ELSE 0 END) as total_nv"),
                    DB::raw('COALESCE((
                        SELECT SUM(vc2.venta_cuota_importe)
                        FROM ventas_cuotas vc2
                        JOIN ventas v2 ON v2.id_venta = vc2.id_venta
                        WHERE v2.id_clientes = c.id_clientes
                          AND v2.id_empresa  = ' . intval($idEmpresaActiva) . '
                          AND vc2.venta_cuota_pago   = 0
                          AND vc2.venta_cuota_estado = 1
                    ), 0) as deuda_pendiente'),
                )
                ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')
                ->whereIn('v.venta_tipo', ['01', '03', '20'])
                ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$this->desde, $this->hasta]);

            $this->aplicarFiltroUbicacion($queryResumen);

            if ($this->idCliente) {
                $queryResumen->where('c.id_clientes', $this->idCliente);
            }

            $resumenClientes = $queryResumen
                ->groupBy('c.id_clientes', 'c.cliente_nombre', 'c.cliente_razonsocial',
                    'c.cliente_numero', 'c.id_tipo_documento')
                ->orderByDesc('total_ventas')
                ->get();

            $totalesResumen = (object)[
                'total_ventas'    => $resumenClientes->sum('total_ventas'),
                'cantidad'        => $resumenClientes->sum('cantidad'),
                'ticket_promedio' => $resumenClientes->sum('cantidad') > 0
                    ? round($resumenClientes->sum('total_ventas') / $resumenClientes->sum('cantidad'), 2)
                    : 0,
                'deuda_total'     => $resumenClientes->sum('deuda_pendiente'),
            ];

            // ── Detalle del cliente seleccionado ──────────────
            if ($this->clienteSeleccionado) {

                $columnasPermitidas = ['venta_fecha', 'venta_total', 'venta_tipo'];
                $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'venta_fecha';
                $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

                // Tab: Ventas
                if ($this->tabDetalle === 'ventas') {
                    $queryDetalle = DB::table('ventas as v')
                        ->select(
                            'v.id_venta', 'v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo',
                            'v.venta_fecha', 'v.venta_total', 'v.id_formas_pago',
                            'u.nombre_users', 'mo.simbolo',
                        )
                        ->join('users as u',    'u.id_users',   '=', 'v.id_users')
                        ->join('monedas as mo', 'mo.id_moneda', '=', 'v.id_moneda')
                        ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                        ->whereNull('va.id_venta')
                        ->whereIn('v.venta_tipo', ['01', '03', '20'])
                        ->where('v.id_clientes', $this->clienteSeleccionado)
                        ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$this->desde, $this->hasta]);

                    $this->aplicarFiltroUbicacion($queryDetalle);

                    $detalleVentas = $queryDetalle
                        ->orderBy("v.{$columna}", $direccion)
                        ->paginate($this->porPagina);
                }

                // Tab: Productos
                if ($this->tabDetalle === 'productos') {
                    $queryProductos = DB::table('ventas_detalle as vd')
                        ->select(
                            'p.pro_nombre', 'p.pro_codigo', 'ca.ca_nombre',
                            DB::raw('SUM(vd.venta_detalle_cantidad) as total_cantidad'),
                            DB::raw('SUM(vd.venta_detalle_importe_total) as total_importe'),
                            DB::raw('COUNT(DISTINCT v.id_venta) as en_comprobantes'),
                            DB::raw('ROUND(AVG(vd.venta_detalle_precio_unitario), 2) as precio_promedio'),
                        )
                        ->join('ventas as v',      'v.id_venta', '=', 'vd.id_venta')
                        ->join('productos as p',   'p.id_pro',   '=', 'vd.id_pro')
                        ->join('categorias as ca', 'ca.id_ca',   '=', 'p.id_ca')
                        ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                        ->whereNull('va.id_venta')
                        ->where('v.id_clientes', $this->clienteSeleccionado)
                        ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$this->desde, $this->hasta]);

                    $this->aplicarFiltroUbicacion($queryProductos);

                    $detalleProductos = $queryProductos
                        ->groupBy('p.id_pro', 'p.pro_nombre', 'p.pro_codigo', 'ca.ca_nombre')
                        ->orderByDesc('total_importe')
                        ->paginate($this->porPagina);
                }

                // Tab: Deuda (sin filtro de fecha, pero sí por empresa)
                if ($this->tabDetalle === 'deuda') {
                    $deudaCliente = DB::table('ventas_cuotas as vc')
                        ->select(
                            'vc.venta_cuota_numero', 'vc.venta_cuota_importe',
                            'vc.venta_cuota_fecha',  'vc.venta_cuota_pago',
                            'v.venta_serie', 'v.venta_correlativo', 'v.venta_tipo',
                            DB::raw('COALESCE((
                                SELECT SUM(pc.pagos_cuota_monto)
                                FROM pagos_cuotas pc
                                WHERE pc.id_ventas_cuotas = vc.id_ventas_cuotas
                                AND pc.deleted_at IS NULL
                            ), 0) as total_pagado'),
                        )
                        ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
                        ->where('v.id_clientes', $this->clienteSeleccionado)
                        ->when($idSucursalActiva > 0,
                            fn($q) => $q->where('v.id_sucursal', $idSucursalActiva),
                            fn($q) => $q->where('v.id_empresa', $idEmpresaActiva)
                        )
                        ->where('vc.venta_cuota_estado', 1)
                        ->orderBy('vc.venta_cuota_fecha', 'asc')
                        ->paginate($this->porPagina);
                }
            }
        }

        $sucursalesDisponibles = $this->sucursalesDisponibles;

        return view('livewire.reporte.reporte-ventas-cliente', compact(
            'esSuperAdmin', 'esAdmin', 'idEmpresaActiva',
            'empresas', 'sucursalesDisponibles', 'clientesBuscador',
            'resumenClientes', 'detalleVentas',
            'detalleProductos', 'deudaCliente', 'totalesResumen'
        ));
    }
}
