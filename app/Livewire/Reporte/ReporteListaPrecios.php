<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteListaPrecios extends Component
{
    use WithPagination;

    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

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
        abort_if(!auth()->user()->can('reporte_lista_precios.listar'), 403);

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
    public function updatedBuscarProducto(): void        { $this->resetPage(); }
    public function updatingPorPagina(): void            { $this->resetPage(); }

    public function generar(): void { $this->buscado = true; $this->resetPage(); }

    private function buildParams(): array
    {
        return [
            'id_empresa'  => $this->resolverIdEmpresa() ?? 0,
            'id_sucursal' => $this->resolverIdSucursal(),
            'q'           => $this->buscarProducto,
        ];
    }

    public function imprimirPdf(): void
    {
        if (!auth()->user()->can('reporte_lista_precios.exportar')) { session()->flash('error', 'Sin permiso para exportar.'); return; }
        $this->dispatch('abrirEnlaces', url: route('reporte.lista_precios_pdf', $this->buildParams()));
    }

    public function imprimirExcel(): void
    {
        if (!auth()->user()->can('reporte_lista_precios.exportar')) { session()->flash('error', 'Sin permiso para exportar.'); return; }
        $this->dispatch('abrirEnlaces', url: route('reporte.lista_precios_excel', $this->buildParams()));
    }

    private function buildQuery()
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        $query = DB::table('producto_sucursal as ps')
            ->join('productos as p', 'p.id_pro', '=', 'ps.id_pro')
            ->join('tiendas as t', 't.id_tienda', '=', 'ps.id_tienda')
            ->where('ps.ps_estado', 1)
            ->where('p.pro_estado', 1);

        if ($idSucursal > 0) {
            $query->where('ps.id_tienda', $idSucursal);
        } elseif ($idEmpresa) {
            $query->where('t.id_empresa', $idEmpresa);
        }

        if (trim($this->buscarProducto) !== '') {
            $like = '%' . trim($this->buscarProducto) . '%';
            $query->where(fn($q) => $q->where('p.pro_nombre', 'like', $like)
                                      ->orWhere('p.pro_codigo', 'like', $like)
                                      ->orWhere('p.pro_marca', 'like', $like));
        }

        return $query->select(
            'p.pro_nombre', 'p.pro_codigo', 'p.pro_marca',
            'p.pro_costo_total',
            'ps.ps_precio_uni', 'ps.ps_precio_uni_2', 'ps.ps_precio_uni_3',
            'ps.ps_stock', 't.tienda_nombre'
        )->orderBy('p.pro_nombre');
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $filas = collect();
        $totalProductos = 0;

        if ($this->buscado) {
            $filas = $this->buildQuery()->paginate($this->porPagina);
            $totalProductos = $this->buildQuery()->count();
        }

        return view('livewire.reporte.reporte-lista-precios', compact(
            'esSuperAdmin', 'esAdmin', 'empresas', 'filas', 'totalProductos'
        ));
    }
}
