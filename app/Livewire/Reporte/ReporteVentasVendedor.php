<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class ReporteVentasVendedor extends Component
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

    private function queryVendedores(): \Illuminate\Support\Collection
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        if (!$idEmpresa) return collect();

        $query = DB::table('users as u')
            ->select('u.id_users', 'u.nombre_users')
            ->join('user_tienda as ut', 'ut.id_users', '=', 'u.id_users')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id_users')
            ->where('mr.role_id', 5)
            ->where('u.users_estado', 1)
            ->where('t.id_empresa', $idEmpresa);

        if ($idSucursal > 0) {
            $query->where('ut.id_tienda', $idSucursal);
        }

        return $query->distinct()->orderBy('u.nombre_users')->get();
    }

    // ── Lifecycle hooks ───────────────────────────────────────

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        $this->idVendedor           = '';
        $this->buscar               = false;
        $this->vendedorSeleccionado = null;
        $this->resetPage();

        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get()
            : [];
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->idVendedor           = '';
        $this->buscar               = false;
        $this->vendedorSeleccionado = null;
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

    public string $idVendedor = '';
    public bool   $buscar     = false;

    // ── Vista detalle ─────────────────────────────────────────
    public $vendedorSeleccionado  = null;
    public string $nombreVendedorDetalle = '';

    // ── Paginación detalle ────────────────────────────────────
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

    public function listarRegistros(): void
    {
        $this->validateOnly('desde');
        $this->validateOnly('hasta');
        $this->buscar               = true;
        $this->vendedorSeleccionado = null;
        $this->resetPage();
    }

    public function verDetalle($idVendedor, string $nombre): void
    {
        $this->vendedorSeleccionado  = $idVendedor;
        $this->nombreVendedorDetalle = $nombre;
        $this->resetPage();
    }

    public function cerrarDetalle(): void
    {
        $this->vendedorSeleccionado  = null;
        $this->nombreVendedorDetalle = '';
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
            $url = route('reporte.imprimir_pdf_ventas_vendedor', $this->buildExportableParams());
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
            $url = route('reporte.imprimir_excel_ventas_vendedor', $this->buildExportableParams());
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
            'vendedor'    => $this->idVendedor,
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

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('id_empresa')->get()
            : collect();

        // Vendedores filtrados por empresa/sucursal para el select
        $vendedores = $idEmpresaActiva ? $this->queryVendedores() : collect();

        $resumenVendedores = collect();
        $detalleVentas     = collect();
        $totalesResumen    = (object)[
            'total_ventas'   => 0, 'cantidad'       => 0,
            'total_facturas' => 0, 'total_boletas'  => 0, 'total_nv' => 0,
        ];

        if ($this->buscar && $idEmpresaActiva) {

            // ── Resumen por vendedor ──────────────────────────
            $queryResumen = DB::table('ventas as v')
                ->select(
                    'u.id_users',
                    'u.nombre_users',
                    DB::raw('SUM(v.venta_total) as total_ventas'),
                    DB::raw('COUNT(v.id_venta) as cantidad'),
                    DB::raw("SUM(CASE WHEN v.venta_tipo = '01' THEN v.venta_total ELSE 0 END) as total_facturas"),
                    DB::raw("SUM(CASE WHEN v.venta_tipo = '03' THEN v.venta_total ELSE 0 END) as total_boletas"),
                    DB::raw("SUM(CASE WHEN v.venta_tipo = '20' THEN v.venta_total ELSE 0 END) as total_nv"),
                    DB::raw("COUNT(CASE WHEN v.venta_tipo = '01' THEN 1 END) as cant_facturas"),
                    DB::raw("COUNT(CASE WHEN v.venta_tipo = '03' THEN 1 END) as cant_boletas"),
                    DB::raw("COUNT(CASE WHEN v.venta_tipo = '20' THEN 1 END) as cant_nv"),
                )
                ->join('users as u', 'u.id_users', '=', 'v.id_users')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')
                ->whereIn('v.venta_tipo', ['01', '03', '20'])
                ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$this->desde, $this->hasta]);

            $this->aplicarFiltroUbicacion($queryResumen);

            if ($this->idVendedor) {
                $queryResumen->where('v.id_users', $this->idVendedor);
            }

            $resumenVendedores = $queryResumen
                ->groupBy('u.id_users', 'u.nombre_users')
                ->orderByDesc('total_ventas')
                ->get();

            $totalesResumen = (object)[
                'total_ventas'   => $resumenVendedores->sum('total_ventas'),
                'cantidad'       => $resumenVendedores->sum('cantidad'),
                'total_facturas' => $resumenVendedores->sum('total_facturas'),
                'total_boletas'  => $resumenVendedores->sum('total_boletas'),
                'total_nv'       => $resumenVendedores->sum('total_nv'),
            ];

            // ── Detalle ventas del vendedor seleccionado ──────
            if ($this->vendedorSeleccionado) {
                $columnasPermitidas = ['venta_fecha', 'venta_total', 'venta_tipo', 'cliente_nombre'];
                $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'venta_fecha';
                $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

                $queryDetalle = DB::table('ventas as v')
                    ->select(
                        'v.id_venta', 'v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo',
                        'v.venta_fecha', 'v.venta_total', 'v.id_formas_pago',
                        'c.cliente_nombre', 'c.cliente_razonsocial', 'c.cliente_numero',
                        'c.id_tipo_documento', 'mo.simbolo',
                    )
                    ->join('clientes as c',  'c.id_clientes', '=', 'v.id_clientes')
                    ->join('monedas as mo',  'mo.id_moneda',  '=', 'v.id_moneda')
                    ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                    ->whereNull('va.id_venta')
                    ->whereIn('v.venta_tipo', ['01', '03', '20'])
                    ->where('v.id_users', $this->vendedorSeleccionado)
                    ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$this->desde, $this->hasta]);

                $this->aplicarFiltroUbicacion($queryDetalle);

                $detalleVentas = $queryDetalle
                    ->orderBy($columna === 'cliente_nombre' ? 'c.cliente_nombre' : "v.{$columna}", $direccion)
                    ->paginate($this->porPagina);
            }
        }

        return view('livewire.reporte.reporte-ventas-vendedor', compact(
            'esSuperAdmin', 'esAdmin', 'idEmpresaActiva',
            'empresas', 'vendedores',
            'resumenVendedores', 'detalleVentas', 'totalesResumen'
        ));
    }
}
