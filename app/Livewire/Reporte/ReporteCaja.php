<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReporteCaja extends Component
{
    private int $cachedRoleId          = 0;
    public int  $empresaSeleccionada   = 0;
    public int  $sucursalSeleccionada  = 0;
    public      $sucursalesDisponibles = [];

    public int    $idCajaNumeroSeleccionada = 0;
    public        $cajas                    = [];
    public string $filtroDesde              = '';
    public string $filtroHasta              = '';

    public bool $buscado = false;

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

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reporte_caja.listar'), 403);

        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');

        $empresaId = $this->empresaUsuario();
        if ($empresaId) {
            $this->empresaSeleccionada   = $empresaId;
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
                $this->cargarCajas();
            }
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0; $this->cajas = []; $this->idCajaNumeroSeleccionada = 0; $this->buscado = false;
        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')->where('id_empresa', $this->empresaSeleccionada)->where('tienda_estado', 1)->orderBy('tienda_nombre')->get()
            : collect();
        $this->cargarCajas();
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->cajas = []; $this->idCajaNumeroSeleccionada = 0; $this->buscado = false;
        $this->cargarCajas();
    }

    public function updatedIdCajaNumeroSeleccionada(): void { $this->buscado = false; }
    public function updatedFiltroDesde(): void              { $this->buscado = false; }
    public function updatedFiltroHasta(): void              { $this->buscado = false; }

    private function cargarCajas(): void
    {
        $query = DB::table('caja_numero as cn')
            ->join('tiendas as t', 't.id_tienda', '=', 'cn.id_tienda')
            ->where('cn.caja_numero_estado', 1)
            ->orderBy('cn.caja_numero_nombre')
            ->select('cn.*');

        if ($this->sucursalSeleccionada > 0) {
            $query->where('cn.id_tienda', $this->sucursalSeleccionada);
        } else {
            $idEmpresa = $this->resolverIdEmpresa();
            if ($idEmpresa) $query->where('t.id_empresa', $idEmpresa);
        }

        $this->cajas = $query->get();
        if ($this->cajas->count() === 1) {
            $this->idCajaNumeroSeleccionada = $this->cajas[0]->id_caja_numero;
        }
    }

    public function generar(): void
    {
        $this->buscado = true;
    }

    public function exportarPdf(): void
    {
        if (!auth()->user()->can('reporte_caja.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.'); return;
        }
        $url = route('reporte.reporte_caja_pdf', $this->buildParams());
        $this->dispatch('abrirEnlaces', url: $url);
    }

    public function exportarExcel(): void
    {
        if (!auth()->user()->can('reporte_caja.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.'); return;
        }
        $url = route('reporte.reporte_caja_excel', $this->buildParams());
        $this->dispatch('abrirEnlaces', url: $url);
    }

    private function buildParams(): array
    {
        return [
            'id_caja_numero' => $this->idCajaNumeroSeleccionada,
            'desde'          => $this->filtroDesde,
            'hasta'          => $this->filtroHasta,
        ];
    }

    public function calcularReporte(): ?array
    {
        $desde = $this->filtroDesde;
        $hasta = $this->filtroHasta;

        // Resolver qué caja_numero(s) aplican
        $cajasQuery = DB::table('caja_numero as cn')
            ->join('tiendas as t', 't.id_tienda', '=', 'cn.id_tienda')
            ->where('cn.caja_numero_estado', 1)
            ->select('cn.id_caja_numero', 'cn.caja_numero_nombre');

        if ($this->idCajaNumeroSeleccionada > 0) {
            $cajasQuery->where('cn.id_caja_numero', $this->idCajaNumeroSeleccionada);
        } elseif ($this->sucursalSeleccionada > 0) {
            $cajasQuery->where('cn.id_tienda', $this->sucursalSeleccionada);
        } else {
            $idEmpresa = $this->resolverIdEmpresa();
            if ($idEmpresa) $cajasQuery->where('t.id_empresa', $idEmpresa);
        }

        $idsCajaNumero = $cajasQuery->pluck('id_caja_numero')->toArray();
        if (empty($idsCajaNumero)) return null;

        // Turnos en el rango
        $turnos = DB::table('caja as c')
            ->select('c.*', 'u_ap.nombre_users as nombre_apertura', 'u_ci.nombre_users as nombre_cierre', 'cn.caja_numero_nombre')
            ->join('users as u_ap',     'u_ap.id_users',     '=', 'c.id_users_apertura')
            ->leftJoin('users as u_ci', 'u_ci.id_users',     '=', 'c.id_users_cierre')
            ->join('caja_numero as cn', 'cn.id_caja_numero', '=', 'c.id_caja_numero')
            ->whereIn('c.id_caja_numero', $idsCajaNumero)
            ->whereBetween(DB::raw('DATE(c.caja_fecha)'), [$desde, $hasta])
            ->orderBy('cn.caja_numero_nombre')
            ->orderBy('c.caja_fecha_apertura')
            ->get();

        if ($turnos->isEmpty()) return null;

        $idsCaja = $turnos->pluck('id_caja')->toArray();

        // Detectar tipos de pago efectivo / Yape / Plin
        $tiposPago   = DB::table('tipo_pago')->where('tipo_pago_estado', 1)->get(['id_tipo_pago', 'tipo_pago_nombre']);
        $idsEfectivo = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'efectivo') !== false)->pluck('id_tipo_pago')->toArray();
        $idsYape     = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'yape')     !== false)->pluck('id_tipo_pago')->toArray();
        $idsPlin     = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'plin')     !== false)->pluck('id_tipo_pago')->toArray();

        $baseVdp = fn() => DB::table('ventas_detalle_pagos as vdp')
            ->join('ventas as v',              'v.id_venta',  '=', 'vdp.id_venta')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereIn('v.id_caja', $idsCaja)
            ->where('vdp.venta_detalle_pago_estado', 1);

        $ventasEfectivo = !empty($idsEfectivo)
            ? (float) $baseVdp()->whereIn('vdp.id_tipo_pago', $idsEfectivo)->sum('vdp.venta_detalle_pago_monto')
            : 0.0;

        $ventasYape = !empty($idsYape)
            ? (float) $baseVdp()->whereIn('vdp.id_tipo_pago', $idsYape)->sum('vdp.venta_detalle_pago_monto')
            : 0.0;

        $ventasPlin = !empty($idsPlin)
            ? (float) $baseVdp()->whereIn('vdp.id_tipo_pago', $idsPlin)->sum('vdp.venta_detalle_pago_monto')
            : 0.0;

        $notasCredito = (float) DB::table('ventas as v')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->where('v.venta_tipo', '07')
            ->whereIn('v.id_caja', $idsCaja)
            ->sum('v.venta_total');

        $gastos = (float) DB::table('gastos')
            ->where('gasto_estado', 1)
            ->where('gasto_tipo', 1)
            ->whereIn('id_caja_numero', $idsCajaNumero)
            ->whereBetween(DB::raw('DATE(gasto_fecha)'), [$desde, $hasta])
            ->sum('gasto_monto');

        $ingresosGastos = (float) DB::table('gastos')
            ->where('gasto_estado', 1)
            ->where('gasto_tipo', 2)
            ->whereIn('id_caja_numero', $idsCajaNumero)
            ->whereBetween(DB::raw('DATE(gasto_fecha)'), [$desde, $hasta])
            ->sum('gasto_monto');

        $ventasCredito = (float) DB::table('ventas as v')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereIn('v.id_caja', $idsCaja)
            ->where('v.id_formas_pago', 2)
            ->sum('v.venta_total');

        $ventasPorMedio = DB::table('ventas_detalle_pagos as vdp')
            ->select('tp.tipo_pago_nombre', DB::raw('SUM(vdp.venta_detalle_pago_monto) as total'))
            ->join('ventas as v',              'v.id_venta',      '=', 'vdp.id_venta')
            ->join('tipo_pago as tp',          'tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
            ->leftJoin('ventas_anulados as va', 'va.id_venta',     '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.id_caja', $idsCaja)
            ->where('vdp.venta_detalle_pago_estado', 1)
            ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
            ->get();

        $totalVentas = $ventasPorMedio->sum('total');

        $pagosCuotas = DB::table('pagos_cuotas as pc')
            ->select('tp.tipo_pago_nombre', DB::raw('SUM(pc.pagos_cuota_monto) as total'))
            ->join('ventas_cuotas as vc', 'vc.id_ventas_cuotas', '=', 'pc.id_ventas_cuotas')
            ->join('ventas as v',         'v.id_venta',          '=', 'vc.id_venta')
            ->join('tipo_pago as tp',     'tp.id_tipo_pago',     '=', 'pc.id_tipo_pago')
            ->whereNull('pc.deleted_at')
            ->whereIn('v.id_caja', $idsCaja)
            ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
            ->get();

        $totalPagosCuotas = $pagosCuotas->sum('total');

        $movimientos = DB::table('caja_movimientos as cm')
            ->select('cm.*', 'u.nombre_users', 'tp.tipo_pago_nombre')
            ->join('users as u',              'u.id_users',      '=', 'cm.id_users')
            ->leftJoin('tipo_pago as tp',     'tp.id_tipo_pago', '=', 'cm.id_tipo_pago')
            ->whereNull('cm.deleted_at')
            ->whereIn('cm.id_caja', $idsCaja)
            ->orderBy('cm.created_at')
            ->get();

        $totalIngresos = $movimientos->where('tipo', 1)->sum('monto');
        $totalEgresos  = $movimientos->where('tipo', 2)->sum('monto');

        $ventas = DB::table('ventas as v')
            ->select(
                'v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo',
                'v.venta_total', 'v.venta_fecha',
                'c.cliente_nombre', 'c.cliente_razonsocial',
                'cn.caja_numero_nombre'
            )
            ->join('clientes as c',           'c.id_clientes',     '=', 'v.id_clientes')
            ->join('caja as ca',              'ca.id_caja',        '=', 'v.id_caja')
            ->join('caja_numero as cn',       'cn.id_caja_numero', '=', 'ca.id_caja_numero')
            ->leftJoin('ventas_anulados as va','va.id_venta',       '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.id_caja', $idsCaja)
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->orderBy('v.venta_fecha')
            ->get();

        $montoApertura = $turnos->sum('caja_apertura');
        $totalSistema  = $montoApertura + $ventasEfectivo + $totalPagosCuotas
                       + $totalIngresos + $ingresosGastos
                       - $totalEgresos  - $notasCredito   - $gastos;

        $resumen = (object) [
            'monto_apertura'     => $montoApertura,
            'total_ventas'       => $totalVentas,
            'ventas_efectivo'    => round($ventasEfectivo,   2),
            'ventas_yape'        => round($ventasYape,       2),
            'ventas_plin'        => round($ventasPlin,       2),
            'ventas_credito'     => round($ventasCredito,    2),
            'notas_credito'      => round($notasCredito,     2),
            'gastos'             => round($gastos,           2),
            'ingresos_gastos'    => round($ingresosGastos,   2),
            'total_pagos_cuotas' => $totalPagosCuotas,
            'total_ingresos'     => $totalIngresos,
            'total_egresos'      => $totalEgresos,
            'total_sistema'      => round($totalSistema,     2),
        ];

        return compact('turnos', 'resumen', 'ventas', 'movimientos', 'ventasPorMedio', 'pagosCuotas');
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $reporte = $this->buscado ? $this->calcularReporte() : null;

        if ($this->idCajaNumeroSeleccionada) {
            $nombreCaja = DB::table('caja_numero')->where('id_caja_numero', $this->idCajaNumeroSeleccionada)->value('caja_numero_nombre');
        } elseif ($this->sucursalSeleccionada) {
            $sede       = DB::table('tiendas')->where('id_tienda', $this->sucursalSeleccionada)->value('tienda_nombre');
            $nombreCaja = 'Todas las cajas — ' . $sede;
        } else {
            $nombreCaja = 'Todas las cajas';
        }

        return view('livewire.reporte.reporte-caja', compact(
            'esSuperAdmin', 'esAdmin', 'empresas', 'reporte', 'nombreCaja'
        ));
    }
}
