<?php

namespace App\Livewire\Facturacion;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class NotasCreditoDebito extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public string $filtroDesde   = '';
    public string $filtroHasta   = '';
    public string $filtroCliente = '';
    public string $filtroTipo    = '';   // '' todas | 07 crédito | 08 débito
    public int    $porPagina     = 20;
    public bool   $buscado       = false;

    public function mount(): void
    {
        abort_if(!auth()->user()->can('notas_credito_debito.listar'), 403);
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
        $tipos = $this->filtroTipo !== '' ? [$this->filtroTipo] : ['07', '08'];

        return DB::table('ventas as v')
            ->leftJoin('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', $tipos)
            ->whereDate('v.venta_fecha', '>=', $this->filtroDesde)
            ->whereDate('v.venta_fecha', '<=', $this->filtroHasta)
            ->when($this->filtroCliente !== '', fn($q) => $q->where(fn($w) =>
                $w->where('c.cliente_nombre', 'like', '%' . $this->filtroCliente . '%')
                  ->orWhere('c.cliente_razonsocial', 'like', '%' . $this->filtroCliente . '%')
                  ->orWhere('c.cliente_numero', 'like', '%' . $this->filtroCliente . '%')))
            ->select(
                'v.id_venta', 'v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo', 'v.venta_fecha',
                'v.venta_total', 'v.id_formas_pago', 'v.venta_estado_sunat', 'v.anulado_sunat',
                'v.tipo_documento_modificar', 'v.serie_modificar', 'v.correlativo_modificar',
                'v.venta_codigo_motivo_nota',
                'c.cliente_nombre', 'c.cliente_razonsocial', 'c.cliente_numero', 'c.id_tipo_documento'
            )
            ->orderByDesc('v.id_venta');
    }

    public function render()
    {
        $ventas = $this->buscado
            ? $this->baseQuery()->paginate($this->porPagina)
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->porPagina);

        $motivosNC = DB::table('tipo_ncreditos')->pluck('tipo_nota_descripcion', 'codigo')->toArray();
        $motivosND = DB::table('tipo_ndebitos')->pluck('tipo_nota_descripcion', 'codigo')->toArray();

        return view('livewire.facturacion.notas-credito-debito', compact('ventas', 'motivosNC', 'motivosND'));
    }
}
