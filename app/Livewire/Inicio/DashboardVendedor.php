<?php

namespace App\Livewire\Inicio;

use App\Models\Logs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;

class DashboardVendedor extends Component
{
    // ── Sucursal activa ───────────────────────────────────────
    public int  $idSucursal                   = 0;
    public bool $necesitaSeleccionarSucursal  = false;
    public $sucursalesVendedor                = [];

    // ── Caja ─────────────────────────────────────────────────
    public $idCajaSeleccionada      = '';
    public $montoApertura           = '';
    public $montoCierre             = '';
    public $apertura                = null;
    public $cajas                   = [];
    public $mensajeCajaCerradaAyer  = null;

    // ── Paginación stock ──────────────────────────────────────
    public $stockPorPagina    = 5;
    public $stockPaginaActual = 1;

    private $logs;

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    public function mount(): void
    {
        $userId = auth()->user()->id_users;

        $sucursales = DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', $userId)
            ->where('s.sucursal_estado', 1)
            ->whereNull('s.deleted_at')
            ->select('s.id_sucursal', 's.sucursal_nombre')
            ->orderBy('s.sucursal_nombre')
            ->get()
            ->map(fn($s) => (object)['id_sucursal' => $s->id_sucursal, 'sucursal_nombre' => $s->sucursal_nombre]);

        $tiendas = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', $userId)
            ->where('t.tienda_estado', 1)
            ->select('t.id_tienda', 't.tienda_nombre')
            ->orderBy('t.tienda_nombre')
            ->get()
            ->map(fn($t) => (object)['id_sucursal' => -$t->id_tienda, 'sucursal_nombre' => $t->tienda_nombre]);

        $this->sucursalesVendedor = $sucursales->merge($tiendas)->sortBy('sucursal_nombre')->values();

        if ($this->sucursalesVendedor->count() === 0) {
            // Sin sucursal asignada: mostrar todas las cajas (comportamiento legacy)
            $this->cajas = DB::table('caja_numero')->where('caja_numero_estado', 1)->get();
            if ($this->cajas->isNotEmpty()) {
                $this->idCajaSeleccionada = $this->cajas->first()->id_caja_numero;
            }
        } elseif ($this->sucursalesVendedor->count() === 1) {
            // Una sola sucursal: preseleccionar automáticamente
            $this->idSucursal = $this->sucursalesVendedor->first()->id_sucursal;
            session(['sucursal_activa_id' => $this->idSucursal]);
            $this->cargarCajasPorSucursal();
        } else {
            // Múltiples sucursales: verificar si ya hay una en sesión
            $sucursalEnSesion = (int) session('sucursal_activa_id', 0);
            $valida = $this->sucursalesVendedor->firstWhere('id_sucursal', $sucursalEnSesion);

            if ($valida) {
                $this->idSucursal = $sucursalEnSesion;
                $this->cargarCajasPorSucursal();
            } else {
                $this->necesitaSeleccionarSucursal = true;
                $this->dispatch('abrirModalSucursal');
            }
        }

        if ($this->idSucursal) {
            $n = $this->cerrarCajasAnteriores();
            if ($n > 0) {
                $this->mensajeCajaCerradaAyer = "Se cerr" . ($n === 1 ? 'ó' : 'aron') . " automáticamente {$n} caja(s) del día anterior. Por favor apertura tu caja para continuar.";
            }
        }
        $this->apertura = $this->buscarAperturaCaja();
    }

    // ── Selección obligatoria de sucursal (modal) ─────────────
    public function seleccionarSucursal(int $idSucursal): void
    {
        $valida = $this->sucursalesVendedor->firstWhere('id_sucursal', $idSucursal);
        if (!$valida) return;

        $this->idSucursal                  = $idSucursal;
        $this->necesitaSeleccionarSucursal = false;
        session(['sucursal_activa_id' => $idSucursal]);
        $this->cargarCajasPorSucursal();
        $n = $this->cerrarCajasAnteriores();
        if ($n > 0) {
            $this->mensajeCajaCerradaAyer = "Se cerr" . ($n === 1 ? 'ó' : 'aron') . " automáticamente {$n} caja(s) del día anterior. Por favor apertura tu caja para continuar.";
        }
        $this->apertura = $this->buscarAperturaCaja();
        $this->dispatch('cerrarModalSucursal');
    }

