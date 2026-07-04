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
    public int    $filtroPuntoVenta = 0;
    public string $filtroEstado   = '';
    public int    $porPagina      = 20;
    public bool   $filtrado       = false;

    // ── Tipos de pago (para rectificar) ───────────────────────
    public array $tiposPago = [];

    // ── Ver detalle ───────────────────────────────────────────
    public ?array $detalle       = null;
    public array  $detalleItems  = [];

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

    public function updatedFiltroDesde(): void    { $this->filtrado = true; $this->resetPage(); }
    public function updatedFiltroHasta(): void    { $this->filtrado = true; $this->resetPage(); }
    public function updatedFiltroSerie(): void    { $this->filtrado = true; $this->resetPage(); }
    public function updatedFiltroNumero(): void   { $this->filtrado = true; $this->resetPage(); }
    public function updatedFiltroCliente(): void    { $this->filtrado = true; $this->resetPage(); }
    public function updatedFiltroPuntoVenta(): void { $this->filtrado = true; $this->resetPage(); }
    public function updatedFiltroEstado(): void     { $this->filtrado = true; $this->resetPage(); }
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
            ->when($this->filtroPuntoVenta > 0, fn($q) => $q->where('v.id_users', $this->filtroPuntoVenta))
            ->when($this->filtroEstado !== '', function ($q) {
                $ncSql = "(v.anulado_sunat = 1 OR EXISTS (
                    SELECT 1 FROM ventas nc
                    WHERE nc.venta_tipo = '07'
                      AND nc.serie_modificar = v.venta_serie
                      AND nc.correlativo_modificar = v.venta_correlativo
                      AND nc.id_empresa = v.id_empresa))";
                if ($this->filtroEstado === 'anulado') {
                    $q->whereRaw($ncSql);
                } elseif ($this->filtroEstado === 'enviado') {
                    $q->whereRaw("NOT $ncSql")->where('v.venta_estado_sunat', 1);
                } elseif ($this->filtroEstado === 'pendiente') {
                    $q->whereRaw("NOT $ncSql")->where('v.venta_estado_sunat', 0);
                }
            })
            ->select(
                'v.id_venta', 'v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo', 'v.venta_fecha',
                'v.venta_totalgravada', 'v.venta_totalexonerada', 'v.venta_totalinafecta',
                'v.venta_totaldescuento', 'v.venta_total', 'v.id_formas_pago', 'v.venta_estado_sunat',
                'v.anulado_sunat',
                'c.cliente_nombre', 'c.cliente_razonsocial', 'c.cliente_numero', 'c.id_tipo_documento',
                'u.nombre_users',
                'mo.abreviado as moneda_abrev', 'mo.simbolo as moneda_simbolo',
                DB::raw("(CASE WHEN v.anulado_sunat = 1 OR EXISTS (
                    SELECT 1 FROM ventas nc
                    WHERE nc.venta_tipo = '07'
                      AND nc.serie_modificar = v.venta_serie
                      AND nc.correlativo_modificar = v.venta_correlativo
                      AND nc.id_empresa = v.id_empresa
                ) THEN 1 ELSE 0 END) as tiene_nc")
            )
            ->orderByDesc('v.id_venta');
    }

    // ── Imprimir (mismo flujo que Caja: ticketera) ────────────
    public function reimprimir(int $idVenta): void
    {
        $this->dispatch('abrirComprobanteCaja', idVenta: $idVenta);
    }

    // ── Ver detalle del comprobante ───────────────────────────
    public function verDetalle(int $idVenta): void
    {
        $v = DB::table('ventas as v')
            ->leftJoin('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->leftJoin('tipo_documento as td', 'td.id_tipo_documento', '=', 'c.id_tipo_documento')
            ->leftJoin('empresa as e', 'e.id_empresa', '=', 'v.id_empresa')
            ->leftJoin('users as u', 'u.id_users', '=', 'v.id_users')
            ->where('v.id_venta', $idVenta)
            ->select(
                'v.id_venta', 'v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo', 'v.venta_fecha',
                'v.venta_totalgravada', 'v.venta_totalexonerada', 'v.venta_totalinafecta',
                'v.venta_totaligv', 'v.venta_totaldescuento', 'v.venta_total', 'v.id_formas_pago',
                'v.venta_estado_sunat', 'v.anulado_sunat',
                'e.empresa_ruc', 'e.empresa_razon_social', 'e.empresa_domiciliofiscal',
                'c.cliente_nombre', 'c.cliente_razonsocial', 'c.cliente_numero',
                'td.tipo_documento_identidad',
                'u.nombre_users'
            )->first();

        if (!$v) return;

        $tipoLbl = ['01'=>'Factura','03'=>'Boleta','20'=>'Nota de Venta','07'=>'Nota de Crédito','08'=>'Nota de Débito'][$v->venta_tipo] ?? $v->venta_tipo;
        $esEmpresa   = !empty($v->cliente_razonsocial);
        $compradorNom = $esEmpresa ? $v->cliente_razonsocial : ($v->cliente_nombre ?: $v->cliente_razonsocial);

        $this->detalle = [
            'tipo'            => $tipoLbl,
            'numero'          => $v->venta_serie . ' - ' . str_pad((string)$v->venta_correlativo, 8, '0', STR_PAD_LEFT),
            'fecha'           => \Carbon\Carbon::parse($v->venta_fecha)->format('d/m/Y'),
            'hora'            => \Carbon\Carbon::parse($v->venta_fecha)->format('H:i:s'),
            'emisor_ruc'      => $v->empresa_ruc,
            'emisor_razon'    => $v->empresa_razon_social,
            'emisor_dom'      => $v->empresa_domiciliofiscal,
            'comp_tipo_doc'   => $v->tipo_documento_identidad ?: '—',
            'comp_num_doc'    => $v->cliente_numero,
            'comp_razon'      => $compradorNom,
            'condicion'       => $v->id_formas_pago == 2 ? 'Crédito' : 'Contado',
            'vendedor'        => $v->nombre_users ?? '—',
            'gravada'         => (float) $v->venta_totalgravada,
            'exonerada'       => (float) $v->venta_totalexonerada,
            'inafecta'        => (float) $v->venta_totalinafecta,
            'igv'             => (float) $v->venta_totaligv,
            'descuento'       => (float) $v->venta_totaldescuento,
            'total'           => (float) $v->venta_total,
        ];

        $items = DB::table('ventas_detalle as vd')
            ->leftJoin('productos as p', 'p.id_pro', '=', 'vd.id_pro')
            ->leftJoin('medida as m', 'm.id_medida', '=', 'p.id_medida')
            ->where('vd.id_venta', $idVenta)
            ->select(
                'vd.venta_detalle_cantidad as cantidad',
                'vd.venta_detalle_nombre_producto as descripcion',
                'vd.venta_detalle_valor_unitario as valor_unitario',
                'vd.venta_detalle_precio_unitario as precio_unitario',
                'p.pro_codigo as codigo',
                'm.medida_nombre as um'
            )->get();

        $this->detalleItems = $items->map(fn($i) => [
            'cantidad'  => (float) $i->cantidad,
            'um'        => $i->um ?: 'UNIDAD',
            'codigo'    => $i->codigo ?: '—',
            'descripcion' => $i->descripcion ?: '—',
            'precio'    => (float) ($i->precio_unitario ?: $i->valor_unitario),
            'importe'   => (float) $i->cantidad * (float) ($i->precio_unitario ?: $i->valor_unitario),
        ])->toArray();

        $this->dispatch('abrirModalDetalle');
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
        // No mostrar registros hasta que se aplique un filtro
        $ventas = $this->filtrado
            ? $this->baseQuery()->paginate($this->porPagina)
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->porPagina);

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

        // Puntos de venta = usuarios cajeros (rol 4) con permiso de cobrar (caja_pedidos.crear)
        $permCobrarId = DB::table('permissions')->where('name', 'caja_pedidos.crear')->value('id');

        $puntosVenta = DB::table('users as u')
            ->join('model_has_roles as mr', function ($j) {
                $j->on('mr.model_id', '=', 'u.id_users')
                  ->where('mr.model_type', 'App\\Models\\User')
                  ->where('mr.role_id', 4);
            })
            ->where('u.users_estado', 1)
            ->when($permCobrarId, function ($q) use ($permCobrarId) {
                $q->where(function ($w) use ($permCobrarId) {
                    // permiso otorgado por alguno de sus roles
                    $w->whereExists(function ($sub) use ($permCobrarId) {
                        $sub->from('model_has_roles as mr2')
                            ->join('role_has_permissions as rhp', 'rhp.role_id', '=', 'mr2.role_id')
                            ->whereColumn('mr2.model_id', 'u.id_users')
                            ->where('mr2.model_type', 'App\\Models\\User')
                            ->where('rhp.permission_id', $permCobrarId);
                    })
                    // o permiso directo al usuario
                    ->orWhereExists(function ($sub) use ($permCobrarId) {
                        $sub->from('model_has_permissions as mhp')
                            ->whereColumn('mhp.model_id', 'u.id_users')
                            ->where('mhp.model_type', 'App\\Models\\User')
                            ->where('mhp.permission_id', $permCobrarId);
                    });
                });
            })
            ->orderBy('u.nombre_users')
            ->select('u.id_users', 'u.nombre_users')
            ->distinct()
            ->get();

        return view('livewire.gestion-ventas.registro-ventas', compact('ventas', 'pagosPorVenta', 'puntosVenta'));
    }
}
