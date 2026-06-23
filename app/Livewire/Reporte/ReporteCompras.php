<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteCompras extends Component
{
    use WithPagination;

    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    public string $filtroProveedor  = '';
    public string $filtroDesde      = '';
    public string $filtroHasta      = '';
    public string $filtroEstado     = '';
    public string $filtroEstadoOC   = '';
    public string $tipoReporte      = 'compras';
    public bool   $buscado          = false;
    public int    $porPagina        = 20;

    private $logs;

    public function boot(): void
    {
        $this->logs = new Logs();
        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)->value('role_id');
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
        abort_if(!auth()->user()->can('reporte_compras.listar'), 403);
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');

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
            if ($idTienda) $this->sucursalSeleccionada = (int) $idTienda;
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0; $this->buscado = false; $this->resetPage();
        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')->get()
            : collect();
    }

    public function updatedSucursalSeleccionada(): void { $this->buscado = false; $this->resetPage(); }
    public function updatedFiltroProveedor(): void       { $this->buscado = false; }
    public function updatedFiltroDesde(): void           { $this->buscado = false; }
    public function updatedFiltroHasta(): void           { $this->buscado = false; }
    public function updatedFiltroEstado(): void          { $this->buscado = false; }
    public function updatedFiltroEstadoOC(): void        { $this->buscado = false; }
    public function updatedTipoReporte(): void           { $this->buscado = false; $this->resetPage(); }
    public function updatingPorPagina(): void            { $this->resetPage(); }

    public function generar(): void { $this->buscado = true; $this->resetPage(); }

    public function exportarPdf(): void
    {
        if (!auth()->user()->can('reporte_compras.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.'); return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.reporte_compras_pdf', $this->buildParams()));
    }

    public function exportarExcel(): void
    {
        if (!auth()->user()->can('reporte_compras.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.'); return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.reporte_compras_excel', $this->buildParams()));
    }

    private function buildParams(): array
    {
        return [
            'empresa'    => $this->empresaSeleccionada,
            'sucursal'   => $this->sucursalSeleccionada,
            'proveedor'  => $this->filtroProveedor,
            'desde'      => $this->filtroDesde,
            'hasta'      => $this->filtroHasta,
            'estado'     => $this->filtroEstado,
            'estado_oc'  => $this->filtroEstadoOC,
            'tipo'       => $this->tipoReporte,
        ];
    }

    private function applyBaseFilters($query): void
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        if ($idSucursal > 0) {
            $query->where('oc.id_sucursal', $idSucursal);
        } elseif ($idEmpresa) {
            $query->whereIn('oc.id_sucursal', function ($sub) use ($idEmpresa) {
                $sub->select('id_tienda')->from('tiendas')->where('id_empresa', $idEmpresa);
            });
        }

        if ($this->filtroDesde) $query->whereDate('oc.orden_compra_fecha', '>=', $this->filtroDesde);
        if ($this->filtroHasta) $query->whereDate('oc.orden_compra_fecha', '<=', $this->filtroHasta);

        if ($this->filtroProveedor !== '') {
            $like = '%' . $this->filtroProveedor . '%';
            $query->where(fn($q) => $q->where('p.proveedores_nombre', 'like', $like)
                                      ->orWhere('p.proveedores_numero_documento', 'like', $like));
        }

        if ($this->filtroEstado !== '') {
            $query->where('oc.orden_compra_activo', $this->filtroEstado === 'activo' ? 1 : 0);
        }

        if ($this->filtroEstadoOC !== '') {
            $query->where('oc.orden_compra_estado', $this->filtroEstadoOC);
        }
    }

    public function buildQuery()
    {
        $query = DB::table('orden_compra as oc')
            ->select(
                'oc.id_orden_compra', 'oc.orden_compra_codigo', 'oc.orden_compra_numero',
                'oc.orden_compra_fecha', 'oc.orden_compra_fecha_emision_doc',
                'oc.orden_compra_tipo_doc', 'oc.orden_compra_numero_doc',
                'oc.orden_compra_total', 'oc.orden_compra_flete',
                'oc.orden_compra_gastos_operativos', 'oc.orden_compra_estado',
                'oc.orden_compra_activo',
                'p.proveedores_nombre', 'p.proveedores_numero_documento',
                't.tienda_nombre as sucursal_nombre',
                DB::raw('(SELECT COUNT(*) FROM orden_compra_detalle ocd WHERE ocd.id_orden_compra = oc.id_orden_compra) as total_items')
            )
            ->join('proveedores as p', 'p.id_proveedores', '=', 'oc.id_proveedores')
            ->join('tiendas as t',     't.id_tienda',      '=', 'oc.id_sucursal');

        $this->applyBaseFilters($query);
        return $query->orderByDesc('oc.orden_compra_fecha');
    }

    public function buildQueryDetallado()
    {
        $query = DB::table('orden_compra as oc')
            ->select(
                'oc.orden_compra_codigo', 'oc.orden_compra_numero', 'oc.orden_compra_fecha',
                'oc.orden_compra_estado',
                'p.proveedores_nombre', 'p.proveedores_numero_documento',
                't.tienda_nombre as sucursal_nombre',
                'ocd.detalle_orden_nombre_producto',
                'pro.pro_codigo',
                'ocd.detalle_compra_cantidad',
                'ocd.detalle_compra_cantidad_recibida',
                'ocd.detalle_compra_total_pedido',
                DB::raw('COALESCE(ocd.flete, 0) as detalle_flete')
            )
            ->join('proveedores as p',           'p.id_proveedores',  '=', 'oc.id_proveedores')
            ->join('tiendas as t',               't.id_tienda',       '=', 'oc.id_sucursal')
            ->join('orden_compra_detalle as ocd','ocd.id_orden_compra','=', 'oc.id_orden_compra')
            ->leftJoin('productos as pro',       'pro.id_pro',        '=', 'ocd.id_pro');

        $this->applyBaseFilters($query);
        return $query->orderByDesc('oc.orden_compra_fecha')->orderBy('p.proveedores_nombre');
    }

    public function buildQueryResumen()
    {
        $query = DB::table('orden_compra as oc')
            ->select(
                'p.proveedores_nombre', 'p.proveedores_numero_documento',
                DB::raw('COUNT(DISTINCT oc.id_orden_compra) as total_ordenes'),
                DB::raw('SUM(oc.orden_compra_total) as total_mercaderia'),
                DB::raw('SUM(COALESCE(oc.orden_compra_flete, 0)) as total_flete'),
                DB::raw('SUM(COALESCE(oc.orden_compra_gastos_operativos, 0)) as total_gastos'),
                DB::raw('SUM(oc.orden_compra_total + COALESCE(oc.orden_compra_flete,0) + COALESCE(oc.orden_compra_gastos_operativos,0)) as gran_total')
            )
            ->join('proveedores as p', 'p.id_proveedores', '=', 'oc.id_proveedores')
            ->join('tiendas as t',     't.id_tienda',      '=', 'oc.id_sucursal')
            ->groupBy('p.id_proveedores', 'p.proveedores_nombre', 'p.proveedores_numero_documento');

        $this->applyBaseFilters($query);
        return $query->orderBy('p.proveedores_nombre');
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $ordenes = collect();
        $totales = null;

        if ($this->buscado) {
            if ($this->tipoReporte === 'detallado') {
                $ordenes = $this->buildQueryDetallado()->paginate($this->porPagina);
                $all     = $this->buildQueryDetallado()->get();
                $totales = [
                    'tipo'        => 'detallado',
                    'cantidad'    => $all->count(),
                    'total_costo' => $all->sum('detalle_compra_total_pedido'),
                    'total_flete' => $all->sum('detalle_flete'),
                ];
            } elseif ($this->tipoReporte === 'resumen') {
                $ordenes = $this->buildQueryResumen()->paginate($this->porPagina);
                $all     = $this->buildQueryResumen()->get();
                $totales = [
                    'tipo'       => 'resumen',
                    'cantidad'   => $all->count(),
                    'ordenes'    => $all->sum('total_ordenes'),
                    'mercaderia' => $all->sum('total_mercaderia'),
                    'flete'      => $all->sum('total_flete'),
                    'gastos'     => $all->sum('total_gastos'),
                    'gran_total' => $all->sum('gran_total'),
                ];
            } else {
                $ordenes = $this->buildQuery()->paginate($this->porPagina);
                $all     = $this->buildQuery()->get();
                $totales = [
                    'tipo'       => 'compras',
                    'cantidad'   => $all->count(),
                    'total'      => $all->sum('orden_compra_total'),
                    'flete'      => $all->sum('orden_compra_flete'),
                    'gastos'     => $all->sum('orden_compra_gastos_operativos'),
                    'gran_total' => $all->sum(fn($r) => $r->orden_compra_total + $r->orden_compra_flete + $r->orden_compra_gastos_operativos),
                ];
            }
        }

        return view('livewire.reporte.reporte-compras', compact(
            'esSuperAdmin', 'esAdmin', 'empresas', 'ordenes', 'totales'
        ));
    }
}