    private function cargarCajasPorSucursal(): void
    {
        $hoy = Carbon::today()->toDateString();

        $q = DB::table('caja_numero as cn')
            ->leftJoin('caja as c', function ($join) use ($hoy) {
                $join->on('c.id_caja_numero', '=', 'cn.id_caja_numero')
                     ->where('c.caja_fecha', $hoy)
                     ->where('c.caja_estado', 1);
            })
            ->where('cn.caja_numero_estado', 1)
            ->orderBy('cn.caja_numero_nombre')
            ->select('cn.*', DB::raw('CASE WHEN c.id_caja IS NOT NULL THEN 1 ELSE 0 END as ya_abierta'));

        if ($this->idSucursal < 0) {
            $q->where('cn.id_tienda', -$this->idSucursal);
        } else {
            $q->where('cn.id_sucursal', $this->idSucursal);
        }

        $this->cajas = $q->get();

        $disponible = $this->cajas->firstWhere('ya_abierta', 0);
        $this->idCajaSeleccionada = $disponible ? $disponible->id_caja_numero : '';
    }

    public function render()
    {
        // ── Stock bajo ────────────────────────────────────────
        $offsetStock = ($this->stockPaginaActual - 1) * $this->stockPorPagina;

        if ($this->idSucursal > 0) {
            $totalStockBajo = DB::table('producto_sucursal as ps')
                ->join('productos as p', 'p.id_pro', '=', 'ps.id_pro')
                ->where('p.pro_estado', 1)
                ->where('ps.id_sucursal', $this->idSucursal)
                ->whereRaw('ps.ps_stock <= ps.ps_stock_minimo')
                ->count();

            $totalPaginasStock  = (int) ceil($totalStockBajo / $this->stockPorPagina);

            $productosStockBajo = DB::table('producto_sucursal as ps')
                ->join('productos as p', 'p.id_pro', '=', 'ps.id_pro')
                ->join('categorias as ca', 'ca.id_ca', '=', 'p.id_ca')
                ->select('p.pro_nombre', 'ps.ps_stock as stock_actual', 'ps.ps_stock_minimo', 'p.pro_codigo', 'ca.ca_nombre')
                ->where('p.pro_estado', 1)
                ->where('ps.id_sucursal', $this->idSucursal)
                ->whereRaw('ps.ps_stock <= ps.ps_stock_minimo')
                ->orderBy('ps.ps_stock', 'asc')
                ->offset($offsetStock)
                ->limit($this->stockPorPagina)
                ->get();
        } else {
            $totalStockBajo     = 0;
            $totalPaginasStock  = 0;
            $productosStockBajo = collect();
        }

        // ── Ventas del turno (caja activa del usuario) ───────
        $ventasTurno      = collect();
        $totalVentasTurno = 0.0;
        $ventasTurnoMedio = collect();

        if ($this->apertura) {
            $ventasTurno = DB::table('ventas as v')
                ->select('v.id_venta', 'v.venta_serie', 'v.venta_correlativo', 'v.venta_tipo',
                         'v.venta_total', 'v.venta_fecha',
                         'c.cliente_nombre', 'c.cliente_razonsocial', 'c.id_tipo_documento')
                ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')
                ->whereIn('v.venta_tipo', ['01', '03', '20'])
                ->where('v.id_caja', $this->apertura->id_caja)
                ->orderByDesc('v.venta_fecha')
                ->limit(20)
                ->get();

            $totalVentasTurno = (float) $ventasTurno->sum('venta_total');

            $ventasTurnoMedio = DB::table('ventas_detalle_pagos as vdp')
                ->select('tp.tipo_pago_nombre', DB::raw('SUM(vdp.venta_detalle_pago_monto) as total'))
                ->join('ventas as v',   'v.id_venta',     '=', 'vdp.id_venta')
                ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')
                ->where('vdp.venta_detalle_pago_estado', 1)
                ->where('v.id_caja', $this->apertura->id_caja)
                ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
                ->orderByDesc('total')
                ->get();
        }

        // ── SUNAT filtrado por sucursal activa ───────────────
        $idSucursalSunat = $this->idSucursal > 0 ? $this->idSucursal : null;

        $qPendientes = DB::table('ventas')
            ->whereIn('venta_tipo', ['01', '03', '07', '08'])
            ->where('venta_estado_sunat', 0)
            ->where('anulado_sunat', 0);
        if ($idSucursalSunat) $qPendientes->where('id_sucursal', $idSucursalSunat);
        $pendientesSunat = $qPendientes->count();

        $qAlertas = DB::table('ventas as v')
            ->select('v.id_venta', 'v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo',
                'v.venta_respuesta_sunat', 'v.venta_fecha')
            ->where('v.venta_estado_sunat', 1)
            ->where('v.anulado_sunat', 0)
            ->whereNotNull('v.venta_respuesta_sunat')
            ->where('v.venta_respuesta_sunat', '!=', '')
            ->where('v.venta_respuesta_sunat', 'not like', '%ha sido aceptad%')
            ->whereIn('v.venta_tipo', ['01', '03', '07', '08'])
            ->orderByDesc('v.venta_fecha');
        if ($idSucursalSunat) $qAlertas->where('v.id_sucursal', $idSucursalSunat);
        $comprobantesConAlerta = $qAlertas->limit(10)->get();

        // ── Saludo ────────────────────────────────────────────
        $hora   = (int) Carbon::now()->format('H');
        $saludo = $hora >= 6 && $hora < 12 ? 'Buenos días'
            : ($hora >= 12 && $hora < 18 ? 'Buenas tardes' : 'Buenas noches');

        $nombreUsuario = Auth::user()->nombre_users ?? '';

        // ── Nombre de sucursal activa ─────────────────────────
        $sucursalNombre = $this->idSucursal != 0
            ? ($this->sucursalesVendedor->firstWhere('id_sucursal', $this->idSucursal)?->sucursal_nombre ?? null)
            : null;

        $resumenCajaActual = $this->calcularResumenCajaActual();

        return view('livewire.inicio.dashboard-nuevo', compact(
            'productosStockBajo', 'totalStockBajo', 'totalPaginasStock',
            'pendientesSunat', 'comprobantesConAlerta',
            'ventasTurno', 'totalVentasTurno', 'ventasTurnoMedio',
            'saludo', 'nombreUsuario', 'sucursalNombre',
            'resumenCajaActual'
        ));
    }

