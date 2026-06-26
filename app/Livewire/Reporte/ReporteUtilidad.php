<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteUtilidad extends Component
{
    use WithPagination;

    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    public string $filtroDesde = '';
    public string $filtroHasta = '';
    public string $buscarProducto = '';
    public bool   $buscado     = false;
    public int    $porPagina   = 20;

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
            ->where('ut.id_users', auth()->user()->id_users)->value('ut.id_tienda');
        return $id ? (int) $id : 0;
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reporte_utilidad.listar'), 403);
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');

        $empresaId = $this->empresaUsuario();
        if ($empresaId) {
            $this->empresaSeleccionada   = $empresaId;
            $this->sucursalesDisponibles = DB::table('tiendas')
                ->where('id_empresa', $empresaId)->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')->get();
        }
        if (!$this->esSuperAdmin() && !$this->esAdmin()) {
            $idTienda = DB::table('user_tienda as ut')
                ->where('ut.id_users', auth()->user()->id_users)->value('ut.id_tienda');
            if ($idTienda) $this->sucursalSeleccionada = (int) $idTienda;
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0; $this->buscado = false; $this->resetPage();
        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)->orderBy('tienda_nombre')->get()
            : collect();
    }

    public function updatedSucursalSeleccionada(): void { $this->buscado = false; $this->resetPage(); }
    public function updatedFiltroDesde(): void           { $this->buscado = false; }
    public function updatedFiltroHasta(): void           { $this->buscado = false; }
    public function updatedBuscarProducto(): void        { $this->resetPage(); }
    public function updatingPorPagina(): void            { $this->resetPage(); }

    public function generar(): void { $this->buscado = true; $this->resetPage(); }

    private function buildParams(): array
    {
        return [
            'id_empresa'  => $this->resolverIdEmpresa() ?? 0,
            'id_sucursal' => $this->resolverIdSucursal(),
            'desde'       => $this->filtroDesde,
            'hasta'       => $this->filtroHasta,
            'q'           => $this->buscarProducto,
        ];
    }

    public function imprimirPdf(): void
    {
        if (!auth()->user()->can('reporte_utilidad.exportar')) { session()->flash('error', 'Sin permiso para exportar.'); return; }
        $this->dispatch('abrirEnlaces', url: route('reporte.utilidad_pdf', $this->buildParams()));
    }

    public function imprimirExcel(): void
    {
        if (!auth()->user()->can('reporte_utilidad.exportar')) { session()->flash('error', 'Sin permiso para exportar.'); return; }
        $this->dispatch('abrirEnlaces', url: route('reporte.utilidad_excel', $this->buildParams()));
    }

    private function buildQuery()
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        $query = DB::table('ventas_detalle as vd')
            ->join('ventas as v', 'v.id_venta', '=', 'vd.id_venta')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->leftJoin('productos as p', 'p.id_pro', '=', 'vd.id_pro')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereDate('v.venta_fecha', '>=', $this->filtroDesde)
            ->whereDate('v.venta_fecha', '<=', $this->filtroHasta);

        if ($idSucursal > 0) {
            $query->where('v.id_sucursal', $idSucursal);
        } elseif ($idEmpresa) {
            $query->where('v.id_empresa', $idEmpresa);
        }

        if (trim($this->buscarProducto) !== '') {
            $like = '%' . trim($this->buscarProducto) . '%';
            $query->where(fn($q) => $q->where('vd.venta_detalle_nombre_producto', 'like', $like)
                                      ->orWhere('p.pro_codigo', 'like', $like));
        }

        // costo unitario = costo total del producto (Base+Flete+Margen) o costo base
        $costoExpr = 'COALESCE(p.pro_costo_total, p.pro_costo_base, 0)';

        return $query->groupBy('vd.id_pro', 'vd.venta_detalle_nombre_producto', 'p.pro_codigo')
            ->select(
                'vd.venta_detalle_nombre_producto as producto',
                'p.pro_codigo',
                DB::raw('SUM(vd.venta_detalle_cantidad) as cantidad'),
                DB::raw('SUM(vd.venta_detalle_importe_total) as total_venta'),
                DB::raw("SUM(vd.venta_detalle_cantidad * {$costoExpr}) as total_costo"),
                DB::raw("SUM(vd.venta_detalle_importe_total - (vd.venta_detalle_cantidad * {$costoExpr})) as utilidad")
            )
            ->orderByDesc('utilidad');
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $filas   = collect();
        $totales = ['venta' => 0, 'costo' => 0, 'utilidad' => 0];

        if ($this->buscado) {
            $filas = $this->buildQuery()->paginate($this->porPagina);
            $all   = $this->buildQuery()->get();
            $totales = [
                'venta'    => (float) $all->sum('total_venta'),
                'costo'    => (float) $all->sum('total_costo'),
                'utilidad' => (float) $all->sum('utilidad'),
            ];
        }

        return view('livewire.reporte.reporte-utilidad', compact(
            'esSuperAdmin', 'esAdmin', 'empresas', 'filas', 'totales'
        ));
    }
}
