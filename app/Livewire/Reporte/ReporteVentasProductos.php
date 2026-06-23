<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteVentasProductos extends Component
{
    use WithPagination;

    // ── Rol y contexto ────────────────────────────────────────
    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

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
        if ($this->esSuperAdmin() || $this->esAdmin()) {
            return $this->sucursalSeleccionada;
        }
        $id = DB::table('user_tienda as ut')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('ut.id_tienda');
        return $id ? (int) $id : 0;
    }

    // Empresa filtra por p.id_empresa, sucursal por v.id_sucursal
    private function aplicarFiltroUbicacion(\Illuminate\Database\Query\Builder $query): void
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        if ($idEmpresa) {
            $query->where('p.id_empresa', $idEmpresa);
        }
        if ($idSucursal > 0) {
            $query->where('v.id_sucursal', $idSucursal);
        }
    }

    // ── Lifecycle hooks ───────────────────────────────────────

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        $this->buscar               = false;
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

    public string $idCategoria = '';
    public int    $topN        = 10;
    public bool   $buscar      = false;

    public string $vistaActiva    = 'cantidad';
    public int    $porPagina      = 15;
    public string $ordenColumna   = 'total_cantidad';
    public string $ordenDireccion = 'desc';

    public function mount(): void
    {
        abort_if(!auth()->user()->can('productos_mas_vendidos.listar'), 403);

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
        $this->buscar = true;
        $this->resetPage();
    }

    public function cambiarVista(string $vista): void
    {
        $this->vistaActiva = $vista;
        $this->resetPage();
    }

    public function ordenar(string $columna): void
    {
        $this->ordenDireccion = $this->ordenColumna === $columna
            ? ($this->ordenDireccion === 'asc' ? 'desc' : 'asc')
            : 'asc';
        $this->ordenColumna = $columna;
        $this->resetPage();
    }

    public function updatingPorPagina(): void   { $this->resetPage(); }
    public function updatingIdCategoria(): void { $this->resetPage(); }

    // ── Exportables ───────────────────────────────────────────

    public function imprimirPdf(): void
    {
        try {
            if (!auth()->user()->can('productos_mas_vendidos.exportar')) {
                session()->flash('errorGeneral', 'Acceso denegado.');
                return;
            }
            $this->dispatch('abrirEnlaces', url: route('reporte.imprimir_pdf_ventas_productos', $this->buildExportableParams()));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function imprimirExcel(): void
    {
        try {
            if (!auth()->user()->can('productos_mas_vendidos.exportar')) {
                session()->flash('errorGeneral', 'Acceso denegado.');
                return;
            }
            $this->dispatch('abrirEnlaces', url: route('reporte.imprimir_excel_ventas_productos', $this->buildExportableParams()));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    private function buildExportableParams(): array
    {
        return [
            'desde'       => $this->desde,
            'hasta'       => $this->hasta,
            'categoria'   => $this->idCategoria,
            'top'         => $this->topN,
            'id_empresa'  => $this->resolverIdEmpresa()  ?? 0,
            'id_sucursal' => $this->resolverIdSucursal(),
        ];
    }

    // ── Query productos sin rotación en el periodo ───────────
    private function buildQuerySinRotacion(): \Illuminate\Database\Query\Builder
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        $vendidos = DB::table('ventas_detalle as vd')
            ->select('vd.id_pro')
            ->join('ventas as v', 'v.id_venta', '=', 'vd.id_venta')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$this->desde, $this->hasta]);

        if ($idEmpresa) {
            $vendidos->where('v.id_empresa', $idEmpresa);
        }
        if ($idSucursal > 0) {
            $vendidos->where('v.id_sucursal', $idSucursal);
        }

        $stockSubquery = $idSucursal > 0
            ? DB::raw('(SELECT COALESCE(ps2.ps_stock, 0)
                        FROM producto_sucursal ps2
                        WHERE ps2.id_pro = p.id_pro
                          AND ps2.id_sucursal = ' . $idSucursal . '
                        LIMIT 1) as stock_actual')
            : DB::raw('(SELECT COALESCE(SUM(ps2.ps_stock), 0)
                        FROM producto_sucursal ps2
                        JOIN tiendas t2 ON t2.id_tienda = ps2.id_sucursal
                        WHERE ps2.id_pro = p.id_pro
                          AND t2.id_empresa = ' . intval($idEmpresa) . ') as stock_actual');

        $query = DB::table('producto_sucursal as ps')
            ->select('p.id_pro', 'p.pro_nombre', 'p.pro_codigo', 'ca.ca_nombre', $stockSubquery)
            ->join('productos as p', 'p.id_pro', '=', 'ps.id_pro')
            ->join('categorias as ca', 'ca.id_ca', '=', 'p.id_ca')
            ->where('ps.ps_estado', 1)
            ->where('p.pro_estado', 1)
            ->whereNotIn('p.id_pro', (clone $vendidos)->distinct());

        if ($idEmpresa) {
            $query->where('p.id_empresa', $idEmpresa);
        }

        if ($idSucursal > 0) {
            $query->where('ps.id_sucursal', $idSucursal);
        } elseif ($idEmpresa) {
            $query->whereIn('ps.id_sucursal', function ($sub) use ($idEmpresa) {
                $sub->select('id_tienda')->from('tiendas')
                    ->where('id_empresa', $idEmpresa)
                    ->where('tienda_estado', 1);
            });
        }

        if ($this->idCategoria) {
            $query->where('p.id_ca', $this->idCategoria);
        }

        return $query->groupBy('p.id_pro', 'p.pro_nombre', 'p.pro_codigo', 'ca.ca_nombre');
    }

    // ── Query base ────────────────────────────────────────────
    // Stock resuelto con subquery:
    //   - Sucursal activa → stock de esa sucursal (producto_sucursal.ps_stock)
    //   - Solo empresa    → suma de stock en todas sus sucursales
    private function buildQueryBase(): \Illuminate\Database\Query\Builder
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        $stockSubquery = $idSucursal > 0
            ? DB::raw('(SELECT COALESCE(ps.ps_stock, 0)
                        FROM producto_sucursal ps
                        WHERE ps.id_pro = p.id_pro
                          AND ps.id_sucursal = ' . $idSucursal . '
                        LIMIT 1) as stock_actual')
            : DB::raw('(SELECT COALESCE(SUM(ps.ps_stock), 0)
                        FROM producto_sucursal ps
                        JOIN tiendas t ON t.id_tienda = ps.id_sucursal
                        WHERE ps.id_pro = p.id_pro
                          AND t.id_empresa = ' . intval($idEmpresa) . ') as stock_actual');

        $query = DB::table('ventas_detalle as vd')
            ->select(
                'p.id_pro',
                'p.pro_nombre',
                'p.pro_codigo',
                'ca.ca_nombre',
                $stockSubquery,
                DB::raw('SUM(vd.venta_detalle_cantidad) as total_cantidad'),
                DB::raw('SUM(vd.venta_detalle_importe_total) as total_monto'),
                DB::raw('COUNT(DISTINCT v.id_venta) as en_comprobantes'),
                DB::raw('ROUND(AVG(vd.venta_detalle_precio_unitario), 2) as precio_promedio'),
                DB::raw('ROUND(AVG(vd.venta_detalle_cantidad), 2) as cantidad_promedio'),
            )
            ->join('ventas as v',      'v.id_venta', '=', 'vd.id_venta')
            ->join('productos as p',   'p.id_pro',   '=', 'vd.id_pro')
            ->join('categorias as ca', 'ca.id_ca',   '=', 'p.id_ca')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$this->desde, $this->hasta]);

        $this->aplicarFiltroUbicacion($query);

        if ($this->idCategoria) {
            $query->where('p.id_ca', $this->idCategoria);
        }

        return $query->groupBy('p.id_pro', 'p.pro_nombre', 'p.pro_codigo', 'ca.ca_nombre');
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

        // Categorías filtradas por empresa activa
        $categorias = $idEmpresaActiva
            ? DB::table('categorias as ca')
                ->select('ca.id_ca', 'ca.ca_nombre')
                ->join('productos as p', 'p.id_ca', '=', 'ca.id_ca')
                ->where('ca.ca_estado', 1)
                ->where('p.id_empresa', $idEmpresaActiva)
                ->distinct()
                ->orderBy('ca.ca_nombre')
                ->get()
            : DB::table('categorias')->where('ca_estado', 1)->orderBy('ca_nombre')->get();

        $rankingCantidad  = collect();
        $rankingMonto     = collect();
        $bottomCantidad   = collect();
        $bottomMonto      = collect();
        $sinRotacion      = collect();
        $tablaCompleta    = collect();
        $comparativaMeses = collect();
        $resumenGeneral   = (object)[
            'total_productos'    => 0, 'total_unidades'     => 0,
            'total_monto'        => 0, 'total_comprobantes' => 0,
        ];

        if ($this->buscar && $idEmpresaActiva) {

            $base = $this->buildQueryBase();

            $rankingCantidad = (clone $base)->orderByDesc('total_cantidad')->limit($this->topN)->get();
            $rankingMonto    = (clone $base)->orderByDesc('total_monto')->limit($this->topN)->get();
            $bottomCantidad  = (clone $base)->orderBy('total_cantidad')->limit($this->topN)->get();
            $bottomMonto     = (clone $base)->orderBy('total_monto')->limit($this->topN)->get();
            $sinRotacion     = $this->buildQuerySinRotacion()->orderByDesc('stock_actual')->limit(100)->get();

            // Tabla completa paginada
            $columnasPermitidas = ['total_cantidad', 'total_monto', 'en_comprobantes', 'precio_promedio', 'pro_nombre'];
            $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'total_cantidad';
            $direccion = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

            $tablaCompleta = (clone $base)
                ->orderBy($columna === 'pro_nombre' ? 'p.pro_nombre' : $columna, $direccion)
                ->paginate($this->porPagina);

            // Resumen general
            $totales = (clone $base)->get();

            $idSucursal = $this->resolverIdSucursal();
            $qComp = DB::table('ventas as v')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')
                ->whereIn('v.venta_tipo', ['01', '03', '20'])
                ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$this->desde, $this->hasta])
                ->where('v.id_empresa', $idEmpresaActiva);
            if ($idSucursal > 0) {
                $qComp->where('v.id_sucursal', $idSucursal);
            }

            $resumenGeneral = (object)[
                'total_productos'    => $totales->count(),
                'total_unidades'     => $totales->sum('total_cantidad'),
                'total_monto'        => $totales->sum('total_monto'),
                'total_comprobantes' => $qComp->count(),
            ];

            // Comparativa por mes
            $queryComp = DB::table('ventas_detalle as vd')
                ->select(
                    DB::raw("DATE_FORMAT(v.venta_fecha, '%Y-%m') as mes"),
                    DB::raw("DATE_FORMAT(v.venta_fecha, '%m/%Y') as mes_label"),
                    DB::raw('SUM(vd.venta_detalle_cantidad) as total_cantidad'),
                    DB::raw('SUM(vd.venta_detalle_importe_total) as total_monto'),
                    DB::raw('COUNT(DISTINCT vd.id_pro) as productos_distintos'),
                )
                ->join('ventas as v',    'v.id_venta', '=', 'vd.id_venta')
                ->join('productos as p', 'p.id_pro',   '=', 'vd.id_pro')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')
                ->whereIn('v.venta_tipo', ['01', '03', '20'])
                ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$this->desde, $this->hasta]);

            $this->aplicarFiltroUbicacion($queryComp);

            if ($this->idCategoria) {
                $queryComp->where('p.id_ca', $this->idCategoria);
            }

            $comparativaMeses = $queryComp
                ->groupBy(
                    DB::raw("DATE_FORMAT(v.venta_fecha, '%Y-%m')"),
                    DB::raw("DATE_FORMAT(v.venta_fecha, '%m/%Y')")
                )
                ->orderBy('mes')
                ->get();

            $this->dispatch('actualizarGraficosProductos', [
                'rankingCantidad' => [
                    'labels'  => $rankingCantidad->pluck('pro_nombre')->map(fn($n) => mb_substr($n, 0, 20))->values()->toArray(),
                    'totales' => $rankingCantidad->pluck('total_cantidad')->map(fn($v) => round((float) $v, 2))->values()->toArray(),
                ],
                'rankingMonto' => [
                    'labels'  => $rankingMonto->pluck('pro_nombre')->map(fn($n) => mb_substr($n, 0, 20))->values()->toArray(),
                    'totales' => $rankingMonto->pluck('total_monto')->map(fn($v) => round((float) $v, 2))->values()->toArray(),
                ],
                'comparativa' => [
                    'labels'   => $comparativaMeses->pluck('mes_label')->values()->toArray(),
                    'cantidad' => $comparativaMeses->pluck('total_cantidad')->map(fn($v) => round((float) $v, 2))->values()->toArray(),
                    'monto'    => $comparativaMeses->pluck('total_monto')->map(fn($v) => round((float) $v, 2))->values()->toArray(),
                ],
            ]);
        }

        return view('livewire.reporte.reporte-ventas-productos', compact(
            'esSuperAdmin', 'esAdmin', 'idEmpresaActiva',
            'empresas', 'categorias',
            'rankingCantidad', 'rankingMonto',
            'bottomCantidad', 'bottomMonto',
            'sinRotacion',
            'tablaCompleta', 'comparativaMeses', 'resumenGeneral'
        ));
    }
}
