<?php

namespace App\Livewire\Logistica;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReporteCompras extends Component
{
    // ── Rol y contexto ─────────────────────────────────────────
    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];
    public      $empresas              = [];
    public      $proveedores           = [];

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

    private function aplicarFiltroUbicacion(\Illuminate\Database\Query\Builder $query): void
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        if ($idSucursal > 0) {
            $query->where('oc.id_sucursal', $idSucursal);
        } elseif ($idEmpresa) {
            $query->where('s.id_empresa', $idEmpresa);
        }
    }

    // ── Filtros ────────────────────────────────────────────────
    public string $desde      = '';
    public string $hasta      = '';
    public string $idProveedor = '';
    public string $vistaActiva = 'por_proveedor';
    public string $agrupacion  = 'mensual';
    public bool   $buscando    = false;

    // ── Resultados ─────────────────────────────────────────────
    public $resultados  = [];
    public $totales     = null;
    public $porPeriodo  = [];

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reporte_compras.listar'), 403);

        $this->desde = now()->startOfMonth()->format('Y-m-d');
        $this->hasta = now()->format('Y-m-d');

        $this->cargarEmpresas();
        $this->cargarProveedores();

        if ($this->esAdmin()) {
            $empresaId = $this->adminEmpresaId();
            if ($empresaId) {
                $this->sucursalesDisponibles = DB::table('sucursals')
                    ->where('id_empresa', $empresaId)
                    ->where('sucursal_estado', 1)
                    ->whereNull('deleted_at')
                    ->orderBy('sucursal_nombre')
                    ->get();
            }
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        $this->buscando = false;
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
        $this->buscando = false;
    }

    private function cargarEmpresas(): void
    {
        if ($this->esSuperAdmin()) {
            $this->empresas = DB::table('empresa')
                ->where('empresa_estado', 1)
                ->orderBy('empresa_nombrecomercial')
                ->get(['id_empresa', 'empresa_nombrecomercial']);
        }
    }

    private function cargarProveedores(): void
    {
        $query = DB::table('proveedores')->where('proveedores_estado', 1);

        // Limitar proveedores por empresa/sucursal según rol
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();
        if ($idSucursal > 0 || $idEmpresa) {
            $subquery = DB::table('orden_compra as oc')
                ->join('sucursals as s', 's.id_sucursal', '=', 'oc.id_sucursal')
                ->select('oc.id_proveedores');
            if ($idSucursal > 0) {
                $subquery->where('oc.id_sucursal', $idSucursal);
            } elseif ($idEmpresa) {
                $subquery->where('s.id_empresa', $idEmpresa);
            }
            $query->whereIn('id_proveedores', $subquery);
        }

        $this->proveedores = $query->orderBy('proveedores_nombre')
            ->get(['id_proveedores', 'proveedores_nombre']);
    }

    public function buscar(): void
    {
        $this->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ], [
            'desde.required'       => 'La fecha "Desde" es obligatoria.',
            'hasta.required'       => 'La fecha "Hasta" es obligatoria.',
            'hasta.after_or_equal' => '"Hasta" debe ser igual o posterior a "Desde".',
        ]);

        $this->buscando = true;
        $this->cargarDatos();
    }

    private function buildBaseQuery(): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('orden_compra as oc')
            ->join('proveedores as pv', 'pv.id_proveedores', '=', 'oc.id_proveedores')
            ->join('sucursals as s', 's.id_sucursal', '=', 'oc.id_sucursal')
            ->join('orden_compra_detalle as ocd', 'ocd.id_orden_compra', '=', 'oc.id_orden_compra')
            ->join('productos as p', 'p.id_pro', '=', 'ocd.id_pro')
            ->where('oc.orden_compra_estado', 'recibido')
            ->whereBetween(DB::raw('DATE(oc.orden_compra_fecha)'), [$this->desde, $this->hasta]);

        if ($this->idProveedor !== '') {
            $query->where('oc.id_proveedores', $this->idProveedor);
        }

        $this->aplicarFiltroUbicacion($query);

        return $query;
    }

    private function cargarDatos(): void
    {
        $base = $this->buildBaseQuery();

        $this->resultados = (clone $base)
            ->select(
                'pv.id_proveedores',
                'pv.proveedores_nombre',
                'p.id_pro',
                'p.pro_nombre',
                'p.pro_codigo',
                DB::raw('SUM(COALESCE(ocd.detalle_compra_cantidad_recibida, ocd.detalle_compra_cantidad)) as total_cantidad'),
                DB::raw('SUM(ocd.detalle_compra_total_pedido) as total_costo_base'),
                DB::raw('SUM(COALESCE(ocd.flete, 0)) as total_flete'),
                DB::raw('SUM(ocd.detalle_compra_total_pedido + COALESCE(ocd.flete, 0)) as total_costo'),
                DB::raw('MAX(p.pro_precio_venta) as precio_venta_ref'),
                DB::raw('COUNT(DISTINCT oc.id_orden_compra) as num_ordenes')
            )
            ->groupBy('pv.id_proveedores', 'pv.proveedores_nombre', 'p.id_pro', 'p.pro_nombre', 'p.pro_codigo')
            ->orderBy('pv.proveedores_nombre')
            ->orderByDesc('total_costo')
            ->get();

        $this->totales = (clone $base)
            ->selectRaw('
                COUNT(DISTINCT oc.id_orden_compra) as num_ordenes,
                COUNT(DISTINCT oc.id_proveedores) as num_proveedores,
                SUM(COALESCE(ocd.detalle_compra_cantidad_recibida, ocd.detalle_compra_cantidad)) as total_cantidad,
                SUM(ocd.detalle_compra_total_pedido) as total_costo_base,
                SUM(COALESCE(ocd.flete, 0)) as total_flete,
                SUM(ocd.detalle_compra_total_pedido + COALESCE(ocd.flete, 0)) as total_costo
            ')
            ->first();

        $groupFormat = $this->agrupacion === 'diario'
            ? "DATE_FORMAT(oc.orden_compra_fecha, '%Y-%m-%d')"
            : "DATE_FORMAT(oc.orden_compra_fecha, '%Y-%m')";
        $labelFormat = $this->agrupacion === 'diario'
            ? "DATE_FORMAT(oc.orden_compra_fecha, '%d/%m/%Y')"
            : "DATE_FORMAT(oc.orden_compra_fecha, '%m/%Y')";

        $this->porPeriodo = (clone $base)
            ->select(
                DB::raw("{$groupFormat} as periodo_key"),
                DB::raw("{$labelFormat} as periodo_label"),
                DB::raw('COUNT(DISTINCT oc.id_orden_compra) as num_ordenes'),
                DB::raw('COUNT(DISTINCT oc.id_proveedores) as num_proveedores'),
                DB::raw('SUM(COALESCE(ocd.detalle_compra_cantidad_recibida, ocd.detalle_compra_cantidad)) as total_cantidad'),
                DB::raw('SUM(ocd.detalle_compra_total_pedido) as total_costo_base'),
                DB::raw('SUM(COALESCE(ocd.flete, 0)) as total_flete'),
                DB::raw('SUM(ocd.detalle_compra_total_pedido + COALESCE(ocd.flete, 0)) as total_costo')
            )
            ->groupByRaw("{$groupFormat}, {$labelFormat}")
            ->orderByRaw($groupFormat)
            ->get();
    }

    public function setVista(string $vista): void
    {
        $this->vistaActiva = $vista;
    }

    public function exportarPdf(): void
    {
        try {
            if (!auth()->user()->can('reporte_compras.exportar')) {
                session()->flash('errorGeneral', 'Acceso denegado.');
                return;
            }
            $this->dispatch('abrirEnlaces', url: route('logistica.reporte_compras_pdf', $this->buildExportableParams()));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    public function exportarExcel(): void
    {
        try {
            if (!auth()->user()->can('reporte_compras.exportar')) {
                session()->flash('errorGeneral', 'Acceso denegado.');
                return;
            }
            $this->dispatch('abrirEnlaces', url: route('logistica.reporte_compras_excel', $this->buildExportableParams()));
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
        }
    }

    private function buildExportableParams(): array
    {
        return [
            'desde'       => $this->desde,
            'hasta'       => $this->hasta,
            'proveedor'   => $this->idProveedor,
            'agrupacion'  => $this->agrupacion,
            'id_empresa'  => $this->resolverIdEmpresa() ?? 0,
            'id_sucursal' => $this->resolverIdSucursal(),
        ];
    }

    public function render()
    {
        return view('livewire.logistica.reporte-compras');
    }
}
