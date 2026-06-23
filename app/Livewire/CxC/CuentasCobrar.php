<?php

namespace App\Livewire\CxC;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CuentasCobrar extends Component
{
    use WithPagination;

    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    public string $filtroCliente    = '';
    public string $filtroEstado     = '';
    public string $filtroDesde      = '';
    public string $filtroHasta      = '';
    public string $filtroVinculada  = '';
    public int    $porPagina        = 15;
    public string $ordenColumna     = 'venta_cuota_fecha';
    public string $ordenDireccion   = 'asc';

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
        if ($this->esSuperAdmin()) {
            return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : null;
        }
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
        abort_if(!auth()->user()->can('cuentas_cobrar.listar'), 403);

        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');

        $empresaId = $this->empresaUsuario();
        if ($empresaId) {
            $this->sucursalesDisponibles = DB::table('tiendas')
                ->where('id_empresa', $empresaId)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get();
        }

        if (!$this->esSuperAdmin() && !$this->esAdmin()) {
            $idTienda = DB::table('user_tienda as ut')
                ->where('ut.id_users', auth()->user()->id_users)
                ->value('ut.id_tienda');
            if ($idTienda) {
                $this->sucursalSeleccionada = (int) $idTienda;
            }
        } elseif ($this->esAdmin() && $empresaId) {
            $sucursales = DB::table('tiendas')
                ->where('id_empresa', $empresaId)
                ->where('tienda_estado', 1)
                ->get();
            if ($sucursales->count() === 1) {
                $this->sucursalSeleccionada = $sucursales->first()->id_tienda;
            }
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada  = 0;
        $this->resetPage();

        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get()
            : collect();
    }

    public function updatedSucursalSeleccionada(): void { $this->resetPage(); }
    public function updatedFiltroCliente(): void        { $this->resetPage(); }
    public function updatedFiltroEstado(): void         { $this->resetPage(); }
    public function updatedFiltroDesde(): void          { $this->resetPage(); }
    public function updatedFiltroHasta(): void          { $this->resetPage(); }
    public function updatedFiltroVinculada(): void      { $this->resetPage(); }
    public function updatingPorPagina(): void           { $this->resetPage(); }

    public function ordenar(string $columna): void
    {
        $this->ordenDireccion = $this->ordenColumna === $columna
            ? ($this->ordenDireccion === 'asc' ? 'desc' : 'asc')
            : 'asc';
        $this->ordenColumna = $columna;
        $this->resetPage();
    }

    private function buildQuery()
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();
        $hoy        = now()->toDateString();

        $subPagado = DB::table('pagos_cuotas as pc2')
            ->selectRaw('pc2.id_ventas_cuotas, SUM(pc2.pagos_cuota_monto) as total_pagado')
            ->whereNull('pc2.deleted_at')
            ->groupBy('pc2.id_ventas_cuotas');

        $query = DB::table('ventas_cuotas as vc')
            ->select(
                'vc.id_ventas_cuotas',
                'vc.venta_cuota_numero',
                'vc.venta_cuota_importe',
                'vc.venta_cuota_fecha',
                'vc.venta_cuota_estado',
                'v.id_venta',
                'v.venta_tipo',
                'v.venta_serie',
                'v.venta_correlativo',
                'v.venta_fecha',
                'v.id_empresa',
                'v.id_sucursal',
                'c.cliente_nombre',
                'c.cliente_razonsocial',
                'c.cliente_numero',
                'ti.tienda_nombre',
                'emp.empresa_nombrecomercial',
                DB::raw('COALESCE(pag.total_pagado, 0) as total_pagado'),
                DB::raw('GREATEST(vc.venta_cuota_importe - COALESCE(pag.total_pagado, 0), 0) as saldo'),
                DB::raw("CASE WHEN vc.venta_cuota_fecha < '{$hoy}' AND GREATEST(vc.venta_cuota_importe - COALESCE(pag.total_pagado, 0), 0) > 0 THEN DATEDIFF('{$hoy}', vc.venta_cuota_fecha) ELSE 0 END as dias_atraso"),
                DB::raw("CASE WHEN ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo THEN 1 ELSE 0 END as es_vinculada")
            )
            ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
            ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->leftJoinSub($subPagado, 'pag', 'pag.id_ventas_cuotas', '=', 'vc.id_ventas_cuotas')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->leftJoin('empresa as ev', 'ev.empresa_ruc', '=', 'c.cliente_numero')
            ->leftJoin('empresa as eo', 'eo.id_empresa', '=', 'v.id_empresa')
            ->leftJoin('tiendas as ti', 'ti.id_tienda', '=', 'v.id_sucursal')
            ->leftJoin('empresa as emp', 'emp.id_empresa', '=', 'v.id_empresa')
            ->whereNull('va.id_venta')
            ->where('v.id_formas_pago', 2);

        if ($idSucursal > 0) {
            $query->where('v.id_sucursal', $idSucursal);
        } elseif ($idEmpresa) {
            $query->where('v.id_empresa', $idEmpresa);
        }

        if ($this->filtroCliente !== '') {
            $like = '%' . $this->filtroCliente . '%';
            $query->where(function ($q) use ($like) {
                $q->where('c.cliente_nombre', 'like', $like)
                  ->orWhere('c.cliente_razonsocial', 'like', $like)
                  ->orWhere('c.cliente_numero', 'like', $like);
            });
        }