    // ── Paginación stock ──────────────────────────────────────
    public function stockAnterior()
    {
        if ($this->stockPaginaActual > 1) $this->stockPaginaActual--;
    }

    public function stockSiguiente(int $totalPaginas)
    {
        if ($this->stockPaginaActual < $totalPaginas) $this->stockPaginaActual++;
    }

    private function calcularResumenCajaActual(): ?array
    {
        if (!$this->apertura) return null;

        $idCaja       = $this->apertura->id_caja;
        $idCajaNumero = $this->apertura->id_caja_numero;

        $tiposPago   = DB::table('tipo_pago')->where('tipo_pago_estado', 1)->get(['id_tipo_pago', 'tipo_pago_nombre']);
        $idsEfectivo = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'efectivo') !== false)->pluck('id_tipo_pago')->toArray();
        $idsYape     = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'yape')     !== false)->pluck('id_tipo_pago')->toArray();
        $idsPlin     = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'plin')     !== false)->pluck('id_tipo_pago')->toArray();

        $base = DB::table('ventas_detalle_pagos as vdp')
            ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->where('v.id_caja', $idCaja)
            ->where('vdp.venta_detalle_pago_estado', 1);

        $efectivo = (float) (clone $base)->when(!empty($idsEfectivo), fn($q) => $q->whereIn('vdp.id_tipo_pago', $idsEfectivo))->sum('vdp.venta_detalle_pago_monto');
        $yape     = (float) (clone $base)->when(!empty($idsYape),     fn($q) => $q->whereIn('vdp.id_tipo_pago', $idsYape)    )->sum('vdp.venta_detalle_pago_monto');
        $plin     = (float) (clone $base)->when(!empty($idsPlin),     fn($q) => $q->whereIn('vdp.id_tipo_pago', $idsPlin)    )->sum('vdp.venta_detalle_pago_monto');

        $nc = (float) DB::table('ventas as v')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->where('v.venta_tipo', '07')
            ->where('v.id_caja', $idCaja)
            ->sum('v.venta_total');

        $ventasCredito = (float) DB::table('ventas as v')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->where('v.id_caja', $idCaja)
            ->where('v.id_formas_pago', 2)
            ->sum('v.venta_total');

        $ventasPorMedio = DB::table('ventas_detalle_pagos as vdp')
            ->select('tp.tipo_pago_nombre', DB::raw('SUM(vdp.venta_detalle_pago_monto) as total'))
            ->join('ventas as v',    'v.id_venta',      '=', 'vdp.id_venta')
            ->join('tipo_pago as tp','tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->where('v.id_caja', $idCaja)
            ->where('vdp.venta_detalle_pago_estado', 1)
            ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
            ->get();

        $totalVentas = (float) $ventasPorMedio->sum('total');

        $pagosCuotasDetalle = DB::table('pagos_cuotas as pc')
            ->select('tp.tipo_pago_nombre', DB::raw('SUM(pc.pagos_cuota_monto) as total'))
            ->join('ventas_cuotas as vc', 'vc.id_ventas_cuotas', '=', 'pc.id_ventas_cuotas')
            ->join('ventas as v',         'v.id_venta',          '=', 'vc.id_venta')
            ->join('tipo_pago as tp',     'tp.id_tipo_pago',     '=', 'pc.id_tipo_pago')
            ->whereNull('pc.deleted_at')
            ->where('v.id_caja', $idCaja)
            ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
            ->get();

        $cobros   = (float) $pagosCuotasDetalle->sum('total');
        $ingresos = (float) DB::table('caja_movimientos')->whereNull('deleted_at')->where('id_caja', $idCaja)->where('tipo', 1)->sum('monto');
        $egresos  = (float) DB::table('caja_movimientos')->whereNull('deleted_at')->where('id_caja', $idCaja)->where('tipo', 2)->sum('monto');

        $gastos = (float) DB::table('gastos')
            ->where('gasto_estado', 1)
            ->where('gasto_tipo', 1)
            ->where('id_caja_numero', $idCajaNumero)
            ->whereDate('gasto_fecha', Carbon::today())
            ->sum('gasto_monto');

        $ingresosGastos = (float) DB::table('gastos')
            ->where('gasto_estado', 1)
            ->where('gasto_tipo', 2)
            ->where('id_caja_numero', $idCajaNumero)
            ->whereDate('gasto_fecha', Carbon::today())
            ->sum('gasto_monto');

        $montoApertura = (float) $this->apertura->caja_apertura;
        $totalSistema  = round($montoApertura + $efectivo + $cobros + $ingresos + $ingresosGastos - $egresos - $nc - $gastos, 2);
        $cajaAbierta   = (bool) ($this->apertura->caja_estado ?? true);
        $montoCierre   = isset($this->apertura->caja_cierre) && $this->apertura->caja_cierre !== null
            ? (float) $this->apertura->caja_cierre : null;
        $diferencia    = (!$cajaAbierta && $montoCierre !== null)
            ? round($montoCierre - $totalSistema, 2) : null;

        return [
            'apertura'           => $montoApertura,
            'efectivo'           => round($efectivo,       2),
            'yape'               => round($yape,           2),
            'plin'               => round($plin,           2),
            'nc'                 => round($nc,             2),
            'cobros'             => round($cobros,         2),
            'ingresos'           => round($ingresos,       2),
            'egresos'            => round($egresos,        2),
            'gastos'             => round($gastos,         2),
            'ingresos_gastos'    => round($ingresosGastos, 2),
            'ventas_credito'     => round($ventasCredito,  2),
            'total_ventas'       => round($totalVentas,    2),
            'total_sistema'      => $totalSistema,
            'caja_abierta'       => $cajaAbierta,
            'monto_cierre'       => $montoCierre,
            'diferencia'         => $diferencia,
            'ventasPorMedio'     => $ventasPorMedio,
            'pagosCuotasDetalle' => $pagosCuotasDetalle,
        ];
    }

    // ── Caja ─────────────────────────────────────────────────
    private function buscarAperturaCaja()
    {
        return DB::table('caja as c')
            ->select('c.*', 'cn.caja_numero_nombre', 'u.nombre_users as persona_nombre',
                's.sucursal_nombre')
            ->join('caja_numero as cn', 'cn.id_caja_numero', '=', 'c.id_caja_numero')
            ->join('users as u', 'u.id_users', '=', 'c.id_users_apertura')
            ->leftJoin('sucursals as s', 's.id_sucursal', '=', 'cn.id_sucursal')
            ->where('c.id_users_apertura', Auth::id())
            ->where('c.caja_fecha', Carbon::today()->toDateString())
            ->where('c.caja_estado', 1)
            ->first();
    }

    private function cerrarCajasAnteriores(): int
    {
        if (!$this->idSucursal) return 0;

        $q = DB::table('caja_numero')->where('caja_numero_estado', 1);
        if ($this->idSucursal < 0) {
            $q->where('id_tienda', -$this->idSucursal);
        } else {
            $q->where('id_sucursal', $this->idSucursal);
        }
        $idsCajaNumero = $q->pluck('id_caja_numero');

        if ($idsCajaNumero->isEmpty()) return 0;

        $ids = DB::table('caja')
            ->whereIn('id_caja_numero', $idsCajaNumero)
            ->where('caja_estado', 1)
            ->whereDate('caja_fecha', '<', Carbon::today()->toDateString())
            ->pluck('id_caja');

        if ($ids->isEmpty()) return 0;

        DB::table('caja')->whereIn('id_caja', $ids)->update([
            'id_users_cierre'   => Auth::id(),
            'caja_fecha_cierre' => Carbon::now()->toDateTimeString(),
            'caja_estado'       => 0,
            'updated_at'        => now(),
        ]);

        return $ids->count();
    }

    public function aperturarCaja()
    {
        if (!$this->idSucursal && $this->sucursalesVendedor->isNotEmpty()) {
            session()->flash('errorCaja', 'Debes seleccionar una sucursal primero.');
            return;
        }

        $this->validate([
            'idCajaSeleccionada' => 'required',
            'montoApertura'      => 'required|numeric|min:0',
        ], [
            'idCajaSeleccionada.required' => 'Seleccione una caja.',
            'montoApertura.required'      => 'Ingrese el monto de apertura.',
            'montoApertura.numeric'       => 'El monto debe ser un número válido.',
            'montoApertura.min'           => 'El monto no puede ser negativo.',
        ]);

        try {
            if (DB::table('caja')->where('id_users_apertura', Auth::id())->where('caja_fecha', Carbon::today()->toDateString())->where('caja_estado', 1)->exists()) {
                session()->flash('errorCaja', 'Ya tienes una caja aperturada hoy.');
                return;
            }
            if (DB::table('caja')->where('id_caja_numero', $this->idCajaSeleccionada)->where('caja_fecha', Carbon::today()->toDateString())->where('caja_estado', 1)->exists()) {
                session()->flash('errorCaja', 'Esta caja ya se encuentra aperturada.');
                return;
            }

            DB::table('caja')->insert([
                'id_caja_numero'      => $this->idCajaSeleccionada,
                'caja_fecha'          => Carbon::today()->toDateString(),
                'id_users_apertura'   => Auth::id(),
                'caja_apertura'       => $this->montoApertura,
                'caja_fecha_apertura' => Carbon::now()->toDateTimeString(),
                'caja_estado'         => 1,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            if ($this->idSucursal) {
                session(['sucursal_activa_id' => $this->idSucursal]);
            }

            $this->montoApertura = '';
            $this->apertura      = $this->buscarAperturaCaja();
            session()->flash('successCaja', 'Caja aperturada correctamente.');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('errorCaja', 'Ocurrió un error al aperturar la caja.');
        }
    }

    public function abrirModalCierre()
    {
        $this->montoCierre = '';
        $this->resetErrorBag('montoCierre');
        $this->dispatch('abrirModalCierre');
    }

    public function cerrarCaja()
    {
        $this->validate([
            'montoCierre' => 'required|numeric|min:0',
        ], [
            'montoCierre.required' => 'Ingrese el monto de cierre.',
            'montoCierre.numeric'  => 'El monto debe ser un número válido.',
            'montoCierre.min'      => 'El monto no puede ser negativo.',
        ]);

        try {
            if (!$this->apertura) {
                session()->flash('errorCaja', 'No se encontró la apertura de caja.');
                return;
            }

            DB::table('caja')->where('id_caja', $this->apertura->id_caja)->update([
                'id_users_cierre'   => Auth::id(),
                'caja_cierre'       => $this->montoCierre,
                'caja_fecha_cierre' => Carbon::now()->toDateTimeString(),
                'caja_estado'       => 0,
                'updated_at'        => now(),
            ]);

            $this->apertura    = null;
            $this->montoCierre = '';
            $this->dispatch('cerrarModalCierre');
            session()->flash('successCaja', 'Caja cerrada correctamente.');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('errorCaja', 'Ocurrió un error al cerrar la caja.');
        }
    }
}
