<?php

namespace App\Livewire\Inicio;

use App\Models\Logs;
use App\Models\Ventas;
use App\Models\PagosCuota;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;

class DashboardAdmin extends Component
{
    public $periodo = 'mes';

    public int $empresaSeleccionada  = 0;
    public int $sucursalSeleccionada = 0;
    public     $sucursalesDisponibles = [];

    public $idCajaSeleccionada     = '';
    public $montoApertura          = '';
    public $montoCierre            = '';
    public $apertura               = null;
    public $cajas                  = [];
    public $mensajeCajaCerradaAyer = null;

    public $stockPorPagina    = 5;
    public $stockPaginaActual = 1;

    private $logs;
    private $ventasModel;
    private $pagosCuotaModel;
    private int $cachedRoleId = 0;

    public function boot(): void
    {
        $this->logs            = new Logs();
        $this->ventasModel     = new Ventas();
        $this->pagosCuotaModel = new PagosCuota();

        if (auth()->check()) {
            $this->cachedRoleId = (int) DB::table('model_has_roles')
                ->where('model_id', auth()->user()->id_users)
                ->value('role_id');
        }
    }

    private function esAdmin(): bool         { return $this->cachedRoleId === 2; }
    private function esSuperAdmin(): bool    { return $this->cachedRoleId === 1; }
    private function esAdministrador(): bool { return $this->cachedRoleId === 3; }
    private function esPrivilegiado(): bool  { return in_array($this->cachedRoleId, [1, 2, 3]); }

    private function adminEmpresaId(): ?int
    {
        $id = DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->orderBy('us.id_sucursal')
            ->value('s.id_empresa');

        if ($id) return (int) $id;

        // Fallback: buscar empresa desde tiendas asignadas al usuario
        $id = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('t.id_empresa');

        return $id ? (int) $id : null;
    }

    private function resolverIdEmpresa(): ?int
    {
        if ($this->esPrivilegiado()) {
            return $this->empresaSeleccionada > 0 ? $this->empresaSeleccionada : null;
        }
        return null;
    }

    private function resolverIdSucursal(): int
    {
        return $this->sucursalSeleccionada > 0 ? $this->sucursalSeleccionada : 0;
    }

    private function cargarSedes(int $idEmpresa): \Illuminate\Support\Collection
    {
        $sucursals = DB::table('sucursals')
            ->where('id_empresa', $idEmpresa)
            ->where('sucursal_estado', 1)
            ->whereNull('deleted_at')
            ->orderBy('sucursal_nombre')
            ->get()
            ->map(fn($s) => (object)['id_sucursal' => $s->id_sucursal, 'sucursal_nombre' => $s->sucursal_nombre]);

        $tiendas = DB::table('tiendas')
            ->where('id_empresa', $idEmpresa)
            ->where('tienda_estado', 1)
            ->whereIn('tienda_tipo', [1, 2])
            ->whereNull('id_tienda_padre')
            ->orderBy('tienda_nombre')
            ->get()
            ->map(fn($t) => (object)['id_sucursal' => -$t->id_tienda, 'sucursal_nombre' => $t->tienda_nombre]);

        return $sucursals->merge($tiendas)->sortBy('sucursal_nombre')->values();
    }

