<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteStock extends Component
{
    use WithPagination;

    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    public string $filtroBusqueda  = '';
    public string $filtroCategoria = '';
    public string $filtroEstado    = '';
    public int    $porPagina       = 25;

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
        abort_if(!auth()->user()->can('reporte_stock.listar'), 403);

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
        $this->sucursalSeleccionada = 0; $this->resetPage();
        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')->get()
            : collect();
    }

    public function updatedSucursalSeleccionada(): void  { $this->resetPage(); }
    public function updatedFiltroBusqueda(): void         { $this->resetPage(); }
    public function updatedFiltroCategoria(): void        { $this->resetPage(); }
    public function updatedFiltroEstado(): void           { $this->resetPage(); }
    public function updatingPorPagina(): void             { $this->resetPage(); }

    public function exportarPdf(): void
    {
        if (!auth()->user()->can('reporte_stock.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.'); return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.reporte_stock_pdf', $this->buildParams()));
    }

    public function exportarExcel(): void
    {
        if (!auth()->user()->can('reporte_stock.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.'); return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.reporte_stock_excel', $this->buildParams()));
    }

    private function buildParams(): array
    {
        return [
            'empresa'   => $this->empresaSeleccionada,
            'sucursal'  => $this->sucursalSeleccionada,
            'busqueda'  => $this->filtroBusqueda,
            'categoria' => $this->filtroCategoria,
            'estado'    => $this->filtroEstado,
        ];
    }

    private function buildQuery()
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        $query = DB::table('producto_sucursal as ps')
            ->select(
                'p.pro_codigo', 'p.pro_nombre', 'ca.ca_nombre as categoria',
                't.tienda_nombre as sucursal_nombre',
                'ps.ps_stock', 'ps.ps_stock_minimo',
                DB::raw('CASE
                    WHEN ps.ps_stock <= 0 THEN "sin_stock"
                    WHEN ps.ps_stock <= ps.ps_stock_minimo THEN "critico"
                    ELSE "ok"
                END as estado_stock')
            )
            ->join('productos as p',   'p.id_pro',    '=', 'ps.id_pro')
            ->join('tiendas as t',     't.id_tienda', '=', 'ps.id_sucursal')
            ->join('categorias as ca', 'ca.id_ca',    '=', 'p.id_ca')
            ->where('ps.ps_estado', 1)
            ->where('p.pro_estado', 1)
            ->where('t.tienda_estado', 1);

        if ($idSucursal > 0) {
            $query->where('ps.id_sucursal', $idSucursal);
        } elseif ($idEmpresa) {
            $query->where('p.id_empresa', $idEmpresa);
        }

        if ($this->filtroBusqueda !== '') {
            $like = '%' . $this->filtroBusqueda . '%';
            $query->where(fn($q) => $q->where('p.pro_nombre', 'like', $like)
                                      ->orWhere('p.pro_codigo', 'like', $like));
        }
        if ($this->filtroCategoria !== '') {
            $query->where('p.id_ca', $this->filtroCategoria);
        }
        if ($this->filtroEstado === 'critico') {
            $query->whereRaw('ps.ps_stock > 0 AND ps.ps_stock <= ps.ps_stock_minimo');
        } elseif ($this->filtroEstado === 'sin_stock') {
            $query->where('ps.ps_stock', '<=', 0);
        } elseif ($this->filtroEstado === 'ok') {
            $query->whereRaw('ps.ps_stock > ps.ps_stock_minimo');
        }

        return $query;
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();
        $idEmpresa    = $this->resolverIdEmpresa();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $categorias = $idEmpresa
            ? DB::table('categorias as ca')
                ->join('productos as p', 'p.id_ca', '=', 'ca.id_ca')
                ->where('p.id_empresa', $idEmpresa)->where('ca.ca_estado', 1)
                ->distinct()->orderBy('ca.ca_nombre')->get(['ca.id_ca', 'ca.ca_nombre'])
            : DB::table('categorias')->where('ca_estado', 1)->orderBy('ca_nombre')->get(['id_ca', 'ca_nombre']);

        $productos = $this->buildQuery()->orderBy('p.pro_nombre')->paginate($this->porPagina);

        $resumen = [
            'total'      => $this->buildQuery()->count(),
            'sin_stock'  => $this->buildQuery()->where('ps.ps_stock', '<=', 0)->count(),
            'critico'    => $this->buildQuery()->whereRaw('ps.ps_stock > 0 AND ps.ps_stock <= ps.ps_stock_minimo')->count(),
            'ok'         => $this->buildQuery()->whereRaw('ps.ps_stock > ps.ps_stock_minimo')->count(),
        ];

        return view('livewire.reporte.reporte-stock', compact(
            'esSuperAdmin', 'esAdmin', 'empresas', 'categorias', 'productos', 'resumen'
        ));
    }
}
