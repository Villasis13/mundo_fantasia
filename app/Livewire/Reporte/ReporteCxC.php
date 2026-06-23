<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReporteCxC extends Component
{
    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    public string $filtroCliente   = '';
    public string $filtroDesde    = '';
    public string $filtroHasta    = '';
    public string $filtroVinculada = '';   // '' | '1' | '0'
    public bool   $buscado        = false;

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
        abort_if(!auth()->user()->can('reporte_cxc.listar'), 403);
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta  = now()->format('Y-m-d');

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
        $this->sucursalSeleccionada = 0; $this->buscado = false;
        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')
                ->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')->get()
            : collect();
    }

    public function updatedSucursalSeleccionada(): void  { $this->buscado = false; }
    public function updatedFiltroCliente(): void         { $this->buscado = false; }
    public function updatedFiltroDesde(): void           { $this->buscado = false; }
    public function updatedFiltroHasta(): void           { $this->buscado = false; }
    public function updatedFiltroVinculada(): void       { $this->buscado = false; }

    public function generar(): void { $this->buscado = true; }

    public function exportarPdf(): void
    {
        if (!auth()->user()->can('reporte_cxc.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.'); return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.reporte_cxc_pdf', $this->buildParams()));
    }

    public function exportarExcel(): void
    {
        if (!auth()->user()->can('reporte_cxc.exportar')) {
            session()->flash('error', 'No tienes permiso para exportar.'); return;
        }
        $this->dispatch('abrirEnlaces', url: route('reporte.reporte_cxc_excel', $this->buildParams()));
    }

    private function buildParams(): array
    {
        return [
            'empresa'    => $this->empresaSeleccionada,
            'sucursal'   => $this->sucursalSeleccionada,
            'cliente'    => $this->filtroCliente,
            'desde'      => $this->filtroDesde,
            'hasta'      => $this->filtroHasta,
            'vinculada'  => $this->filtroVinculada,
        ];
    }

    public function calcularReporte(): array
    {
        $idEmpresa  = $this->resolverIdEmpresa();
        $idSucursal = $this->resolverIdSucursal();
        $hoy        = now()->toDateString();

        $subPagado = DB::table('pagos_cuotas as pc2')
            ->selectRaw('pc2.id_ventas_cuotas, SUM(pc2.pagos_cuota_monto) as total_pagado')
            ->whereNull('pc2.deleted_at')->groupBy('pc2.id_ventas_cuotas');

        $query = DB::table('ventas_cuotas as vc')
            ->select(
                'c.id_clientes', 'c.cliente_nombre', 'c.cliente_razonsocial', 'c.cliente_numero',
                'vc.id_ventas_cuotas', 'vc.venta_cuota_importe', 'vc.venta_cuota_fecha',
                'v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo',
                DB::raw('COALESCE(pag.total_pagado, 0) as total_pagado'),
                DB::raw('GREATEST(vc.venta_cuota_importe - COALESCE(pag.total_pagado, 0), 0) as saldo'),
                DB::raw("CASE WHEN ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo THEN 1 ELSE 0 END as es_vinculada")
            )
            ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
            ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->leftJoinSub($subPagado, 'pag', 'pag.id_ventas_cuotas', '=', 'vc.id_ventas_cuotas')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->leftJoin('empresa as ev', 'ev.empresa_ruc', '=', 'c.cliente_numero')
            ->leftJoin('empresa as eo', 'eo.id_empresa', '=', 'v.id_empresa')
            ->whereNull('va.id_venta')
            ->where('v.id_formas_pago', 2)
            ->whereRaw('COALESCE(pag.total_pagado, 0) < vc.venta_cuota_importe');

        if ($idSucursal > 0)   $query->where('v.id_sucursal', $idSucursal);
        elseif ($idEmpresa)    $query->where('v.id_empresa', $idEmpresa);
        if ($this->filtroDesde) $query->whereDate('vc.venta_cuota_fecha', '>=', $this->filtroDesde);
        if ($this->filtroHasta) $query->whereDate('vc.venta_cuota_fecha', '<=', $this->filtroHasta);
        if ($this->filtroCliente !== '') {
            $like = '%'.$this->filtroCliente.'%';
            $query->where(function ($q) use ($like) {
                $q->where('c.cliente_nombre', 'like', $like)
                  ->orWhere('c.cliente_razonsocial', 'like', $like)
                  ->orWhere('c.cliente_numero', 'like', $like);
            });
        }

        if ($this->filtroVinculada === '1') {
            $query->whereRaw('ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo');
        } elseif ($this->filtroVinculada === '0') {
            $query->whereRaw('NOT (ev.id_empresa IS NOT NULL AND eo.id_grupo IS NOT NULL AND ev.id_grupo = eo.id_grupo)');
        }

        $cuotas = $query->get();

        // Agrupar por cliente con aging
        $clientes = [];
        foreach ($cuotas as $c) {
            $id   = $c->id_clientes;
            $dias = (int) now()->diffInDays($c->venta_cuota_fecha, false) * -1;
            $dias = max(0, $dias);
            $saldo = (float) $c->saldo;

            if (!isset($clientes[$id])) {
                $clientes[$id] = [
                    'nombre'       => $c->cliente_nombre ?: $c->cliente_razonsocial,
                    'numero'       => $c->cliente_numero,
                    'es_vinculada' => (bool) $c->es_vinculada,
                    'total'        => 0, 'corriente' => 0,
                    'dias_1_30'    => 0, 'dias_31_60' => 0,
                    'dias_61_90'   => 0, 'dias_mas_90' => 0,
                    'cuotas'       => 0,
                ];
            }
            $clientes[$id]['total']  += $saldo;
            $clientes[$id]['cuotas'] += 1;
            if ($c->venta_cuota_fecha >= $hoy)     $clientes[$id]['corriente']   += $saldo;
            elseif ($dias <= 30)                    $clientes[$id]['dias_1_30']   += $saldo;
            elseif ($dias <= 60)                    $clientes[$id]['dias_31_60']  += $saldo;
            elseif ($dias <= 90)                    $clientes[$id]['dias_61_90']  += $saldo;
            else                                    $clientes[$id]['dias_mas_90'] += $saldo;
        }

        usort($clientes, fn($a, $b) => $b['total'] <=> $a['total']);

        $totales = [
            'total'       => array_sum(array_column($clientes, 'total')),
            'corriente'   => array_sum(array_column($clientes, 'corriente')),
            'dias_1_30'   => array_sum(array_column($clientes, 'dias_1_30')),
            'dias_31_60'  => array_sum(array_column($clientes, 'dias_31_60')),
            'dias_61_90'  => array_sum(array_column($clientes, 'dias_61_90')),
            'dias_mas_90' => array_sum(array_column($clientes, 'dias_mas_90')),
        ];

        return compact('clientes', 'totales');
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();
        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $idEmpresaActiva = $this->resolverIdEmpresa();
        $reporte = $this->buscado ? $this->calcularReporte() : null;

        return view('livewire.reporte.reporte-cxc', compact(
            'esSuperAdmin', 'esAdmin', 'empresas', 'idEmpresaActiva', 'reporte'
        ));
    }
}
