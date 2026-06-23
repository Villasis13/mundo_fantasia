<?php

namespace App\Livewire\Logistica;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StockConsolidado extends Component
{
    use WithPagination;

    // ── Rol y contexto ────────────────────────────────────────
    private int  $cachedRoleId        = 0;
    private ?Logs $logs               = null;

    // ── Filtros ───────────────────────────────────────────────
    public int    $empresaSeleccionada  = 0;
    public int    $sucursalSeleccionada = 0;
    public int    $filtroFamilia        = 0;
    public string $filtroEstado         = 'todos';
    public string $buscar               = '';
    public int    $porPagina            = 20;
    public string $ordenColumna         = 'p.pro_nombre';
    public string $ordenDireccion       = 'asc';

    public function boot(): void
    {
        $this->logs = new Logs();
        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('stock_consolidado.listar'), 403);
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

    // ── Hooks de actualización ────────────────────────────────
    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        $this->resetPage();
    }

    public function updatedSucursalSeleccionada(): void { $this->resetPage(); }
    public function updatedFiltroFamilia(): void        { $this->resetPage(); }
    public function updatedFiltroEstado(): void         { $this->resetPage(); }
    public function updatingBuscar(): void              { $this->resetPage(); }
    public function updatingPorPagina(): void           { $this->resetPage(); }

    // ── Ordenar ───────────────────────────────────────────────
    public function ordenar(string $columna): void
    {
        $permitidas = ['p.pro_nombre', 'p.pro_codigo', 's.sucursal_nombre', 'ps.ps_stock', 'ps.ps_stock_minimo'];
        if (!in_array($columna, $permitidas)) return;

        if ($this->ordenColumna === $columna) {
            $this->ordenDireccion = $this->ordenDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenColumna   = $columna;
            $this->ordenDireccion = 'asc';
        }
        $this->resetPage();
    }

    // ── Render ────────────────────────────────────────────────
    public function render()
    {
        $esSuperAdmin   = $this->esSuperAdmin();
        $esAdmin        = $this->esAdmin();
        $adminEmpresaId = $esAdmin ? $this->adminEmpresaId() : null;

        $permitidas = ['p.pro_nombre', 'p.pro_codigo', 's.sucursal_nombre', 'ps.ps_stock', 'ps.ps_stock_minimo'];
        $columna    = in_array($this->ordenColumna, $permitidas) ? $this->ordenColumna : 'p.pro_nombre';
        $direccion  = $this->ordenDireccion === 'asc' ? 'asc' : 'desc';

        $query = DB::table('producto_sucursal as ps')
            ->join('productos as p',   'p.id_pro',        '=', 'ps.id_pro')
            ->join('sucursals as s',   's.id_sucursal',   '=', 'ps.id_sucursal')
            ->join('empresa as e',     'e.id_empresa',    '=', 's.id_empresa')
            ->leftJoin('categorias as c', 'c.id_ca',      '=', 'p.id_ca')
            ->leftJoin('familias as f',   'f.id_fa',      '=', 'c.id_fa')
            ->where('p.pro_estado',        1)
            ->where('ps.ps_estado',        1)
            ->where('s.sucursal_estado',   1)
            ->whereNull('s.deleted_at')
            ->selectRaw('
                p.id_pro, p.pro_nombre, p.pro_codigo, p.pro_codigo_interno,
                s.id_sucursal, s.sucursal_nombre,
                e.id_empresa, e.empresa_nombrecomercial,
                f.fa_nombre,
                ps.ps_stock, ps.ps_stock_minimo, ps.ps_precio_uni
            ')
            // Filtro empresa
            ->when($esSuperAdmin && $this->empresaSeleccionada > 0,
                fn($q) => $q->where('s.id_empresa', $this->empresaSeleccionada))
            ->when($esAdmin && $adminEmpresaId,
                fn($q) => $q->where('s.id_empresa', $adminEmpresaId))
            // Filtro sucursal
            ->when($this->sucursalSeleccionada > 0,
                fn($q) => $q->where('ps.id_sucursal', $this->sucursalSeleccionada))
            // Filtro familia
            ->when($this->filtroFamilia > 0,
                fn($q) => $q->where('f.id_fa', $this->filtroFamilia))
            // Filtro estado
            ->when($this->filtroEstado === 'sin_stock',
                fn($q) => $q->where('ps.ps_stock', '<=', 0))
            ->when($this->filtroEstado === 'bajo_minimo',
                fn($q) => $q->where('ps.ps_stock', '>', 0)
                             ->whereColumn('ps.ps_stock', '<=', 'ps.ps_stock_minimo'))
            // Búsqueda
            ->when($this->buscar, fn($q) => $q->where(function ($inner) {
                $inner->where('p.pro_nombre',          'like', "%{$this->buscar}%")
                      ->orWhere('p.pro_codigo',        'like', "%{$this->buscar}%")
                      ->orWhere('p.pro_codigo_interno','like', "%{$this->buscar}%");
            }))
            ->orderBy($columna, $direccion);

        $stockPaginado = $query->paginate($this->porPagina);

        // Empresas para dropdown superadmin
        $empresas = $esSuperAdmin
            ? DB::table('empresa')
                ->where('empresa_estado', '!=', 0)
                ->orderBy('empresa_razon_social')
                ->get()
            : collect();

        // Sucursales disponibles (según empresa activa o rol)
        $sucursalesDisponibles = collect();
        if ($esSuperAdmin) {
            $sucursalesDisponibles = DB::table('sucursals')
                ->when($this->empresaSeleccionada > 0,
                    fn($q) => $q->where('id_empresa', $this->empresaSeleccionada))
                ->where('sucursal_estado', 1)
                ->whereNull('deleted_at')
                ->orderBy('sucursal_nombre')
                ->get();
        } elseif ($esAdmin && $adminEmpresaId) {
            $sucursalesDisponibles = DB::table('sucursals')
                ->where('id_empresa', $adminEmpresaId)
                ->where('sucursal_estado', 1)
                ->whereNull('deleted_at')
                ->orderBy('sucursal_nombre')
                ->get();
        }

        $familias = DB::table('familias')
            ->where('fa_estado', 1)
            ->orderBy('fa_nombre')
            ->get();

        return view('livewire.logistica.stock-consolidado', compact(
            'stockPaginado', 'empresas', 'sucursalesDisponibles', 'familias',
            'esSuperAdmin', 'esAdmin'
        ));
    }
}
