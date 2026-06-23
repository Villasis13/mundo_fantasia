<?php

namespace App\Livewire\Inicio;

use App\Models\Logs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;

class DashboardContador extends Component
{
    private $logs;

    public function boot()
    {
        $this->logs = new Logs();
    }

    public function render()
    {
        // Filtrar por sucursal activa en sesión (igual que otras vistas)
        $idSucursal = (int) session('sucursal_activa_id', 0);

        // Obtener empresa desde la sucursal activa
        $idEmpresa = null;
        if ($idSucursal > 0) {
            $idEmpresa = DB::table('sucursals')->where('id_sucursal', $idSucursal)->value('id_empresa');
            $idEmpresa = $idEmpresa ? (int) $idEmpresa : null;
        }

        $hoy = Carbon::today()->toDateString();

        // ── CxC pendientes ────────────────────────────────────
        $qCxc = DB::table('ventas_cuotas as vc')
            ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
            ->where('vc.venta_cuota_pago', 0)
            ->where('vc.venta_cuota_estado', 1);
        if ($idEmpresa)      $qCxc->where('v.id_empresa',  $idEmpresa);
        if ($idSucursal > 0) $qCxc->where('v.id_sucursal', $idSucursal);

        $totalCxcPendiente    = (float) (clone $qCxc)->sum('vc.venta_cuota_importe');
        $cantidadCxcPendiente = (int)   (clone $qCxc)->count();
        $totalCxcVencida      = (float) (clone $qCxc)->where('vc.venta_cuota_fecha', '<', $hoy)->sum('vc.venta_cuota_importe');
        $cantidadCxcVencida   = (int)   (clone $qCxc)->where('vc.venta_cuota_fecha', '<', $hoy)->count();

        // ── CxP pendientes ────────────────────────────────────
        $qCxp = DB::table('cuentas_pagar')
            ->whereNull('deleted_at')
            ->whereNotIn('cp_estado', [0, 3]);
        if ($idEmpresa)      $qCxp->where('id_empresa',  $idEmpresa);
        if ($idSucursal > 0) $qCxp->where('id_sucursal', $idSucursal);

        $totalCxpPendiente    = (float) (clone $qCxp)->sum('cp_saldo');
        $cantidadCxpPendiente = (int)   (clone $qCxp)->count();
        $totalCxpVencida      = (float) (clone $qCxp)->where('cp_fecha_vencimiento', '<', $hoy)->sum('cp_saldo');
        $cantidadCxpVencida   = (int)   (clone $qCxp)->where('cp_fecha_vencimiento', '<', $hoy)->count();

        // ── SUNAT — pendientes filtrados por empresa ──────────
        $qPendientes = DB::table('ventas')
            ->whereIn('venta_tipo', ['01', '03', '07', '08'])
            ->where('venta_estado_sunat', 0)
            ->where('anulado_sunat', 0);
        if ($idEmpresa)      $qPendientes->where('id_empresa',  $idEmpresa);
        if ($idSucursal > 0) $qPendientes->where('id_sucursal', $idSucursal);
        $pendientesSunat = $qPendientes->count();

        // ── SUNAT — respuestas fuera del patrón ──────────────
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
        if ($idEmpresa)      $qAlertas->where('v.id_empresa',  $idEmpresa);
        if ($idSucursal > 0) $qAlertas->where('v.id_sucursal', $idSucursal);
        $comprobantesConAlerta = $qAlertas->get(); // Sin limit para que el contador vea todos

        // ── Totales por tipo para el resumen ──────────────────
        $qResumen = DB::table('ventas as v')
            ->select('v.venta_tipo', DB::raw('COUNT(*) as cantidad'))
            ->whereIn('v.venta_tipo', ['01', '03', '07', '08'])
            ->where('v.venta_estado_sunat', 0)
            ->where('v.anulado_sunat', 0);
        if ($idEmpresa)      $qResumen->where('v.id_empresa',  $idEmpresa);
        if ($idSucursal > 0) $qResumen->where('v.id_sucursal', $idSucursal);
        $resumenPorTipo = $qResumen->groupBy('v.venta_tipo')->get()->keyBy('venta_tipo');

        // ── Saludo ────────────────────────────────────────────
        $hora   = (int) Carbon::now()->format('H');
        $saludo = $hora >= 6 && $hora < 12 ? 'Buenos días'
            : ($hora >= 12 && $hora < 18 ? 'Buenas tardes' : 'Buenas noches');

        $nombreUsuario = Auth::user()->nombre_users ?? '';

        return view('livewire.inicio.dashboard-nuevo', compact(
            'pendientesSunat', 'comprobantesConAlerta', 'resumenPorTipo',
            'totalCxcPendiente', 'cantidadCxcPendiente', 'totalCxcVencida', 'cantidadCxcVencida',
            'totalCxpPendiente', 'cantidadCxpPendiente', 'totalCxpVencida', 'cantidadCxpVencida',
            'saludo', 'nombreUsuario'
        ));
    }
}