        if ($this->filtroDesde) {
            $query->whereDate('vc.venta_cuota_fecha', '>=', $this->filtroDesde);
        }
        if ($this->filtroHasta) {
            $query->whereDate('vc.venta_cuota_fecha', '<=', $this->filtroHasta);
        }

        if ($this->filtroVinculada === '1') {
            $query->whereRaw('ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo');
        } elseif ($this->filtroVinculada === '0') {
            $query->whereRaw('NOT (ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo)');
        }

        if ($this->filtroEstado === 'vencidas') {
            $query->whereRaw("vc.venta_cuota_fecha < ?", [$hoy])
                  ->whereRaw('COALESCE(pag.total_pagado, 0) < vc.venta_cuota_importe');
        } elseif ($this->filtroEstado === 'por_vencer') {
            $query->whereRaw("vc.venta_cuota_fecha >= ?", [$hoy])
                  ->whereRaw('COALESCE(pag.total_pagado, 0) < vc.venta_cuota_importe');
        } elseif ($this->filtroEstado === 'pagadas') {
            $query->whereRaw('COALESCE(pag.total_pagado, 0) >= vc.venta_cuota_importe');
        } elseif ($this->filtroEstado === 'pendientes') {
            $query->whereRaw('COALESCE(pag.total_pagado, 0) < vc.venta_cuota_importe');
        }

        return $query;
    }

    private function calcularAging(): array
    {
        $hoy        = now()->toDateString();
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        $subPagado = DB::table('pagos_cuotas as pc2')
            ->selectRaw('pc2.id_ventas_cuotas, SUM(pc2.pagos_cuota_monto) as total_pagado')
            ->whereNull('pc2.deleted_at')
            ->groupBy('pc2.id_ventas_cuotas');

        $base = DB::table('ventas_cuotas as vc')
            ->select(
                DB::raw('GREATEST(vc.venta_cuota_importe - COALESCE(pag.total_pagado, 0), 0) as saldo'),
                'vc.venta_cuota_fecha',
                'v.id_empresa',
                'v.id_sucursal'
            )
            ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
            ->leftJoinSub($subPagado, 'pag', 'pag.id_ventas_cuotas', '=', 'vc.id_ventas_cuotas')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->where('v.id_formas_pago', 2);

        if ($idSucursal > 0) {
            $base->where('v.id_sucursal', $idSucursal);
        } elseif ($idEmpresa) {
            $base->where('v.id_empresa', $idEmpresa);
        }

        $cuotas = $base->get();

        $aging = ['total_pendiente' => 0, 'corriente' => 0, 'dias_1_30' => 0, 'dias_31_60' => 0, 'dias_61_90' => 0, 'dias_mas_90' => 0];

        foreach ($cuotas as $c) {
            $saldo = (float) $c->saldo;
            if ($saldo <= 0) continue;

            $aging['total_pendiente'] += $saldo;
            $dias = max(0, now()->diffInDays($c->venta_cuota_fecha, false) * -1);

            if ($dias <= 0)       $aging['corriente']   += $saldo;
            elseif ($dias <= 30)  $aging['dias_1_30']   += $saldo;
            elseif ($dias <= 60)  $aging['dias_31_60']  += $saldo;
            elseif ($dias <= 90)  $aging['dias_61_90']  += $saldo;
            else                  $aging['dias_mas_90'] += $saldo;
        }

        return $aging;
    }

    public function exportarPdf(): void
    {
        if (!auth()->user()->can('cuentas_cobrar.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.');
            return;
        }
        $url = route('cxc.export_pdf') . '?' . http_build_query([
            'empresa'   => $this->empresaSeleccionada,
            'sucursal'  => $this->sucursalSeleccionada,
            'cliente'   => $this->filtroCliente,
            'desde'     => $this->filtroDesde,
            'hasta'     => $this->filtroHasta,
            'vinculada' => $this->filtroVinculada,
            'estado'    => $this->filtroEstado,
        ]);
        $this->dispatch('abrirEnlaces', url: $url);
    }

    public function exportarExcel(): void
    {
        if (!auth()->user()->can('cuentas_cobrar.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.');
            return;
        }
        $url = route('cxc.export_excel') . '?' . http_build_query([
            'empresa'   => $this->empresaSeleccionada,
            'sucursal'  => $this->sucursalSeleccionada,
            'cliente'   => $this->filtroCliente,
            'desde'     => $this->filtroDesde,
            'hasta'     => $this->filtroHasta,
            'vinculada' => $this->filtroVinculada,
            'estado'    => $this->filtroEstado,
        ]);
        $this->dispatch('abrirEnlaces', url: $url);
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $columnasPermitidas = ['venta_cuota_fecha', 'cliente_nombre', 'venta_cuota_importe', 'saldo', 'dias_atraso'];
        $columna   = in_array($this->ordenColumna, $columnasPermitidas) ? $this->ordenColumna : 'venta_cuota_fecha';
        $direccion = $this->ordenDireccion === 'desc' ? 'desc' : 'asc';

        $cuotas = $this->buildQuery()
            ->orderBy($columna, $direccion)
            ->paginate($this->porPagina);

        $aging = $this->calcularAging();

        return view('livewire.cxc.cuentas-cobrar', compact(
            'esSuperAdmin', 'esAdmin',
            'empresas',
            'cuotas', 'aging'
        ));
    }
}