    private function aplicarFiltroUbicacion(\Illuminate\Database\Query\Builder $query): void
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();
        if ($idEmpresa)      $query->where('v.id_empresa',  $idEmpresa);
        if ($idSucursal > 0) $query->where('v.id_sucursal', $idSucursal);
    }

    public function mount(): void
    {
        $this->sucursalesDisponibles = collect();

        if (!$this->esPrivilegiado()) {
            $this->preSeleccionarUbicacion();
        } elseif ($this->esSuperAdmin()) {
            $this->preSeleccionarSuperAdmin();
        } elseif ($this->esAdmin()) {
            $idEmpresa = $this->adminEmpresaId();
            if ($idEmpresa) {
                $this->empresaSeleccionada   = $idEmpresa;
                $this->sucursalesDisponibles = $this->cargarSedes($idEmpresa);
                $idSucursal = (int) DB::table('user_sucursal')
                    ->where('id_users', auth()->user()->id_users)
                    ->orderBy('id_sucursal')
                    ->value('id_sucursal');
                if ($idSucursal) $this->sucursalSeleccionada = $idSucursal;
            }
        } elseif ($this->esAdministrador()) {
            $idEmpresa = $this->adminEmpresaId();
            if ($idEmpresa) {
                $this->empresaSeleccionada   = $idEmpresa;
                $this->sucursalesDisponibles = $this->cargarSedes($idEmpresa);
                $idTienda = (int) DB::table('user_tienda')
                    ->where('id_users', auth()->user()->id_users)
                    ->orderBy('id_tienda')
                    ->value('id_tienda');
                if ($idTienda) $this->sucursalSeleccionada = -$idTienda;
            }
        }

        if ($this->sucursalSeleccionada != 0 && empty($this->cajas)) {
            $this->cargarCajasPorSucursal();
        }

        $n = $this->cerrarCajasAnteriores();
        if ($n > 0) {
            $this->mensajeCajaCerradaAyer = "Se cerr" . ($n === 1 ? 'ó' : 'aron') . " automáticamente {$n} caja(s) del día anterior. Por favor apertura tu caja para continuar.";
        }

        $this->apertura = $this->buscarAperturaCaja();
    }

    private function preSeleccionarSuperAdmin(): void
    {
        // Busca primera empresa asignada vía sucursal o tienda
        $idEmpresa = (int) DB::table('user_sucursal as us')
            ->join('sucursals as s', 's.id_sucursal', '=', 'us.id_sucursal')
            ->where('us.id_users', auth()->user()->id_users)
            ->orderBy('us.id_sucursal')
            ->value('s.id_empresa');

        if (!$idEmpresa) {
            $idEmpresa = (int) DB::table('user_tienda as ut')
                ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
                ->where('ut.id_users', auth()->user()->id_users)
                ->value('t.id_empresa');
        }

        if (!$idEmpresa) return;

        $this->empresaSeleccionada   = $idEmpresa;
        $this->sucursalesDisponibles = $this->cargarSedes($idEmpresa);

        // Intenta pre-seleccionar sucursal
        $idSucursal = (int) DB::table('user_sucursal')
            ->where('id_users', auth()->user()->id_users)
            ->orderBy('id_sucursal')
            ->value('id_sucursal');
        if ($idSucursal) {
            $this->sucursalSeleccionada = $idSucursal;
            return;
        }

        // Fallback: tienda
        $idTienda = (int) DB::table('user_tienda')
            ->where('id_users', auth()->user()->id_users)
            ->orderBy('id_tienda')
            ->value('id_tienda');
        if ($idTienda) $this->sucursalSeleccionada = -$idTienda;
    }

    private function preSeleccionarUbicacion(): void
    {
        $idTienda = 0;
        $sesion   = (int) session('sucursal_activa_id', 0);

        // Sesión negativa = -id_tienda (patrón Dashboard)
        if ($sesion < 0) {
            $idTienda = abs($sesion);
        }

        // Sin sesión tienda: buscar primera tienda asignada en BD
        if (!$idTienda) {
            $idTienda = (int) DB::table('user_tienda')
                ->where('id_users', auth()->user()->id_users)
                ->orderBy('id_tienda')
                ->value('id_tienda');
        }

        if ($idTienda > 0) {
            $empId = (int) DB::table('tiendas')->where('id_tienda', $idTienda)->value('id_empresa');
            if ($empId) {
                $this->empresaSeleccionada   = $empId;
                $this->sucursalesDisponibles = $this->cargarSedes($empId);
                $this->sucursalSeleccionada  = -$idTienda; // negativo = tienda (convención Dashboard)
                $this->cargarCajasPorSucursal();
            }
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        $this->cajas                = [];
        $this->idCajaSeleccionada   = '';

        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? $this->cargarSedes($this->empresaSeleccionada)
            : collect();
    }

    public function updatedSucursalSeleccionada(): void
    {
        $this->cajas              = [];
        $this->idCajaSeleccionada = '';
        if ($this->sucursalSeleccionada != 0) {
            $this->cargarCajasPorSucursal();
            $n = $this->cerrarCajasAnteriores();
            if ($n > 0) {
                $this->mensajeCajaCerradaAyer = "Se cerr" . ($n === 1 ? 'ó' : 'aron') . " automáticamente {$n} caja(s) del día anterior. Por favor apertura tu caja para continuar.";
            }
        }
        $this->apertura = $this->buscarAperturaCaja();
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

        if ($this->sucursalSeleccionada < 0) {
            $q->where('cn.id_tienda', -$this->sucursalSeleccionada);
        } else {
            $q->where('cn.id_sucursal', $this->sucursalSeleccionada);
        }

        $this->cajas = $q->get();

        $disponible = $this->cajas->firstWhere('ya_abierta', 0);
        $this->idCajaSeleccionada = $disponible ? $disponible->id_caja_numero : '';
    }

    public function render()
    {
        $esAdmin         = $this->esAdmin();
        $esSuperAdmin    = $this->esSuperAdmin();
        $esAdministrador = $this->esAdministrador();
        $esPrivilegiado  = $this->esPrivilegiado();
        $idEmpresaActiva = $this->resolverIdEmpresa();
        $idSucursal      = $this->resolverIdSucursal();

        [$desde, $hasta]       = $this->obtenerRango($this->periodo);
        [$desdeAnt, $hastaAnt] = $this->obtenerRangoMesAnterior();

        // ── Métricas período actual ───────────────────────────
        $ventasBrutas = $this->ventasModel->listarVentasPorTipo(['01','03'], $desde, $hasta, 2, $idEmpresaActiva, $idSucursal, false);
        $notasCredito = $this->ventasModel->listarVentasPorTipo(['07'], $desde, $hasta, 2, $idEmpresaActiva, $idSucursal, true);
        $notasDebito  = $this->ventasModel->listarVentasPorTipo(['08'], $desde, $hasta, 2, $idEmpresaActiva, $idSucursal, true);
        $pagosCuotas  = $this->pagosCuotaModel->listarPagosRealizados($desde, $hasta, 2, $idEmpresaActiva, $idSucursal);
        $notasVentas  = $this->ventasModel->listarVentasNotasVentas($desde, $hasta, 2, $idEmpresaActiva, $idSucursal);
        $ingresoNeto  = round($ventasBrutas - $notasCredito + $notasDebito + $pagosCuotas + $notasVentas, 2);

        // ── Comparativa mes anterior ──────────────────────────
        $ventasBrutasAnt = $this->ventasModel->listarVentasPorTipo(['01','03'], $desdeAnt, $hastaAnt, 2, $idEmpresaActiva, $idSucursal, false);
        $notasCreditoAnt = $this->ventasModel->listarVentasPorTipo(['07'], $desdeAnt, $hastaAnt, 2, $idEmpresaActiva, $idSucursal, true);
        $notasDebitoAnt  = $this->ventasModel->listarVentasPorTipo(['08'], $desdeAnt, $hastaAnt, 2, $idEmpresaActiva, $idSucursal, true);
        $pagosCuotasAnt  = $this->pagosCuotaModel->listarPagosRealizados($desdeAnt, $hastaAnt, 2, $idEmpresaActiva, $idSucursal);
        $notasVentasAnt  = $this->ventasModel->listarVentasNotasVentas($desdeAnt, $hastaAnt, 2, $idEmpresaActiva, $idSucursal);
        $ingresoNetoAnt  = round($ventasBrutasAnt - $notasCreditoAnt + $notasDebitoAnt + $pagosCuotasAnt + $notasVentasAnt, 2);

        $variacionIngreso = $ingresoNetoAnt > 0
            ? round((($ingresoNeto - $ingresoNetoAnt) / $ingresoNetoAnt) * 100, 1)
            : ($ingresoNeto > 0 ? 100 : 0);

        // ── Conteos filtrados por empresa ─────────────────────
        $queryClientes = DB::table('clientes')->where('cliente_estado', 1);
        if ($idEmpresaActiva) {
            $queryClientes->whereExists(function ($q) use ($idEmpresaActiva) {
                $q->select(DB::raw(1))
                    ->from('ventas as v')
                    ->whereColumn('v.id_clientes', 'clientes.id_clientes')
                    ->where('v.id_empresa', $idEmpresaActiva);
            });
        }
        $totalClientes = $queryClientes->count();

        $queryCompras = DB::table('orden_compra as oc')
            ->join('sucursals as s','s.id_sucursal','=','oc.id_sucursal')
            ->where('oc.orden_compra_estado', 1)
            ->whereBetween(DB::raw('DATE(oc.orden_compra_fecha)'), [$desde, $hasta]);
        if ($idEmpresaActiva) $queryCompras->where('s.id_empresa', $idEmpresaActiva);
        $totalCompras = $queryCompras->count();

        // ── Ventas por vendedor ───────────────────────────────
        $qVendedor = DB::table('ventas as v')
            ->select('u.nombre_users', DB::raw('SUM(v.venta_total) as total'), DB::raw('COUNT(v.id_venta) as cantidad'))
            ->join('users as u', 'u.id_users', '=', 'v.id_users')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->where('v.id_formas_pago', 1)
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);
        $this->aplicarFiltroUbicacion($qVendedor);
        $ventasPorVendedor = $qVendedor->groupBy('u.id_users', 'u.nombre_users')->orderByDesc('total')->get();

        // ── Ventas por caja ───────────────────────────────────
        $qCaja = DB::table('ventas as v')
            ->select('cn.caja_numero_nombre', DB::raw('SUM(v.venta_total) as total'), DB::raw('COUNT(v.id_venta) as cantidad'))
            ->join('caja as c', 'c.id_caja', '=', 'v.id_caja')
            ->join('caja_numero as cn', 'cn.id_caja_numero', '=', 'c.id_caja_numero')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->where('v.id_formas_pago', 1)
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);
        $this->aplicarFiltroUbicacion($qCaja);
        $ventasPorCaja = $qCaja->groupBy('cn.id_caja_numero', 'cn.caja_numero_nombre')->orderByDesc('total')->get();

        // ── Stock bajo (producto_sucursal) ────────────────────
        $offsetStock = ($this->stockPaginaActual - 1) * $this->stockPorPagina;

        if ($idSucursal > 0) {
            $totalStockBajo = DB::table('producto_sucursal as ps')
                ->join('productos as p', 'p.id_pro', '=', 'ps.id_pro')
                ->where('p.pro_estado', 1)
                ->where('ps.id_sucursal', $idSucursal)
                ->whereRaw('ps.ps_stock <= ps.ps_stock_minimo')
                ->count();

            $totalPaginasStock  = (int) ceil($totalStockBajo / $this->stockPorPagina);

            $productosStockBajo = DB::table('producto_sucursal as ps')
                ->select('p.pro_nombre', 'ps.ps_stock as stock_actual', 'ps.ps_stock_minimo', 'p.pro_codigo', 'ca.ca_nombre')
                ->join('productos as p',   'p.id_pro', '=', 'ps.id_pro')
                ->join('categorias as ca', 'ca.id_ca', '=', 'p.id_ca')
                ->where('p.pro_estado', 1)
                ->where('ps.id_sucursal', $idSucursal)
                ->whereRaw('ps.ps_stock <= ps.ps_stock_minimo')
                ->orderBy('ps.ps_stock', 'asc')
                ->offset($offsetStock)->limit($this->stockPorPagina)
                ->get();

        } elseif ($idEmpresaActiva) {
            $totalStockBajo = DB::table('producto_sucursal as ps')
                ->join('productos as p', 'p.id_pro',      '=', 'ps.id_pro')
                ->join('sucursals as s', 's.id_sucursal', '=', 'ps.id_sucursal')
                ->where('p.pro_estado', 1)
                ->where('s.id_empresa', $idEmpresaActiva)
                ->whereRaw('ps.ps_stock <= ps.ps_stock_minimo')
                ->count();

            $totalPaginasStock  = (int) ceil($totalStockBajo / $this->stockPorPagina);

            $productosStockBajo = DB::table('producto_sucursal as ps')
                ->select('p.pro_nombre', 'ps.ps_stock as stock_actual', 'ps.ps_stock_minimo',
                    'p.pro_codigo', 'ca.ca_nombre', 's.sucursal_nombre')
                ->join('productos as p',   'p.id_pro',      '=', 'ps.id_pro')
                ->join('categorias as ca', 'ca.id_ca',      '=', 'p.id_ca')
                ->join('sucursals as s',   's.id_sucursal', '=', 'ps.id_sucursal')
                ->where('p.pro_estado', 1)
                ->where('s.id_empresa', $idEmpresaActiva)
                ->whereRaw('ps.ps_stock <= ps.ps_stock_minimo')
                ->orderBy('ps.ps_stock', 'asc')
                ->offset($offsetStock)->limit($this->stockPorPagina)
                ->get();
        } else {
            $totalStockBajo     = 0;
            $totalPaginasStock  = 0;
            $productosStockBajo = collect();
        }

        // ── Cuotas filtradas por empresa/sucursal ─────────────
        $hoy = Carbon::today()->toDateString();

        $baseCuotas = function () use ($idEmpresaActiva, $idSucursal) {
            $q = DB::table('ventas_cuotas as vc')
                ->select('vc.*', 'v.venta_serie', 'v.venta_correlativo',
                    'c.cliente_nombre', 'c.cliente_razonsocial', 'c.id_tipo_documento')
                ->join('ventas as v',   'v.id_venta',    '=', 'vc.id_venta')
                ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
                ->where('vc.venta_cuota_pago', 0)
                ->where('vc.venta_cuota_estado', 1);
            if ($idEmpresaActiva) $q->where('v.id_empresa',  $idEmpresaActiva);
            if ($idSucursal > 0)  $q->where('v.id_sucursal', $idSucursal);
            return $q;
        };

        $cuotasVencidas = (clone $baseCuotas())
            ->where('vc.venta_cuota_fecha', '<', $hoy)
            ->orderBy('vc.venta_cuota_fecha', 'asc')->get();

        $cuotasPorVencer = (clone $baseCuotas())
            ->whereBetween('vc.venta_cuota_fecha', [$hoy, Carbon::today()->addDays(7)->toDateString()])
            ->orderBy('vc.venta_cuota_fecha', 'asc')->get();

        $totalCuotasVencidas  = $cuotasVencidas->sum('venta_cuota_importe');
        $totalCuotasPorVencer = $cuotasPorVencer->sum('venta_cuota_importe');

        // ── CxP vencida ───────────────────────────────────────
        $qCxpVencida = DB::table('cuentas_pagar')
            ->whereNull('deleted_at')
            ->whereNotIn('cp_estado', [0, 3])
            ->where('cp_fecha_vencimiento', '<', $hoy);
        if ($idEmpresaActiva) $qCxpVencida->where('id_empresa', $idEmpresaActiva);
        if ($idSucursal > 0)  $qCxpVencida->where('id_sucursal', $idSucursal);
        $totalCxpVencida  = (float) $qCxpVencida->sum('cp_saldo');
        $cantidadCxpVencida = (int) $qCxpVencida->count();

        // ── Top 15 productos más vendidos ────────────────────
        $qTopProd = DB::table('ventas_detalle as vd')
            ->join('ventas as v', 'v.id_venta', '=', 'vd.id_venta')
            ->join('productos as p', 'p.id_pro', '=', 'vd.id_pro')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);
        $this->aplicarFiltroUbicacion($qTopProd);
        $topProductos = $qTopProd
            ->select('p.pro_nombre', 'p.pro_codigo',
                DB::raw('SUM(vd.venta_detalle_cantidad) as total_cantidad'),
                DB::raw('SUM(vd.venta_detalle_importe_total) as total_importe'),
                DB::raw('COUNT(DISTINCT v.id_venta) as num_ventas'))
            ->groupBy('vd.id_pro', 'p.pro_nombre', 'p.pro_codigo')
            ->orderByDesc('total_cantidad')
            ->limit(15)
            ->get();

        // ── Productos sin rotación en el período ──────────────
        $qSinRot = DB::table('producto_sucursal as ps')
            ->join('productos as p', 'p.id_pro', '=', 'ps.id_pro')
            ->join('sucursals as s', 's.id_sucursal', '=', 'ps.id_sucursal')
            ->where('p.pro_estado', 1)
            ->where('ps.ps_estado', 1)
            ->where('ps.ps_stock', '>', 0);
        if ($idSucursal > 0) {
            $qSinRot->where('ps.id_sucursal', $idSucursal);
        } elseif ($idEmpresaActiva) {
            $qSinRot->where('s.id_empresa', $idEmpresaActiva);
        }
        $sinRotacion = $qSinRot->whereNotExists(function ($sub) use ($desde, $hasta, $idSucursal, $idEmpresaActiva) {
                $sub->select(DB::raw(1))
                    ->from('ventas_detalle as vd2')
                    ->join('ventas as v2', 'v2.id_venta', '=', 'vd2.id_venta')
                    ->leftJoin('ventas_anulados as va2', 'va2.id_venta', '=', 'v2.id_venta')
                    ->whereNull('va2.id_venta')
                    ->whereColumn('vd2.id_pro', 'ps.id_pro')
                    ->whereIn('v2.venta_tipo', ['01', '03', '20'])
                    ->whereBetween(DB::raw('DATE(v2.venta_fecha)'), [$desde, $hasta]);
                if ($idSucursal > 0) {
                    $sub->where('v2.id_sucursal', $idSucursal);
                } elseif ($idEmpresaActiva) {
                    $sub->where('v2.id_empresa', $idEmpresaActiva);
                }
            })
            ->select('p.pro_nombre', 'p.pro_codigo', 'ps.ps_stock', 's.sucursal_nombre')
            ->orderBy('p.pro_nombre')
            ->limit(20)
            ->get();

        // ── Rotación de productos vendidos ───────────────────
        $qVendidosRot = DB::table('ventas_detalle as vd')
            ->join('ventas as v', 'v.id_venta', '=', 'vd.id_venta')
            ->join('productos as p', 'p.id_pro', '=', 'vd.id_pro')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta])
            ->where('p.pro_estado', 1)
            ->select('p.pro_nombre', 'p.pro_codigo',
                DB::raw('SUM(vd.venta_detalle_cantidad) as total_qty'));
        $this->aplicarFiltroUbicacion($qVendidosRot);
        $todosVendidos = $qVendidosRot->groupBy('vd.id_pro', 'p.pro_nombre', 'p.pro_codigo')->get();

        $promedioQty   = $todosVendidos->isNotEmpty() ? (float) $todosVendidos->avg('total_qty') : 0;
        $grupoAlta     = $todosVendidos->filter(fn($p) => (float)$p->total_qty >= $promedioQty);
        $grupoBaja     = $todosVendidos->filter(fn($p) => (float)$p->total_qty <  $promedioQty);

        $rotacionAlta      = $grupoAlta->sortByDesc('total_qty')->values()->take(8);
        $rotacionBaja      = $grupoBaja->sortBy('total_qty')->values()->take(8);
        $totalRotacionAlta = $grupoAlta->count();
        $totalRotacionBaja = $grupoBaja->count();

        // ── Top 5 clientes del período ────────────────────────
        $qTop5 = DB::table('ventas as v')
            ->select('c.cliente_nombre', 'c.cliente_razonsocial', 'c.id_tipo_documento',
                     DB::raw('SUM(v.venta_total) as total'), DB::raw('COUNT(v.id_venta) as cantidad'))
            ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);
        $this->aplicarFiltroUbicacion($qTop5);
        $top5Clientes = $qTop5->groupBy('c.id_clientes', 'c.cliente_nombre', 'c.cliente_razonsocial', 'c.id_tipo_documento')
            ->orderByDesc('total')->limit(5)->get();

        // ── Ingresos por medio de pago (Efectivo, Yape y Plin) ───────
        $idsEfQr = DB::table('tipo_pago')
            ->where('tipo_pago_estado', 1)
            ->where(function ($q) {
                $q->where('tipo_pago_nombre', 'like', '%efectivo%')
                  ->orWhere('tipo_pago_nombre', 'like', '%yape%')
                  ->orWhere('tipo_pago_nombre', 'like', '%plin%');
            })
            ->pluck('id_tipo_pago');
        $qMedioPago = DB::table('ventas_detalle_pagos as vdp')
            ->select('tp.tipo_pago_nombre', DB::raw('SUM(vdp.venta_detalle_pago_monto) as total'))
            ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
            ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->where('vdp.venta_detalle_pago_estado', 1)
            ->whereIn('vdp.id_tipo_pago', $idsEfQr)
            ->whereBetween(DB::raw('DATE(v.venta_fecha)'), [$desde, $hasta]);
        if ($idEmpresaActiva) $qMedioPago->where('v.id_empresa', $idEmpresaActiva);
        if ($idSucursal > 0)  $qMedioPago->where('v.id_sucursal', $idSucursal);
        $ingresosPorMedio = $qMedioPago->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
            ->orderByDesc('total')->get();

        // ── SUNAT filtrado ────────────────────────────────────
        $qSunat = DB::table('ventas')
            ->whereIn('venta_tipo', ['01', '03', '07', '08'])
            ->where('venta_estado_sunat', 0)
            ->where('anulado_sunat', 0);
        if ($idEmpresaActiva) $qSunat->where('id_empresa',  $idEmpresaActiva);
        if ($idSucursal > 0)  $qSunat->where('id_sucursal', $idSucursal);
        $pendientesSunat = $qSunat->count();

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
        if ($idEmpresaActiva) $qAlertas->where('v.id_empresa',  $idEmpresaActiva);
        if ($idSucursal > 0)  $qAlertas->where('v.id_sucursal', $idSucursal);
        $comprobantesConAlerta = $qAlertas->limit(10)->get();


        // ── Productos sin movimiento en el período ────────────
        $subVendidos = DB::table('ventas_detalle as vd2')
            ->select('vd2.id_pro')
            ->join('ventas as v2', 'v2.id_venta', '=', 'vd2.id_venta')
            ->leftJoin('ventas_anulados as va2', 'va2.id_venta', '=', 'v2.id_venta')
            ->whereNull('va2.id_venta')
            ->whereIn('v2.venta_tipo', ['01', '03', '20'])
            ->whereBetween(DB::raw('DATE(v2.venta_fecha)'), [$desde, $hasta]);
        if ($idEmpresaActiva) $subVendidos->where('v2.id_empresa',  $idEmpresaActiva);
        if ($idSucursal > 0)  $subVendidos->where('v2.id_sucursal', $idSucursal);

        $qSinMovimiento = DB::table('productos as p')
            ->where('p.pro_estado', 1)
            ->whereNotIn('p.id_pro', $subVendidos);
        $totalSinMovimiento = $qSinMovimiento->count();

        // ── Gráficos ──────────────────────────────────────────
        $ventasPorMes    = $this->calcularVentasPorMesAnio();
        $graficoLinea    = $this->obtenerDatosGraficoIngresos($desde, $hasta);
        $graficoDonut    = $this->obtenerDatosGraficoDonut($desde, $hasta);
        $graficoVendedor = [
            'labels'  => $ventasPorVendedor->pluck('nombre_users')->values()->toArray(),
            'totales' => $ventasPorVendedor->pluck('total')->map(fn($v) => round((float)$v, 2))->values()->toArray(),
        ];
        $graficoCaja = [
            'labels'  => $ventasPorCaja->pluck('caja_numero_nombre')->values()->toArray(),
            'totales' => $ventasPorCaja->pluck('total')->map(fn($v) => round((float)$v, 2))->values()->toArray(),
        ];
        $graficoVentasMes = [
            'anio'      => $ventasPorMes['anio'],
            'labels'    => array_column($ventasPorMes['meses'], 'nombre'),
            'efectivo'  => array_map(fn($m) => $m['efectivo'], $ventasPorMes['meses']),
            'yape'      => array_map(fn($m) => $m['yape'],     $ventasPorMes['meses']),
            'plin'      => array_map(fn($m) => $m['plin'],     $ventasPorMes['meses']),
            'nc'        => array_map(fn($m) => $m['nc'],        $ventasPorMes['meses']),
            'gastos'    => array_map(fn($m) => $m['gastos'],    $ventasPorMes['meses']),
            'ingresos'  => array_map(fn($m) => $m['ingresos'],  $ventasPorMes['meses']),
            'neto'      => array_map(fn($m) => $m['neto'],      $ventasPorMes['meses']),
            'mesActual' => (int) Carbon::now()->month - 1,
        ];

        $graficoTipoPago = [
            'labels'  => $ingresosPorMedio->pluck('tipo_pago_nombre')->values()->toArray(),
            'totales' => $ingresosPorMedio->pluck('total')->map(fn($v) => round((float)$v, 2))->values()->toArray(),
        ];

        $this->dispatch('actualizarGraficos', [
            'linea'     => $graficoLinea,
            'donut'     => $graficoDonut,
            'vendedor'  => $graficoVendedor,
            'caja'      => $graficoCaja,
            'ventasMes' => $graficoVentasMes,
            'tipoPago'  => $graficoTipoPago,
            'periodo'   => $this->periodo,
        ]);

        // ── Saludo ────────────────────────────────────────────
        $hora          = (int) Carbon::now()->format('H');
        $saludo        = $hora >= 6 && $hora < 12 ? 'Buenos días' : ($hora >= 12 && $hora < 18 ? 'Buenas tardes' : 'Buenas noches');
        $nombreUsuario = Auth::user()->nombre_users ?? '';

        $empresas = $esPrivilegiado
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderByDesc('empresa_nombrecomercial')->get()
            : collect();

        $sucursalNombre = $idSucursal > 0
            ? DB::table('sucursals')->where('id_sucursal', $idSucursal)->value('sucursal_nombre')
            : null;
        $cuadreCajas        = $this->calcularCuadreCajasHoy();
        $resumenCajaActual  = $this->calcularResumenCajaActual();

        return view('livewire.inicio.dashboard-nuevo', compact(
            'ventasBrutas', 'notasCredito', 'notasDebito',
            'pagosCuotas', 'notasVentas', 'ingresoNeto',
            'ingresoNetoAnt', 'variacionIngreso',
            'totalClientes', 'totalCompras',
            'ventasPorVendedor', 'ventasPorCaja',
            'productosStockBajo', 'totalStockBajo', 'totalPaginasStock',
            'cuotasVencidas', 'cuotasPorVencer',
            'totalCuotasVencidas', 'totalCuotasPorVencer',
            'totalCxpVencida', 'cantidadCxpVencida',
            'topProductos', 'sinRotacion',
            'top5Clientes', 'ingresosPorMedio',
            'pendientesSunat', 'comprobantesConAlerta',
            'saludo', 'nombreUsuario',
            'esAdmin', 'esSuperAdmin', 'esAdministrador', 'esPrivilegiado', 'idEmpresaActiva',
            'empresas', 'sucursalNombre',
            'totalSinMovimiento',
            'rotacionAlta', 'rotacionBaja', 'totalRotacionAlta', 'totalRotacionBaja',
            'ventasPorMes', 'cuadreCajas', 'resumenCajaActual'
        ));
    }

    private function obtenerDatosGraficoIngresos(string $desde, string $hasta): array
    {
        $esPorHora  = $this->periodo === 'hoy';
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        $f = function (\Illuminate\Database\Query\Builder $q) use ($idEmpresa, $idSucursal) {
            if ($idEmpresa)      $q->where('v.id_empresa',  $idEmpresa);
            if ($idSucursal > 0) $q->where('v.id_sucursal', $idSucursal);
        };

        if ($esPorHora) {
            $datos = collect(range(0, 23))->map(function ($hora) use ($desde, $f) {

                $vb = DB::table('ventas as v')
                    ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                    ->whereNull('va.id_venta')->whereIn('v.venta_tipo', ['01', '03'])
                    ->where('v.id_formas_pago', 1)->whereDate('v.venta_fecha', $desde)
                    ->whereRaw('HOUR(v.venta_fecha) = ?', [$hora]);
                $f($vb); $vb = $vb->sum('v.venta_total');

                $nc = DB::table('ventas as v')
                    ->join('ventas as v_rel', fn($j) => $j->on('v_rel.venta_tipo','=','v.tipo_documento_modificar')->on('v_rel.venta_serie','=','v.serie_modificar')->on('v_rel.venta_correlativo','=','v.correlativo_modificar'))
                    ->leftJoin('ventas_anulados as va','va.id_venta','=','v.id_venta')
                    ->leftJoin('ventas_anulados as va_rel','va_rel.id_venta','=','v_rel.id_venta')
                    ->whereNull('va.id_venta')->whereNull('va_rel.id_venta')
                    ->where('v.venta_tipo','07')->where('v.id_formas_pago',1)
                    ->whereDate('v_rel.venta_fecha',$desde)->whereRaw('HOUR(v_rel.venta_fecha) = ?',[$hora]);
                $f($nc); $nc = $nc->sum('v.venta_total');

                $nd = DB::table('ventas as v')
                    ->join('ventas as v_rel', fn($j) => $j->on('v_rel.venta_tipo','=','v.tipo_documento_modificar')->on('v_rel.venta_serie','=','v.serie_modificar')->on('v_rel.venta_correlativo','=','v.correlativo_modificar'))
                    ->leftJoin('ventas_anulados as va','va.id_venta','=','v.id_venta')
                    ->leftJoin('ventas_anulados as va_rel','va_rel.id_venta','=','v_rel.id_venta')
                    ->whereNull('va.id_venta')->whereNull('va_rel.id_venta')
                    ->where('v.venta_tipo','08')->where('v.id_formas_pago',1)
                    ->whereDate('v_rel.venta_fecha',$desde)->whereRaw('HOUR(v_rel.venta_fecha) = ?',[$hora]);
                $f($nd); $nd = $nd->sum('v.venta_total');

                $nv = DB::table('ventas as v')
                    ->where('v.anulado_sunat',0)->where('v.venta_cancelar',1)->where('v.venta_tipo','20')
                    ->whereDate('v.venta_fecha',$desde)->whereRaw('HOUR(v.venta_fecha) = ?',[$hora]);
                $f($nv); $nv = $nv->sum('v.venta_total');

                $pc = DB::table('pagos_cuotas')
                    ->join('ventas_cuotas as vc','vc.id_ventas_cuotas','=','pagos_cuotas.id_ventas_cuotas')
                    ->join('ventas as v','v.id_venta','=','vc.id_venta')
                    ->where('v.anulado_sunat',0)->whereDate('pagos_cuotas.pagos_cuota_fecha',$desde)
                    ->whereRaw('HOUR(pagos_cuotas.created_at) = ?',[$hora]);
                $f($pc); $pc = $pc->sum('pagos_cuotas.pagos_cuota_monto');

                return ['label' => str_pad($hora,2,'0',STR_PAD_LEFT).':00', 'total' => round((float)$vb-(float)$nc+(float)$nd+(float)$nv+(float)$pc,2)];
            });

        } else {
            $current = Carbon::parse($desde)->copy();
            $fin     = Carbon::parse($hasta);
            $dias    = [];
            while ($current->lte($fin)) { $dias[] = $current->toDateString(); $current->addDay(); }

            $datos = collect($dias)->map(function ($dia) use ($f) {

                $vb = DB::table('ventas as v')
                    ->leftJoin('ventas_anulados as va','va.id_venta','=','v.id_venta')
                    ->whereNull('va.id_venta')->whereIn('v.venta_tipo',['01','03'])
                    ->where('v.id_formas_pago',1)->whereDate('v.venta_fecha',$dia);
                $f($vb); $vb = $vb->sum('v.venta_total');

                $nc = DB::table('ventas as v')
                    ->join('ventas as v_rel', fn($j) => $j->on('v_rel.venta_tipo','=','v.tipo_documento_modificar')->on('v_rel.venta_serie','=','v.serie_modificar')->on('v_rel.venta_correlativo','=','v.correlativo_modificar'))
                    ->leftJoin('ventas_anulados as va','va.id_venta','=','v.id_venta')
                    ->leftJoin('ventas_anulados as va_rel','va_rel.id_venta','=','v_rel.id_venta')
                    ->whereNull('va.id_venta')->whereNull('va_rel.id_venta')
                    ->where('v.venta_tipo','07')->where('v.id_formas_pago',1)->whereDate('v_rel.venta_fecha',$dia);
                $f($nc); $nc = $nc->sum('v.venta_total');

                $nd = DB::table('ventas as v')
                    ->join('ventas as v_rel', fn($j) => $j->on('v_rel.venta_tipo','=','v.tipo_documento_modificar')->on('v_rel.venta_serie','=','v.serie_modificar')->on('v_rel.venta_correlativo','=','v.correlativo_modificar'))
                    ->leftJoin('ventas_anulados as va','va.id_venta','=','v.id_venta')
                    ->leftJoin('ventas_anulados as va_rel','va_rel.id_venta','=','v_rel.id_venta')
                    ->whereNull('va.id_venta')->whereNull('va_rel.id_venta')
                    ->where('v.venta_tipo','08')->where('v.id_formas_pago',1)->whereDate('v_rel.venta_fecha',$dia);
                $f($nd); $nd = $nd->sum('v.venta_total');

                $nv = DB::table('ventas as v')
                    ->where('v.anulado_sunat',0)->where('v.venta_cancelar',1)->where('v.venta_tipo','20')->whereDate('v.venta_fecha',$dia);
                $f($nv); $nv = $nv->sum('v.venta_total');

                $pc = DB::table('pagos_cuotas')
                    ->join('ventas_cuotas as vc','vc.id_ventas_cuotas','=','pagos_cuotas.id_ventas_cuotas')
                    ->join('ventas as v','v.id_venta','=','vc.id_venta')
                    ->where('v.anulado_sunat',0)->whereDate('pagos_cuotas.pagos_cuota_fecha',$dia);
                $f($pc); $pc = $pc->sum('pagos_cuotas.pagos_cuota_monto');

                return ['label' => Carbon::parse($dia)->format('d/m'), 'total' => round((float)$vb-(float)$nc+(float)$nd+(float)$nv+(float)$pc,2)];
            });
        }

        return ['labels' => $datos->pluck('label')->values()->toArray(), 'totales' => $datos->pluck('total')->values()->toArray(), 'esPorHora' => $esPorHora];
    }

    private function obtenerDatosGraficoDonut(string $desde, string $hasta): array
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        $resultado = [
            ['label' => 'Facturas',       'tipo' => '01', 'nc' => false],
            ['label' => 'Boletas',        'tipo' => '03', 'nc' => false],
            ['label' => 'Notas de Venta', 'tipo' => '20', 'nc' => true],
        ];

        $labels = $totales = [];
        foreach ($resultado as $r) {
            $q = DB::table('ventas as v')
                ->leftJoin('ventas_anulados as va','va.id_venta','=','v.id_venta')
                ->whereNull('va.id_venta')->where('v.venta_tipo',$r['tipo'])
                ->whereBetween(DB::raw('DATE(v.venta_fecha)'),[$desde,$hasta]);
            if (!$r['nc']) { $q->where('v.id_formas_pago',1); }
            else           { $q->where('v.anulado_sunat',0)->where('v.venta_cancelar',1); }
            if ($idEmpresa)      $q->where('v.id_empresa',  $idEmpresa);
            if ($idSucursal > 0) $q->where('v.id_sucursal', $idSucursal);
            $labels[]  = $r['label'];
            $totales[] = round((float)$q->sum('v.venta_total'),2);
        }

        return compact('labels','totales');
    }

    public function cambiarPeriodo(string $periodo): void
    {
        $this->periodo           = $periodo;
        $this->stockPaginaActual = 1;
    }

    public function exportarVentasMes(int $mes): void
    {
        $params = array_filter([
            'mes'         => $mes,
            'anio'        => (int) now()->year,
            'id_empresa'  => $this->resolverIdEmpresa(),
            'id_sucursal' => $this->resolverIdSucursal() ?: null,
        ], fn($v) => $v !== null);

        $this->dispatch('abrirEnlaces', url: route('reporte.excel_ventas_mes', $params));
    }

    public function stockAnterior(): void
    {
        if ($this->stockPaginaActual > 1) $this->stockPaginaActual--;
    }

    public function stockSiguiente(int $totalPaginas): void
    {
        if ($this->stockPaginaActual < $totalPaginas) $this->stockPaginaActual++;
    }

    private function obtenerRango(string $periodo): array
    {
        return match($periodo) {
            'hoy'    => [Carbon::today()->toDateString(), Carbon::today()->toDateString()],
            'semana' => [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->toDateString()],
            default  => [Carbon::now()->startOfMonth()->toDateString(), Carbon::now()->endOfMonth()->toDateString()],
        };
    }

    private function obtenerRangoMesAnterior(): array
    {
        return [Carbon::now()->subMonth()->startOfMonth()->toDateString(), Carbon::now()->subMonth()->endOfMonth()->toDateString()];
    }

    private function buscarAperturaCaja()
    {
        return DB::table('caja as c')
            ->select('c.*','cn.caja_numero_nombre','u.nombre_users as persona_nombre','s.sucursal_nombre')
            ->join('caja_numero as cn','cn.id_caja_numero','=','c.id_caja_numero')
            ->join('users as u','u.id_users','=','c.id_users_apertura')
            ->leftJoin('sucursals as s','s.id_sucursal','=','cn.id_sucursal')
            ->where('c.id_users_apertura', Auth::id())
            ->where('c.caja_fecha', Carbon::today()->toDateString())
            ->where('c.caja_estado', 1)
            ->first();
    }

    private function cerrarCajasAnteriores(): int
    {
        $idSucursal = $this->sucursalSeleccionada;
        if (!$idSucursal) return 0;

        $q = DB::table('caja_numero as cn')
            ->join('caja as c', 'c.id_caja_numero', '=', 'cn.id_caja_numero')
            ->where('cn.caja_numero_estado', 1)
            ->where('c.caja_estado', 1)
            ->whereDate('c.caja_fecha', '<', Carbon::today()->toDateString());

        if ($idSucursal < 0) {
            $q->where('cn.id_tienda', -$idSucursal);
        } else {
            $q->where('cn.id_sucursal', $idSucursal);
        }

        $ids = $q->pluck('c.id_caja');
        if ($ids->isEmpty()) return 0;

        DB::table('caja')->whereIn('id_caja', $ids)->update([
            'id_users_cierre'   => Auth::id(),
            'caja_fecha_cierre' => Carbon::now()->toDateTimeString(),
            'caja_estado'       => 0,
            'updated_at'        => now(),
        ]);

        return $ids->count();
    }

    private function calcularVentasPorMesAnio(): array
    {
        $anio       = (int) Carbon::now()->year;
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();

        $tiposPago   = DB::table('tipo_pago')->where('tipo_pago_estado', 1)->get(['id_tipo_pago', 'tipo_pago_nombre']);
        $idsEfectivo = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'efectivo') !== false)->pluck('id_tipo_pago')->toArray();
        $idsYape     = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'yape')     !== false)->pluck('id_tipo_pago')->toArray();
        $idsPlin     = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'plin')     !== false)->pluck('id_tipo_pago')->toArray();

        // Ventas contado (efectivo+Yape+Plin) por mes
        $qVentas = DB::table('ventas_detalle_pagos as vdp')
            ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->where('vdp.venta_detalle_pago_estado', 1)
            ->whereYear('v.venta_fecha', $anio)
            ->select(DB::raw('MONTH(v.venta_fecha) as mes'), 'vdp.id_tipo_pago', DB::raw('SUM(vdp.venta_detalle_pago_monto) as total'))
            ->groupBy(DB::raw('MONTH(v.venta_fecha)'), 'vdp.id_tipo_pago');
        if ($idEmpresa)      $qVentas->where('v.id_empresa',  $idEmpresa);
        if ($idSucursal > 0) $qVentas->where('v.id_sucursal', $idSucursal);
        $ventasMes = $qVentas->get();

        // Notas de crédito por mes
        $qNC = DB::table('ventas as v')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->where('v.venta_tipo', '07')
            ->whereYear('v.venta_fecha', $anio)
            ->select(DB::raw('MONTH(v.venta_fecha) as mes'), DB::raw('SUM(v.venta_total) as total'))
            ->groupBy(DB::raw('MONTH(v.venta_fecha)'));
        if ($idEmpresa)      $qNC->where('v.id_empresa',  $idEmpresa);
        if ($idSucursal > 0) $qNC->where('v.id_sucursal', $idSucursal);
        $ncMes = $qNC->get()->keyBy('mes');

        // Gastos (módulo Logística) por mes
        $qGastos = DB::table('gastos')
            ->where('gasto_estado', 1)
            ->where('gasto_tipo', 1)
            ->whereYear('gasto_fecha', $anio)
            ->select(DB::raw('MONTH(gasto_fecha) as mes'), DB::raw('SUM(gasto_monto) as total'))
            ->groupBy(DB::raw('MONTH(gasto_fecha)'));
        if ($idEmpresa)      $qGastos->where('id_empresa', $idEmpresa);
        if ($idSucursal < 0) $qGastos->where('id_tienda', -$idSucursal);
        $gastosMes = $qGastos->get()->keyBy('mes');

        // Ingresos (módulo Gastos/Ingresos) por mes
        $qIngresos = DB::table('gastos')
            ->where('gasto_estado', 1)
            ->where('gasto_tipo', 2)
            ->whereYear('gasto_fecha', $anio)
            ->select(DB::raw('MONTH(gasto_fecha) as mes'), DB::raw('SUM(gasto_monto) as total'))
            ->groupBy(DB::raw('MONTH(gasto_fecha)'));
        if ($idEmpresa)      $qIngresos->where('id_empresa', $idEmpresa);
        if ($idSucursal < 0) $qIngresos->where('id_tienda', -$idSucursal);
        $ingresosMes = $qIngresos->get()->keyBy('mes');

        $nombMeses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        $mesActual = (int) Carbon::now()->month;

        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $ef   = (float) $ventasMes->where('mes', $m)->whereIn('id_tipo_pago', $idsEfectivo)->sum('total');
            $yape = (float) $ventasMes->where('mes', $m)->whereIn('id_tipo_pago', $idsYape)->sum('total');
            $plin = (float) $ventasMes->where('mes', $m)->whereIn('id_tipo_pago', $idsPlin)->sum('total');
            $nc   = (float) ($ncMes->get($m)?->total     ?? 0);
            $gs   = (float) ($gastosMes->get($m)?->total  ?? 0);
            $ing  = (float) ($ingresosMes->get($m)?->total ?? 0);
            $meses[] = [
                'nombre'   => $nombMeses[$m - 1],
                'mes'      => $m,
                'efectivo' => round($ef,   2),
                'yape'     => round($yape, 2),
                'plin'     => round($plin, 2),
                'nc'       => round($nc,   2),
                'gastos'   => round($gs,   2),
                'ingresos' => round($ing,  2),
                'neto'     => round($ef + $yape + $plin + $ing - $nc - $gs, 2),
                'futuro'   => $m > $mesActual,
                'actual'   => $m === $mesActual,
            ];
        }

        return ['anio' => $anio, 'meses' => $meses];
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

    private function calcularCuadreCajasHoy(): array
    {
        $hoy       = Carbon::today()->toDateString();
        $idEmpresa = $this->resolverIdEmpresa();
        $idSucursal= $this->resolverIdSucursal();

        $tiposPago   = DB::table('tipo_pago')->where('tipo_pago_estado', 1)->get(['id_tipo_pago', 'tipo_pago_nombre']);
        $idsEfectivo = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'efectivo') !== false)->pluck('id_tipo_pago')->toArray();
        $idsYape     = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'yape')     !== false)->pluck('id_tipo_pago')->toArray();
        $idsPlin     = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'plin')     !== false)->pluck('id_tipo_pago')->toArray();

        $qCajas = DB::table('caja as c')
            ->join('caja_numero as cn', 'cn.id_caja_numero', '=', 'c.id_caja_numero')
            ->join('users as u', 'u.id_users', '=', 'c.id_users_apertura')
            ->leftJoin('tiendas as t', 't.id_tienda', '=', 'cn.id_tienda')
            ->where('c.caja_fecha', $hoy)
            ->select('c.*', 'cn.caja_numero_nombre', 'u.nombre_users as cajero', 't.tienda_nombre')
            ->orderBy('cn.caja_numero_nombre');

        if ($idEmpresa)        $qCajas->where('t.id_empresa', $idEmpresa);
        if ($idSucursal < 0)   $qCajas->where('cn.id_tienda', -$idSucursal);
        elseif ($idSucursal > 0) $qCajas->where('cn.id_sucursal', $idSucursal);

        $cajasHoy = $qCajas->get();
        $resultado = [];

        foreach ($cajasHoy as $caja) {
            $idCaja = $caja->id_caja;

            $ventasEf  = (float) DB::table('ventas_detalle_pagos as vdp')
                ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')->whereIn('v.venta_tipo', ['01','03','20'])
                ->where('v.id_caja', $idCaja)->where('vdp.venta_detalle_pago_estado', 1)
                ->when(!empty($idsEfectivo), fn($q) => $q->whereIn('vdp.id_tipo_pago', $idsEfectivo))
                ->sum('vdp.venta_detalle_pago_monto');

            $ventasYape = (float) DB::table('ventas_detalle_pagos as vdp')
                ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')->whereIn('v.venta_tipo', ['01','03','20'])
                ->where('v.id_caja', $idCaja)->where('vdp.venta_detalle_pago_estado', 1)
                ->when(!empty($idsYape), fn($q) => $q->whereIn('vdp.id_tipo_pago', $idsYape))
                ->sum('vdp.venta_detalle_pago_monto');

            $ventasPlin = (float) DB::table('ventas_detalle_pagos as vdp')
                ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')->whereIn('v.venta_tipo', ['01','03','20'])
                ->where('v.id_caja', $idCaja)->where('vdp.venta_detalle_pago_estado', 1)
                ->when(!empty($idsPlin), fn($q) => $q->whereIn('vdp.id_tipo_pago', $idsPlin))
                ->sum('vdp.venta_detalle_pago_monto');

            $ventasCred = (float) DB::table('ventas as v')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')->whereIn('v.venta_tipo', ['01','03','20'])
                ->where('v.id_caja', $idCaja)->where('v.id_formas_pago', 2)
                ->sum('v.venta_total');

            $cobrosCuotas = (float) DB::table('pagos_cuotas as pc')
                ->join('ventas_cuotas as vc', 'vc.id_ventas_cuotas', '=', 'pc.id_ventas_cuotas')
                ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
                ->whereNull('pc.deleted_at')->where('v.id_caja', $idCaja)
                ->sum('pc.pagos_cuota_monto');

            $ingresos = (float) DB::table('caja_movimientos')
                ->whereNull('deleted_at')->where('id_caja', $idCaja)->where('tipo', 1)->sum('monto');

            $egresos  = (float) DB::table('caja_movimientos')
                ->whereNull('deleted_at')->where('id_caja', $idCaja)->where('tipo', 2)->sum('monto');

            $nc = (float) DB::table('ventas as v')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')
                ->where('v.venta_tipo', '07')
                ->where('v.id_caja', $idCaja)
                ->sum('v.venta_total');

            $gastos = (float) DB::table('gastos')
                ->where('gasto_estado', 1)
                ->where('gasto_tipo', 1)
                ->where('id_caja_numero', $caja->id_caja_numero)
                ->whereDate('gasto_fecha', $hoy)
                ->sum('gasto_monto');

            $ingresosGastos = (float) DB::table('gastos')
                ->where('gasto_estado', 1)
                ->where('gasto_tipo', 2)
                ->where('id_caja_numero', $caja->id_caja_numero)
                ->whereDate('gasto_fecha', $hoy)
                ->sum('gasto_monto');

            $apertura        = (float) $caja->caja_apertura;
            $totalSistema    = round($apertura + $ventasEf + $cobrosCuotas + $ingresos + $ingresosGastos - $egresos - $nc - $gastos, 2);
            $montoCierre     = $caja->caja_cierre !== null ? (float) $caja->caja_cierre : null;
            $diferencia      = $montoCierre !== null ? round($montoCierre - $totalSistema, 2) : null;

            $resultado[] = [
                'caja_numero_nombre' => $caja->caja_numero_nombre,
                'tienda_nombre'      => $caja->tienda_nombre,
                'cajero'             => $caja->cajero,
                'abierta'            => is_null($caja->caja_fecha_cierre),
                'apertura'           => $apertura,
                'ventas_efectivo'    => round($ventasEf,    2),
                'ventas_yape'        => round($ventasYape,  2),
                'ventas_plin'        => round($ventasPlin,  2),
                'ventas_credito'     => round($ventasCred,  2),
                'cobros_cuotas'      => round($cobrosCuotas,2),
                'ingresos'           => round($ingresos,       2),
                'egresos'            => round($egresos,      2),
                'notas_credito'      => round($nc,           2),
                'gastos'             => round($gastos,       2),
                'ingresos_gastos'    => round($ingresosGastos, 2),
                'total_sistema'      => $totalSistema,
                'monto_cierre'       => $montoCierre,
                'diferencia'         => $diferencia,
            ];
        }

        return $resultado;
    }

    public function aperturarCaja(): void
    {
        if (!$this->sucursalSeleccionada) {
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
            if (DB::table('caja')->where('id_users_apertura',Auth::id())->where('caja_fecha',Carbon::today()->toDateString())->where('caja_estado',1)->exists()) {
                session()->flash('errorCaja','Ya tienes una caja aperturada hoy.'); return;
            }
            if (DB::table('caja')->where('id_caja_numero',$this->idCajaSeleccionada)->where('caja_fecha',Carbon::today()->toDateString())->where('caja_estado',1)->exists()) {
                session()->flash('errorCaja','Esta caja ya se encuentra aperturada.'); return;
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
            session(['sucursal_activa_id' => $this->sucursalSeleccionada]);
            $this->montoApertura = '';
            $this->apertura      = $this->buscarAperturaCaja();
            session()->flash('successCaja','Caja aperturada correctamente.');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('errorCaja','Ocurrió un error al aperturar la caja.');
        }
    }

    public function abrirModalCierre(): void
    {
        $this->montoCierre = '';
        $this->resetErrorBag('montoCierre');
        $this->dispatch('abrirModalCierre');
    }

    public function cerrarCaja(): void
    {
        $this->validate(['montoCierre' => 'required|numeric|min:0'], [
            'montoCierre.required' => 'Ingrese el monto de cierre.',
            'montoCierre.numeric'  => 'El monto debe ser un número válido.',
            'montoCierre.min'      => 'El monto no puede ser negativo.',
        ]);
        try {
            if (!$this->apertura) { session()->flash('errorCaja','No se encontró la apertura de caja.'); return; }
            DB::table('caja')->where('id_caja',$this->apertura->id_caja)->update([
                'id_users_cierre'   => Auth::id(),
                'caja_cierre'       => $this->montoCierre,
                'caja_fecha_cierre' => Carbon::now()->toDateTimeString(),
                'caja_estado'       => 0,
                'updated_at'        => now(),
            ]);
            $this->apertura    = null;
            $this->montoCierre = '';
            $this->dispatch('cerrarModalCierre');
            session()->flash('successCaja','Caja cerrada correctamente.');
        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('errorCaja','Ocurrió un error al cerrar la caja.');
        }
    }
}
