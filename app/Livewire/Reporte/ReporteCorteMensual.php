<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReporteCorteMensual extends Component
{
    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    public string $filtroAnio = '';
    public bool   $buscado    = false;

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
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('t.id_empresa');
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
            ->where('ut.id_users', auth()->user()->id_users)
            ->value('ut.id_tienda');
        return $id ? (int) $id : 0;
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reporte_corte_mensual.listar'), 403);
        $this->filtroAnio = now()->format('Y');

        $empresaId = $this->empresaUsuario();
        if ($empresaId) {
            $this->empresaSeleccionada   = $empresaId;
            $this->sucursalesDisponibles = DB::table('tiendas')
                ->where('id_empresa', $empresaId)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')->get();
        }

        if (!$this->esSuperAdmin() && !$this->esAdmin()) {
            $idTienda = DB::table('user_tienda as ut')
                ->where('ut.id_users', auth()->user()->id_users)
                ->value('ut.id_tienda');
            if ($idTienda) $this->sucursalSeleccionada = (int) $idTienda;
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0;
        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')->get()
            : collect();
        $this->buscado = false;
    }

    public function updatedSucursalSeleccionada(): void { $this->buscado = false; }

    public function generar(): void { $this->buscado = true; }

    public function imprimirPdf(): void
    {
        if (!auth()->user()->can('reporte_corte_mensual.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.');
            return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.reporte_corte_mensual_pdf', $this->buildParams()));
    }

    public function imprimirExcel(): void
    {
        if (!auth()->user()->can('reporte_corte_mensual.exportar')) {
            session()->flash('error', 'Sin permiso para exportar.');
            return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.reporte_corte_mensual_excel', $this->buildParams()));
    }

    private function buildParams(): array
    {
        return array_filter([
            'id_empresa'  => $this->resolverIdEmpresa(),
            'id_sucursal' => $this->resolverIdSucursal() ?: null,
            'anio'        => $this->filtroAnio ?: null,
        ]);
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();
        $idEmpresa    = $this->resolverIdEmpresa();
        $idSucursal   = $this->resolverIdSucursal();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', 0)->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $meses   = collect();
        $totales = null;

        if ($this->buscado) {
            $anio = $this->filtroAnio ?: now()->format('Y');

            // Compras por mes
            $qCompras = DB::table('orden_compra as oc')
                ->join('tiendas as t', 't.id_tienda', '=', 'oc.id_sucursal')
                ->selectRaw('MONTH(oc.orden_compra_fecha) as mes,
                    COUNT(oc.id_orden_compra) as num_ordenes,
                    SUM(oc.orden_compra_total) as total_mercaderia,
                    SUM(COALESCE(oc.orden_compra_flete,0)) as total_flete,
                    SUM(COALESCE(oc.orden_compra_gastos_operativos,0)) as total_gastos,
                    SUM(oc.orden_compra_total + COALESCE(oc.orden_compra_flete,0) + COALESCE(oc.orden_compra_gastos_operativos,0)) as gran_total_compras')
                ->whereYear('oc.orden_compra_fecha', $anio)
                ->where('oc.orden_compra_activo', 1);
            if ($idSucursal > 0) $qCompras->where('oc.id_sucursal', $idSucursal);
            elseif ($idEmpresa)  $qCompras->where('t.id_empresa', $idEmpresa);
            $comprasPorMes = $qCompras->groupByRaw('MONTH(oc.orden_compra_fecha)')->get()->keyBy('mes');

            // Ventas por mes
            $qVentas = DB::table('ventas as v')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->selectRaw('MONTH(v.venta_fecha) as mes,
                    COUNT(v.id_venta) as num_ventas,
                    SUM(v.venta_total) as total_ventas')
                ->whereNull('va.id_venta')
                ->whereIn('v.venta_tipo', ['01', '03', '20'])
                ->whereYear('v.venta_fecha', $anio);
            if ($idSucursal > 0) $qVentas->where('v.id_sucursal', $idSucursal);
            elseif ($idEmpresa)  $qVentas->where('v.id_empresa', $idEmpresa);
            $ventasPorMes = $qVentas->groupByRaw('MONTH(v.venta_fecha)')->get()->keyBy('mes');

            $nombresMeses = [
                1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
            ];

            foreach (range(1, 12) as $m) {
                $c = $comprasPorMes->get($m);
                $v = $ventasPorMes->get($m);
                $meses->push((object)[
                    'mes_num'           => $m,
                    'mes_nombre'        => $nombresMeses[$m],
                    'num_ordenes'       => $c->num_ordenes ?? 0,
                    'total_mercaderia'  => (float)($c->total_mercaderia ?? 0),
                    'total_flete'       => (float)($c->total_flete ?? 0),
                    'total_gastos'      => (float)($c->total_gastos ?? 0),
                    'gran_total_compras'=> (float)($c->gran_total_compras ?? 0),
                    'num_ventas'        => $v->num_ventas ?? 0,
                    'total_ventas'      => (float)($v->total_ventas ?? 0),
                    'diferencia'        => (float)($v->total_ventas ?? 0) - (float)($c->gran_total_compras ?? 0),
                ]);
            }

            $totales = (object)[
                'num_ordenes'        => $meses->sum('num_ordenes'),
                'total_mercaderia'   => $meses->sum('total_mercaderia'),
                'total_flete'        => $meses->sum('total_flete'),
                'total_gastos'       => $meses->sum('total_gastos'),
                'gran_total_compras' => $meses->sum('gran_total_compras'),
                'num_ventas'         => $meses->sum('num_ventas'),
                'total_ventas'       => $meses->sum('total_ventas'),
                'diferencia'         => $meses->sum('diferencia'),
            ];
        }

        $sucursalesDisponibles = $this->sucursalesDisponibles;

        return view('livewire.reporte.reporte-corte-mensual', compact(
            'empresas', 'meses', 'totales', 'esSuperAdmin', 'esAdmin', 'sucursalesDisponibles'
        ));
    }
}
