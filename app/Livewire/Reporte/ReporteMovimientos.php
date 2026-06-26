<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteMovimientos extends Component
{
    use WithPagination;

    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    public string $filtroDesde   = '';
    public string $filtroHasta   = '';
    public string $filtroTipo    = '';   // '' | 1 ingreso | 2 salida
    public string $buscarProducto = '';
    public bool   $buscado       = false;
    public int    $porPagina     = 20;

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
            ->where('ut.id_users', auth()->user()->id_users)->value('t.id_empresa');
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
        abort_if(!auth()->user()->can('reporte_movimientos.listar'), 403);
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
    public function updatedFiltroTipo(): void            { $this->buscado = false; $this->resetPage(); }
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
            'tipo'        => $this->filtroTipo,
            'q'           => $this->buscarProducto,
        ];
    }

    public function imprimirPdf(): void
    {
        if (!auth()->user()->can('reporte_movimientos.exportar')) { session()->flash('error', 'Sin permiso para exportar.'); return; }
        $this->dispatch('abrirEnlaces', url: route('reporte.movimientos_pdf', $this->buildParams()));
    }

    public function imprimirExcel(): void
    {
        if (!auth()->user()->can('reporte_movimientos.exportar')) { session()->flash('error', 'Sin permiso para exportar.'); return; }
        $this->dispatch('abrirEnlaces', url: route('reporte.movimientos_excel', $this->buildParams()));
    }

    private function buildQuery()
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        $query = DB::table('movimientos_productos_detalle as d')
            ->join('movimientos_productos as m', 'm.id_movimientos_productos', '=', 'd.id_movimientos_productos')
            ->leftJoin('productos as p', 'p.id_pro', '=', 'd.id_pro')
            ->leftJoin('tiendas as t', 't.id_tienda', '=', 'm.id_sucursal')
            ->where('m.movimientos_productos_estado', 1)
            ->whereDate('m.movimientos_productos_fecha', '>=', $this->filtroDesde)
            ->whereDate('m.movimientos_productos_fecha', '<=', $this->filtroHasta);

        if ($idSucursal > 0) {
            $query->where('m.id_sucursal', $idSucursal);
        } elseif ($idEmpresa) {
            $query->where('t.id_empresa', $idEmpresa);
        }

        if ($this->filtroTipo !== '') {
            $query->where('m.movimientos_productos_tipo', (int) $this->filtroTipo);
        }

        if (trim($this->buscarProducto) !== '') {
            $like = '%' . trim($this->buscarProducto) . '%';
            $query->where(fn($q) => $q->where('p.pro_nombre', 'like', $like)
                                      ->orWhere('p.pro_codigo', 'like', $like));
        }

        return $query->select(
            'm.movimientos_productos_fecha as fecha',
            'm.movimientos_productos_tipo as tipo',
            'm.movimientos_productos_motivo as motivo',
            'm.concepto',
            'p.pro_nombre', 'p.pro_codigo',
            't.tienda_nombre',
            DB::raw('CAST(d.movimientos_productos_detalle_cantidad AS DECIMAL(14,2)) as cantidad'),
            'd.costo_unitario'
        )->orderByDesc('m.movimientos_productos_fecha')->orderByDesc('m.id_movimientos_productos');
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $movimientos = collect();
        $totales = ['ingresos' => 0, 'salidas' => 0, 'registros' => 0];

        if ($this->buscado) {
            $movimientos = $this->buildQuery()->paginate($this->porPagina);
            $all = $this->buildQuery()->get();
            $totales = [
                'registros' => $all->count(),
                'ingresos'  => (float) $all->where('tipo', 1)->sum('cantidad'),
                'salidas'   => (float) $all->where('tipo', 2)->sum('cantidad'),
            ];
        }

        return view('livewire.reporte.reporte-movimientos', compact(
            'esSuperAdmin', 'esAdmin', 'empresas', 'movimientos', 'totales'
        ));
    }
}
