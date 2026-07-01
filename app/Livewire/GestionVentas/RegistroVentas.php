<?php

namespace App\Livewire\GestionVentas;

use App\Models\Logs;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class RegistroVentas extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    // ── Filtros ───────────────────────────────────────────────
    public string $filtroDesde    = '';
    public string $filtroHasta    = '';
    public string $filtroSerie    = '';
    public string $filtroNumero   = '';
    public string $filtroCliente  = '';
    public int    $filtroVendedor = 0;
    public int    $porPagina      = 20;

    // ── Tipos de pago (para rectificar) ───────────────────────
    public array $tiposPago = [];

    // ── Rectificar comprobante ────────────────────────────────
    public int   $rectVentaId    = 0;
    public int   $rectVendedor   = 0;
    public int   $rectCobrador   = 0;
    public int   $rectFormasPago = 1;
    public array $rectMedios            = [];
    public array $rectUsuariosVendedor  = [];
    public array $rectUsuariosCobrador  = [];
    public ?int  $rectIdPedido          = null;
    public ?int  $rectIdProfo           = null;
    public float $rectTotalVenta        = 0;

    private $logs;

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('registro_ventas.listar'), 403);
        $this->filtroDesde = now()->startOfMonth()->format('Y-m-d');
        $this->filtroHasta = now()->format('Y-m-d');
        $this->tiposPago   = DB::table('tipo_pago')->where('tipo_pago_estado', 1)->orderBy('id_tipo_pago')->get()->toArray();
    }

    public function updatedFiltroDesde(): void    { $this->resetPage(); }
    public function updatedFiltroHasta(): void    { $this->resetPage(); }
    public function updatedFiltroSerie(): void    { $this->resetPage(); }
    public function updatedFiltroNumero(): void   { $this->resetPage(); }
    public function updatedFiltroCliente(): void  { $this->resetPage(); }
    public function updatedFiltroVendedor(): void { $this->resetPage(); }
    public function updatingPorPagina(): void     { $this->resetPage(); }

    private function baseQuery()
    {
        return DB::table('ventas as v')
            ->leftJoin('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->leftJoin('users as u', 'u.id_users', '=', 'v.id_users')
            ->leftJoin('monedas as mo', 'mo.id_moneda', '=', 'v.id_moneda')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->whereDate('v.venta_fecha', '>=', $this->filtroDesde)
            ->whereDate('v.venta_fecha', '<=', $this->filtroHasta)
            ->when($this->filtroSerie !== '', fn($q) => $q->where('v.venta_serie', 'like', '%' . $this->filtroSerie . '%'))
            ->when($this->filtroNumero !== '', fn($q) => $q->where('v.venta_correlativo', 'like', '%' . $this->filtroNumero . '%'))
            ->when($this->filtroCliente !== '', fn($q) => $q->where(fn($w) =>
                $w->where('c.cliente_nombre', 'like', '%' . $this->filtroCliente . '%')
                  ->orWhere('c.cliente_razonsocial', 'like', '%' . $this->filtroCliente . '%')
                  ->orWhere('c.cliente_numero', 'like', '%' . $this->filtroCliente . '%')))
            ->when($this->filtroVendedor > 0, fn($q) => $q->where('v.id_users', $this->filtroVendedor))
            ->select(
                'v.id_venta', 'v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo', 'v.venta_fecha',
                'v.venta_totalgravada', 'v.venta_totalexonerada', 'v.venta_totalinafecta',
                'v.venta_totaldescuento', 'v.venta_total', 'v.id_formas_pago',
                'c.cliente_nombre', 'c.cliente_razonsocial', 'c.cliente_numero', 'c.id_tipo_documento',
                'u.nombre_users',
                'mo.abreviado as moneda_abrev', 'mo.simbolo as moneda_simbolo'
            )
            ->orderByDesc('v.id_venta');
    }

    // ── Imprimir (mismo flujo que Caja: ticketera) ────────────
    public function reimprimir(int $idVenta): void
    {
        $this->dispatch('abrirComprobanteCaja', idVenta: $idVenta);
    }

    // ── Rectificar (editar) ───────────────────────────────────
    public function abrirRectificar(int $idVenta): void
    {
        $venta = DB::table('ventas')->where('id_venta', $idVenta)->first();
        if (!$venta) return;

        $this->rectVentaId    = $idVenta;
        $this->rectTotalVenta = (float) $venta->venta_total;
        $this->rectCobrador   = (int) $venta->id_users;
        $this->rectFormasPago = (int) $venta->id_formas_pago;
        $this->rectIdPedido   = $venta->id_pedido ? (int) $venta->id_pedido : null;
        $this->rectIdProfo    = $venta->id_profo  ? (int) $venta->id_profo  : null;

        if ($this->rectIdPedido) {
            $this->rectVendedor = (int) (DB::table('pedidos')->where('id_pedido', $this->rectIdPedido)->value('id_users') ?? $venta->id_users);
        } elseif ($this->rectIdProfo) {
            $this->rectVendedor = (int) (DB::table('proformas')->where('id_profo', $this->rectIdProfo)->value('id_users') ?? $venta->id_users);
        } else {
            $this->rectVendedor = (int) $venta->id_users;
        }

        $pagosActuales = DB::table('ventas_detalle_pagos')->where('id_venta', $idVenta)->get();

        $medios = [];
        $tarjetaAgregada = false;
        foreach ($this->tiposPago as $tp) {
            $esTarjeta = str_contains(strtoupper((string)($tp->tipo_pago_nombre ?? '')), 'TARJETA');
            if ($esTarjeta && !$tarjetaAgregada) {
                $tarjetaAgregada = true;
                foreach (['Visa', 'Mastercard', 'American Express', 'UnionPay'] as $m) {
                    $pago = $pagosActuales->first(fn($p) => $p->id_tipo_pago == $tp->id_tipo_pago && ($p->marca_tarjeta ?? '') === $m);
                    $medios[] = [
                        'id_tipo_pago' => (int) $tp->id_tipo_pago, 'marca' => $m,
                        'label' => 'Tarjeta - ' . $m,
                        'monto' => $pago ? number_format((float)$pago->venta_detalle_pago_monto, 2, '.', '') : '0.00',
                    ];
                }
            } elseif (!$esTarjeta) {
                $pago = $pagosActuales->first(fn($p) => $p->id_tipo_pago == $tp->id_tipo_pago && empty($p->marca_tarjeta));
                $medios[] = [
                    'id_tipo_pago' => (int) $tp->id_tipo_pago, 'marca' => '',
                    'label' => (string)($tp->tipo_pago_nombre ?? ''),
                    'monto' => $pago ? number_format((float)$pago->venta_detalle_pago_monto, 2, '.', '') : '0.00',
                ];
            }
        }
        $this->rectMedios = $medios;
        $this->cargarUsuariosRectificar();
        $this->dispatch('abrirModalRectificar');
    }

    public function guardarRectificar(): void
    {
        if (!$this->rectVentaId) return;
        if (!auth()->user()->can('registro_ventas.actualizar')) {
            $this->dispatch('rectificar-error', mensaje: 'No tienes permiso para rectificar comprobantes.');
            return;
        }

        if ($this->rectFormasPago == 1) {
            $totalVenta = (float) DB::table('ventas')->where('id_venta', $this->rectVentaId)->value('venta_total');
            $sumaMedios = collect($this->rectMedios)->sum(fn($m) => (float) str_replace(',', '.', $m['monto'] ?? '0'));
            if (round($sumaMedios, 2) !== round($totalVenta, 2)) {
                $this->dispatch('rectificar-error',
                    mensaje: 'Los medios de pago (S/ ' . number_format($sumaMedios, 2) . ') no coinciden con el total del comprobante (S/ ' . number_format($totalVenta, 2) . ').');
                return;
            }
        }

        DB::beginTransaction();
        try {
            DB::table('ventas')->where('id_venta', $this->rectVentaId)->update([
                'id_users'       => $this->rectCobrador,
                'id_formas_pago' => $this->rectFormasPago,
                'updated_at'     => now(),
            ]);

            if ($this->rectIdPedido) {
                DB::table('pedidos')->where('id_pedido', $this->rectIdPedido)->update(['id_users' => $this->rectVendedor, 'updated_at' => now()]);
            } elseif ($this->rectIdProfo) {
                DB::table('proformas')->where('id_profo', $this->rectIdProfo)->update(['id_users' => $this->rectVendedor, 'updated_at' => now()]);
            }

            if ($this->rectFormasPago == 1) {
                DB::table('ventas_detalle_pagos')->where('id_venta', $this->rectVentaId)->delete();
                foreach ($this->rectMedios as $medio) {
                    $monto = (float) str_replace(',', '.', $medio['monto'] ?? '0');
                    if ($monto <= 0) continue;
                    DB::table('ventas_detalle_pagos')->insert([
                        'id_venta'                  => $this->rectVentaId,
                        'id_tipo_pago'              => $medio['id_tipo_pago'],
                        'marca_tarjeta'             => $medio['marca'] ?: null,
                        'venta_detalle_pago_monto'  => $monto,
                        'venta_detalle_pago_estado' => 1,
                        'created_at'                => now(),
                        'updated_at'                => now(),
                    ]);
                }
            }

            DB::commit();
            $this->dispatch('cerrarModalRectificar');
            session()->flash('success', 'Comprobante rectificado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            $this->dispatch('rectificar-error', mensaje: 'Error al rectificar el comprobante.');
        }
    }

    private function cargarUsuariosRectificar(): void
    {
        $idsVendedor = DB::table('model_has_roles')->whereIn('role_id', [1, 2, 5])->pluck('model_id')->unique()->values()->toArray();
        if ($this->rectVendedor > 0 && !in_array($this->rectVendedor, $idsVendedor)) $idsVendedor[] = $this->rectVendedor;
        $this->rectUsuariosVendedor = DB::table('users as u')->where('u.users_estado', 1)
            ->whereIn('u.id_users', $idsVendedor)->select('u.id_users', 'u.nombre_users')->orderBy('u.nombre_users')->get()->toArray();

        $idsCobrador = DB::table('model_has_roles')->whereIn('role_id', [1, 2, 4])->pluck('model_id')->unique()->values()->toArray();
        if ($this->rectCobrador > 0 && !in_array($this->rectCobrador, $idsCobrador)) $idsCobrador[] = $this->rectCobrador;
        $this->rectUsuariosCobrador = DB::table('users as u')->where('u.users_estado', 1)
            ->whereIn('u.id_users', $idsCobrador)->select('u.id_users', 'u.nombre_users')->orderBy('u.nombre_users')->get()->toArray();
    }

    public function render()
    {
        $ventas = $this->baseQuery()->paginate($this->porPagina);

        // Tipos de pago por venta (lista) para las ventas de la página actual
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

        $vendedores = DB::table('users')->where('users_estado', 1)
            ->orderBy('nombre_users')->get(['id_users', 'nombre_users']);

        return view('livewire.gestion-ventas.registro-ventas', compact('ventas', 'pagosPorVenta', 'vendedores'));
    }
}
