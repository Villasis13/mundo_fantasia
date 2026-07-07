<?php

namespace App\Livewire\Facturacion;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class EnvioRecepcionComprobantes extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public string $filtroDesde   = '';
    public string $filtroHasta   = '';
    public string $filtroCliente = '';
    public int    $porPagina     = 20;
    public bool   $buscado       = false;

    public function mount(): void
    {
        abort_if(!auth()->user()->can('envio_recepcion_comprobantes.listar'), 403);
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');
    }

    public function buscar(): void
    {
        $this->buscado = true;
        $this->resetPage();
    }

    public function updatingPorPagina(): void { $this->resetPage(); }

    private function baseQuery()
    {
        return DB::table('ventas as v')
            ->leftJoin('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereDate('v.venta_fecha', '>=', $this->filtroDesde)
            ->whereDate('v.venta_fecha', '<=', $this->filtroHasta)
            ->when($this->filtroCliente !== '', fn($q) => $q->where(fn($w) =>
                $w->where('c.cliente_nombre', 'like', '%' . $this->filtroCliente . '%')
                  ->orWhere('c.cliente_razonsocial', 'like', '%' . $this->filtroCliente . '%')
                  ->orWhere('c.cliente_numero', 'like', '%' . $this->filtroCliente . '%')))
            ->select(
                'v.id_venta', 'v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo', 'v.venta_fecha',
                'v.venta_total', 'v.id_formas_pago',
                'v.venta_fecha_envio', 'v.venta_rutaXML', 'v.venta_rutaCDR',
                'v.venta_respuesta_sunat', 'v.venta_estado_sunat',
                'c.cliente_nombre', 'c.cliente_razonsocial', 'c.cliente_numero', 'c.id_tipo_documento'
            )
            ->orderByDesc('v.id_venta');
    }

    public function render()
    {
        $ventas = $this->buscado
            ? $this->baseQuery()->paginate($this->porPagina)
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->porPagina);

        $ids = collect($ventas->items())->pluck('id_venta')->all();
        $pagosPorVenta = [];
        if (!empty($ids)) {
            $pagos = DB::table('ventas_detalle_pagos as vdp')
                ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
                ->whereIn('vdp.id_venta', $ids)
                ->where('vdp.venta_detalle_pago_estado', 1)
                ->select('vdp.id_venta', 'tp.tipo_pago_nombre')
                ->get();
            foreach ($pagos as $p) {
                $pagosPorVenta[$p->id_venta][] = $p->tipo_pago_nombre;
            }
        }

        return view('livewire.facturacion.envio-recepcion-comprobantes', compact('ventas', 'pagosPorVenta'));
    }
}
