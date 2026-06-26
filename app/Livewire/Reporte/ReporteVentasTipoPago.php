<?php

namespace App\Livewire\Reporte;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReporteVentasTipoPago extends Component
{
    private int $cachedRoleId         = 0;
    public int  $empresaSeleccionada  = 0;
    public int  $sucursalSeleccionada = 0;
    public      $sucursalesDisponibles = [];

    public string $filtroDesde = '';
    public string $filtroHasta = '';
    public bool   $buscado     = false;

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
            ->where('ut.id_users', auth()->user()->id_users)->value('ut.id_tienda');
        return $id ? (int) $id : 0;
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('reporte_ventas_tipo_pago.listar'), 403);
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');

        $empresaId = $this->empresaUsuario();
        if ($empresaId) {
            $this->empresaSeleccionada   = $empresaId;
            $this->sucursalesDisponibles = DB::table('tiendas')
                ->where('id_empresa', $empresaId)->where('tienda_estado', 1)
                ->orderBy('tienda_nombre')->get();
        }
        if (!$this->esSuperAdmin() && !$this->esAdmin()) {
            $idTienda = DB::table('user_tienda as ut')
                ->where('ut.id_users', auth()->user()->id_users)->value('ut.id_tienda');
            if ($idTienda) $this->sucursalSeleccionada = (int) $idTienda;
        }
    }

    public function updatedEmpresaSeleccionada(): void
    {
        $this->sucursalSeleccionada = 0; $this->buscado = false;
        $this->sucursalesDisponibles = $this->empresaSeleccionada > 0
            ? DB::table('tiendas')->where('id_empresa', $this->empresaSeleccionada)
                ->where('tienda_estado', 1)->orderBy('tienda_nombre')->get()
            : collect();
    }

    public function updatedSucursalSeleccionada(): void { $this->buscado = false; }
    public function updatedFiltroDesde(): void           { $this->buscado = false; }
    public function updatedFiltroHasta(): void           { $this->buscado = false; }

    public function generar(): void { $this->buscado = true; }

    private function buildParams(): array
    {
        return [
            'id_empresa'  => $this->resolverIdEmpresa() ?? 0,
            'id_sucursal' => $this->resolverIdSucursal(),
            'desde'       => $this->filtroDesde,
            'hasta'       => $this->filtroHasta,
        ];
    }

    public function imprimirPdf(): void
    {
        if (!auth()->user()->can('reporte_ventas_tipo_pago.exportar')) { session()->flash('error', 'Sin permiso para exportar.'); return; }
        $this->dispatch('abrirEnlaces', url: route('reporte.tipo_pago_pdf', $this->buildParams()));
    }

    public function imprimirExcel(): void
    {
        if (!auth()->user()->can('reporte_ventas_tipo_pago.exportar')) { session()->flash('error', 'Sin permiso para exportar.'); return; }
        $this->dispatch('abrirEnlaces', url: route('reporte.tipo_pago_excel', $this->buildParams()));
    }

    public function render()
    {
        $esSuperAdmin = $this->esSuperAdmin();
        $esAdmin      = $this->esAdmin();

        $empresas = ($esSuperAdmin || $esAdmin)
            ? DB::table('empresa')->where('empresa_estado', '!=', '0')->orderBy('empresa_nombrecomercial')->get()
            : collect();

        $filas       = collect();
        $totalGeneral = 0;
        $totalOper    = 0;

        if ($this->buscado) {
            $idEmpresa  = $this->resolverIdEmpresa();
            $idSucursal = $this->resolverIdSucursal();

            $query = DB::table('ventas_detalle_pagos as vdp')
                ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
                ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
                ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
                ->whereNull('va.id_venta')
                ->where('vdp.venta_detalle_pago_estado', 1)
                ->whereIn('v.venta_tipo', ['01', '03', '20'])
                ->whereDate('v.venta_fecha', '>=', $this->filtroDesde)
                ->whereDate('v.venta_fecha', '<=', $this->filtroHasta);

            if ($idSucursal > 0) {
                $query->where('v.id_sucursal', $idSucursal);
            } elseif ($idEmpresa) {
                $query->where('v.id_empresa', $idEmpresa);
            }

            $filas = $query->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
                ->select(
                    'tp.tipo_pago_nombre',
                    DB::raw('COUNT(DISTINCT v.id_venta) as num_operaciones'),
                    DB::raw('SUM(vdp.venta_detalle_pago_monto) as total')
                )
                ->orderByDesc('total')
                ->get();

            $totalGeneral = (float) $filas->sum('total');
            $totalOper    = (int) $filas->sum('num_operaciones');
        }

        return view('livewire.reporte.reporte-ventas-tipo-pago', compact(
            'esSuperAdmin', 'esAdmin', 'empresas', 'filas', 'totalGeneral', 'totalOper'
        ));
    }
}
