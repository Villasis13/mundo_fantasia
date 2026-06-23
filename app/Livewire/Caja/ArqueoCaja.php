<?php

namespace App\Livewire\Caja;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;

class ArqueoCaja extends Component
{
    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    public        $cajas                    = [];
    public int    $idCajaNumeroSeleccionada = 0;
    public string $filtroFecha              = '';

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
        if ($this->esSuperAdmin()) {
            return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : null;
        }
        return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : $this->empresaUsuario();
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('arqueo_caja.listar'), 403);

        $this->filtroFecha = now()->format('Y-m-d');

        if ($this->esSuperAdmin()) {
            $this->sucursalesDisponibles = DB::table('tiendas')
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get();
            $this->cargarCajasDisponibles();
            return;
        }

        $empresaId = $this->empresaUsuario();
        if ($empresaId) {
            $this->sucursalesDisponibles = DB::table('tiendas')
                ->where('id_empresa', $empresaId)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get();
        }

        if (!$this->esAdmin()) {
            $idTienda = DB::table('user_tienda as ut')
                ->where('ut.id_users', auth()->user()->id_users)
                ->value('ut.id_tienda');
            if ($idTienda) {
                $this->sucursalSeleccionada = (int) $idTienda;
                $this->cargarCajasDisponibles();
                $cajaHoy = DB::table('caja')
                    ->where('id_users_apertura', auth()->user()->id_users)
                    ->where('caja_fecha', now()->toDateString())
                    ->first();
                if ($cajaHoy) {
                    $this->idCajaNumeroSeleccionada = (int) $cajaHoy->id_caja_numero;
                }
            }
        } elseif ($empresaId) {
            $sucursales = $this->sucursalesDisponibles;
            if ($sucursales->count() === 1) {
                $this->sucursalSeleccionada = $sucursales->first()->id_tienda;
            }
            $this->cargarCajasDisponibles();
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada     = 0;
        $this->idCajaNumeroSeleccionada = 0;
        $this->buscado                  = false;

        if ($this->empresaSeleccionada > 0) {
            $this->sucursalesDisponibles = DB::table('tiendas')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get();
        } else {
            $this->sucursalesDisponibles = DB::table('tiendas')
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')
                ->get();
        }

        $this->cargarCajasDisponibles();
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->idCajaNumeroSeleccionada = 0;
        $this->buscado                  = false;
        $this->cargarCajasDisponibles();
    }

    public function updatedIdCajaNumeroSeleccionada(): void { $this->buscado = false; }
    public function updatedFiltroFecha(): void              { $this->buscado = false; }

    private function cargarCajasDisponibles(): void
    {
        $query = DB::table('caja_numero as cn')
            ->join('tiendas as t', 't.id_tienda', '=', 'cn.id_tienda')
            ->where('cn.caja_numero_estado', 1)
            ->orderBy('cn.caja_numero_nombre')
            ->select('cn.*');

        if ($this->sucursalSeleccionada > 0) {
            $query->where('cn.id_tienda', $this->sucursalSeleccionada);
        } elseif ($this->empresaSeleccionada > 0) {
            $query->where('t.id_empresa', $this->empresaSeleccionada);
        } elseif (!$this->esSuperAdmin()) {
            $idEmpresa = $this->empresaUsuario();
            if ($idEmpresa) {
                $query->where('t.id_empresa', $idEmpresa);
            }
        }

        $this->cajas = $query->get();

        if ($this->cajas->count() === 1) {
            $this->idCajaNumeroSeleccionada = $this->cajas[0]->id_caja_numero;
        }
    }

    public function generarArqueo(): void
    {
        $this->buscado = true;
    }

    public function exportarPdf(): void
    {
        if (!auth()->user()->can('arqueo_caja.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.');
            return;
        }

        if (!$this->idCajaNumeroSeleccionada) {
            session()->flash('error', 'Seleccione una caja específica para exportar el PDF.');
            return;
        }

        $url = route('caja_tesoreria.arqueo_caja_pdf', [
            'id_caja_numero' => $this->idCajaNumeroSeleccionada,
            'fecha'          => $this->filtroFecha,
        ]);
        $this->dispatch('abrirEnlaces', url: $url);
    }

    public function exportarExcel(): void
    {
        if (!auth()->user()->can('arqueo_caja.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.');
            return;
        }

        if (!$this->idCajaNumeroSeleccionada) {
            session()->flash('error', 'Seleccione una caja específica para exportar el Excel.');
            return;
        }

        $url = route('caja_tesoreria.arqueo_caja_excel', [
            'id_caja_numero' => $this->idCajaNumeroSeleccionada,
            'fecha'          => $this->filtroFecha,
        ]);
        $this->dispatch('abrirEnlaces', url: $url);
    }

    public function exportarTicket(): void
    {
        if (!auth()->user()->can('arqueo_caja.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.');
            return;
        }

        if (!$this->idCajaNumeroSeleccionada) {
            session()->flash('error', 'Seleccione una caja específica para exportar el ticket.');
            return;
        }

        $url = route('caja_tesoreria.arqueo_caja_ticket', [
            'id_caja_numero' => $this->idCajaNumeroSeleccionada,
            'fecha'          => $this->filtroFecha,
        ]);
        $this->dispatch('abrirEnlaces', url: $url);
    }

    private function calcularArqueo(): array
    {
        $fecha = $this->filtroFecha;

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
            if ($idEmpresa) {
                $cajasQuery->where('t.id_empresa', $idEmpresa);
            }
        }

        $idsCajaNumero = $cajasQuery->pluck('id_caja_numero')->toArray();

        if (empty($idsCajaNumero)) {
            return ['turnos' => collect(), 'resumen' => null, 'ventas' => collect(), 'movimientos' => collect(), 'ventasPorMedio' => collect(), 'pagosCuotas' => collect()];
        }

        $turnos = DB::table('caja as c')
            ->select('c.*', 'u_ap.nombre_users as nombre_apertura', 'u_ci.nombre_users as nombre_cierre',
                     'cn.caja_numero_nombre')
            ->join('users as u_ap', 'u_ap.id_users', '=', 'c.id_users_apertura')
            ->leftJoin('users as u_ci', 'u_ci.id_users', '=', 'c.id_users_cierre')
            ->join('caja_numero as cn', 'cn.id_caja_numero', '=', 'c.id_caja_numero')
            ->whereIn('c.id_caja_numero', $idsCajaNumero)
            ->whereDate('c.caja_fecha', $fecha)
            ->orderBy('cn.caja_numero_nombre')
            ->orderBy('c.caja_fecha_apertura')
            ->get();

        if ($turnos->isEmpty()) {
            return ['turnos' => collect(), 'resumen' => null, 'ventas' => collect(), 'movimientos' => collect(), 'ventasPorMedio' => collect(), 'pagosCuotas' => collect()];
        }

        $idsCaja = $turnos->pluck('id_caja')->toArray();

        $tiposPago   = DB::table('tipo_pago')->where('tipo_pago_estado', 1)->get(['id_tipo_pago', 'tipo_pago_nombre']);
        $idsEfectivo = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'efectivo') !== false)->pluck('id_tipo_pago')->toArray();
        $idsYape     = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'yape')     !== false)->pluck('id_tipo_pago')->toArray();
        $idsPlin     = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'plin')     !== false)->pluck('id_tipo_pago')->toArray();

        $baseVdp = fn() => DB::table('ventas_detalle_pagos as vdp')
            ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
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
            ->whereDate('gasto_fecha', $fecha)
            ->sum('gasto_monto');

        $ingresosGastos = (float) DB::table('gastos')
            ->where('gasto_estado', 1)
            ->where('gasto_tipo', 2)
            ->whereIn('id_caja_numero', $idsCajaNumero)
            ->whereDate('gasto_fecha', $fecha)
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
            ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
            ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.id_caja', $idsCaja)
            ->where('vdp.venta_detalle_pago_estado', 1)
            ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
            ->get();

        $totalVentas = $ventasPorMedio->sum('total');

        $pagosCuotas = DB::table('pagos_cuotas as pc')
            ->select('tp.tipo_pago_nombre', DB::raw('SUM(pc.pagos_cuota_monto) as total'))
            ->join('ventas_cuotas as vc', 'vc.id_ventas_cuotas', '=', 'pc.id_ventas_cuotas')
            ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
            ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'pc.id_tipo_pago')
            ->whereNull('pc.deleted_at')
            ->whereIn('v.id_caja', $idsCaja)
            ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
            ->get();

        $totalPagosCuotas = $pagosCuotas->sum('total');

        $movimientos = DB::table('caja_movimientos as cm')
            ->select('cm.*', 'u.nombre_users', 'tp.tipo_pago_nombre')
            ->join('users as u', 'u.id_users', '=', 'cm.id_users')
            ->leftJoin('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'cm.id_tipo_pago')
            ->whereNull('cm.deleted_at')
            ->whereIn('cm.id_caja', $idsCaja)
            ->orderBy('cm.created_at')
            ->get();

        $totalIngresos = $movimientos->where('tipo', 1)->sum('monto');
        $totalEgresos  = $movimientos->where('tipo', 2)->sum('monto');

        $ventas = DB::table('ventas as v')
            ->select('v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo',
                'v.venta_total', 'v.venta_fecha', 'c.cliente_nombre', 'c.cliente_razonsocial')
            ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.id_caja', $idsCaja)
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->orderBy('v.venta_fecha')
            ->get();

        $montoApertura = $turnos->sum('caja_apertura');
        $montoCierre   = $turnos->whereNotNull('caja_cierre')->sum('caja_cierre');
        $cajaAbierta   = $turnos->whereNull('caja_fecha_cierre')->isNotEmpty();

        $totalSistema = $montoApertura + $ventasEfectivo + $totalPagosCuotas + $totalIngresos + $ingresosGastos - $totalEgresos - $notasCredito - $gastos;
        $diferencia   = $cajaAbierta ? null : round($montoCierre - $totalSistema, 2);

        $resumen = (object) [
            'monto_apertura'     => $montoApertura,
            'total_ventas'       => $totalVentas,
            'ventas_efectivo'    => round($ventasEfectivo, 2),
            'ventas_yape'        => round($ventasYape,     2),
            'ventas_plin'        => round($ventasPlin,     2),
            'ventas_credito'     => round($ventasCredito,  2),
            'notas_credito'      => round($notasCredito,   2),
            'gastos'             => round($gastos,         2),
            'ingresos_gastos'    => round($ingresosGastos, 2),
            'total_pagos_cuotas' => $totalPagosCuotas,
            'total_ingresos'     => $totalIngresos,
            'total_egresos'      => $totalEgresos,
            'total_sistema'      => round($totalSistema,   2),
            'monto_cierre'       => $montoCierre,
            'diferencia'         => $diferencia,
            'caja_abierta'       => $cajaAbierta,
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

        $arqueo = null;
        if ($this->buscado) {
            $arqueo = $this->calcularArqueo();
        }

        if ($this->idCajaNumeroSeleccionada) {
            $nombreCaja = DB::table('caja_numero')->where('id_caja_numero', $this->idCajaNumeroSeleccionada)->value('caja_numero_nombre');
        } elseif ($this->sucursalSeleccionada) {
            $sede = DB::table('tiendas')->where('id_tienda', $this->sucursalSeleccionada)->value('tienda_nombre');
            $nombreCaja = 'Todas las cajas — ' . $sede;
        } elseif ($this->empresaSeleccionada) {
            $emp = DB::table('empresa')->where('id_empresa', $this->empresaSeleccionada)->value('empresa_nombrecomercial');
            $nombreCaja = 'Todas las cajas — ' . $emp;
        } else {
            $nombreCaja = 'Todas las cajas';
        }

        return view('livewire.caja.arqueo-caja', compact(
            'esSuperAdmin', 'esAdmin',
            'empresas',
            'arqueo', 'nombreCaja'
        ));
    }
}
