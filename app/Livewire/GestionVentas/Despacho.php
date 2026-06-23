<?php

namespace App\Livewire\GestionVentas;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Despacho extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // ── Contexto ──────────────────────────────────────────────
    public int  $idTienda  = 0;
    public int  $idEmpresa = 0;
    private int $cachedRoleId = 0;

    // ── Filtros ───────────────────────────────────────────────
    public string $buscar      = '';
    public string $filtroDesde = '';
    public string $filtroHasta = '';
    public int    $porPagina   = 10;

    // ── Modal despacho ────────────────────────────────────────
    public ?int $idPedidoDespachar = null;

    // ── Modal detalle ─────────────────────────────────────────
    public ?int   $idDetalleVenta   = null;
    public array  $detalleItems     = [];
    public string $detalleNumero    = '';
    public string $detallePedido    = '';
    public float  $detalleTotal     = 0;

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

    public function mount(): void
    {
        abort_if(!auth()->user()->can('despacho.listar'), 403);

        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');

        // Resolver tienda/empresa del usuario logueado
        $tienda = DB::table('user_tienda as ut')
            ->join('tiendas as t', 't.id_tienda', '=', 'ut.id_tienda')
            ->where('ut.id_users', auth()->user()->id_users)
            ->select('t.id_tienda', 't.id_empresa')
            ->first();

        if ($tienda) {
            $this->idTienda  = (int) $tienda->id_tienda;
            $this->idEmpresa = (int) $tienda->id_empresa;
        }
    }

    // ── Lifecycle hooks ───────────────────────────────────────

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroHasta(): void
    {
        $this->resetPage();
    }

    // ── Ver detalle ───────────────────────────────────────────

    public function verDetalle(int $idVenta): void
    {
        $venta = DB::table('ventas as v')
            ->join('pedidos as p', 'p.id_pedido', '=', 'v.id_pedido')
            ->where('v.id_venta', $idVenta)
            ->select('v.venta_serie', 'v.venta_correlativo', 'v.venta_total', 'p.pedido_numero')
            ->first();

        if (!$venta) return;

        $this->idDetalleVenta = $idVenta;
        $this->detalleNumero  = $venta->venta_serie . '-' . $venta->venta_correlativo;
        $this->detallePedido  = $venta->pedido_numero;
        $this->detalleTotal   = (float) $venta->venta_total;

        $this->detalleItems = DB::table('ventas_detalle as vd')
            ->join('productos as p', 'p.id_pro', '=', 'vd.id_pro')
            ->where('vd.id_venta', $idVenta)
            ->select(
                'p.pro_nombre',
                'p.pro_codigo',
                'vd.venta_detalle_cantidad        as cantidad',
                'vd.venta_detalle_precio_unitario as precio_unitario',
                'vd.venta_detalle_importe_total   as importe_total'
            )
            ->get()
            ->toArray();

        $this->dispatch('abrirModalDetalleDespacho');
    }

    // ── Despacho ──────────────────────────────────────────────

    public function confirmarDespacho(int $idPedido): void
    {
        $this->idPedidoDespachar = $idPedido;
        $this->dispatch('abrirModalDespacho');
    }

    public function despachar(): void
    {
        if (!auth()->user()->can('despacho.crear')) {
            $this->dispatch('cerrarModalDespacho');
            session()->flash('error', 'No tienes permiso para despachar pedidos.');
            return;
        }

        if (!$this->idPedidoDespachar) {
            $this->dispatch('cerrarModalDespacho');
            return;
        }

        DB::beginTransaction();
        try {
            $idPedido = $this->idPedidoDespachar;

            // Verificar que el pedido existe y está en estado 1 (en caja)
            $pedido = DB::table('pedidos')
                ->where('id_pedido', $idPedido)
                ->where('pedido_estado', 1)
                ->first();

            if (!$pedido) {
                DB::rollBack();
                $this->dispatch('cerrarModalDespacho');
                session()->flash('error', 'El pedido no se encuentra disponible para despacho.');
                return;
            }

            // Obtener detalles del pedido
            $detalles = DB::table('pedidos_detalle')
                ->where('id_pedido', $idPedido)
                ->where('pedido_deta_estado', 1)
                ->get();

            foreach ($detalles as $detalle) {
                $idPro    = (int)   $detalle->id_pro;
                $cantidad = (float) $detalle->pedido_deta_cantidad;

                // Verificar y descontar stock
                $ps = DB::table('producto_sucursal')
                    ->where('id_pro',    $idPro)
                    ->where('id_tienda', $this->idTienda)
                    ->first();

                if ($ps && (float) $ps->ps_stock >= $cantidad) {
                    DB::table('producto_sucursal')
                        ->where('id_pro',    $idPro)
                        ->where('id_tienda', $this->idTienda)
                        ->decrement('ps_stock', $cantidad);
                }
            }

            // Actualizar estado del pedido a despachado
            DB::table('pedidos')
                ->where('id_pedido', $idPedido)
                ->update(['pedido_estado' => 2, 'updated_at' => now()]);

            DB::commit();

            $this->idPedidoDespachar = null;
            $this->dispatch('cerrarModalDespacho');
            session()->flash('success', 'Pedido despachado correctamente. Stock actualizado.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            $this->dispatch('cerrarModalDespacho');
            session()->flash('error', 'Ocurrió un error al despachar el pedido.');
        }
    }

    // ── Render ────────────────────────────────────────────────

    public function render(): \Illuminate\View\View
    {
        $query = DB::table('ventas as v')
            ->join('pedidos as p', 'p.id_pedido', '=', 'v.id_pedido')
            ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->leftJoin('tiendas as t', 't.id_tienda', '=', 'v.id_sucursal')
            ->where('p.pedido_estado', 1)
            ->select(
                'v.id_venta',
                'v.venta_serie',
                'v.venta_correlativo',
                'v.venta_total',
                'v.venta_fecha',
                'p.id_pedido',
                'p.pedido_numero',
                'c.cliente_nombre',
                'c.cliente_razonsocial',
                'c.id_tipo_documento',
                't.tienda_nombre'
            );

        // Filtro por tienda del usuario (si no es superadmin)
        if (!$this->esSuperAdmin()) {
            if ($this->idTienda) {
                $query->where('v.id_sucursal', $this->idTienda);
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        if ($this->filtroDesde) {
            $query->whereDate('v.venta_fecha', '>=', $this->filtroDesde);
        }
        if ($this->filtroHasta) {
            $query->whereDate('v.venta_fecha', '<=', $this->filtroHasta);
        }

        if (trim($this->buscar) !== '') {
            $termino = trim($this->buscar);
            $query->where(function ($q) use ($termino) {
                $q->where('p.pedido_numero',      'like', '%' . $termino . '%')
                  ->orWhere('c.cliente_nombre',   'like', '%' . $termino . '%')
                  ->orWhere('c.cliente_razonsocial', 'like', '%' . $termino . '%')
                  ->orWhere('v.venta_serie',       'like', '%' . $termino . '%')
                  ->orWhere('v.venta_correlativo', 'like', '%' . $termino . '%')
                  ->orWhereRaw("CONCAT(v.venta_serie, '-', v.venta_correlativo) like ?", ['%' . $termino . '%'])
                  ->orWhere('v.venta_total',       'like', '%' . $termino . '%');
            });
        }

        $ventas = $query->orderBy('v.id_venta', 'desc')->paginate($this->porPagina);

        return view('livewire.gestion-ventas.despacho', [
            'ventas' => $ventas,
        ]);
    }
}
