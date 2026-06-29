<?php

namespace App\Livewire\GestionVentas;

use App\Models\Caja as CajaModel;
use App\Models\General;
use App\Models\Logs;
use App\Models\Serie;
use App\Models\Venta_detalle;
use App\Models\Ventas;
use App\Models\Ventas_detalle_pago;
use App\Service\CalcularMontosVenta;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Caja extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // ── Vista ─────────────────────────────────────────────────
    public string $vista = 'cobrar'; // cobrar | despachar

    // ── Despacho pendiente (tras cobrar) ──────────────────────
    public ?int   $idPedidoPendienteDespacho = null;
    public ?int   $idVentaReciente           = null;
    public string $ventaNumeroReciente       = '';
    public array  $itemsDespacho            = [];

    // ── Caja / Tienda ─────────────────────────────────────────
    public bool   $validarCaja  = false;
    public int    $idCaja       = 0;
    public int    $idCajaNumero = 0;
    public int    $idTienda     = 0;
    public int    $idEmpresa    = 0;
    public string $nombreTienda = '';
    public string $nombreCaja   = '';

    // ── Apertura desde modal (caja cerrada) ───────────────────
    public array  $cajasDisponibles  = [];
    public string $idCajaParaAbrir   = '';
    public string $montoAperturaForm = '';
    public bool   $cajaCerradaHoy    = false;

    // ── Cierre de caja ────────────────────────────────────────
    public string $montoCierreForm = '';
    public array  $resumenCierre   = [];
    public array  $ventasResumen   = [];

    // ── Modal selección ───────────────────────────────────────
    public string $buscarModal = '';
    public string $tabModal    = 'pedidos'; // pedidos | proformas | cierre_caja

    // ── Proforma seleccionada ─────────────────────────────────
    public ?int   $idProforma     = null;
    public bool   $esProforma     = false;
    public string $proformaNumero = '';

    // ── Detalle modal ─────────────────────────────────────────
    public ?int   $idDetalle            = null;
    public array  $detalleItems         = [];
    public string $detallePedidoNumero  = '';

    // ── Pedido seleccionado ───────────────────────────────────
    public ?int   $idPedido     = null;
    public string $pedidoNumero = '';
    public array  $items        = [];

    // ── Cliente ───────────────────────────────────────────────
    public string $idTipoDocumento  = '2';
    public string $numDocumento     = '00000000';
    public string $nombreCliente    = 'CLIENTE GENERAL';

    // Datos originales del pedido (para restaurar al volver de Factura → Boleta)
    public string $pedidoClienteNombreOrig = '';
    public string $pedidoClienteDocOrig    = '';

    // ── Comprobante / Serie ───────────────────────────────────
    public string $tipoComprobante = '03';
    public array  $series          = [];
    public        $idSerie         = null;
    public int    $correlativo     = 0;
    public float  $porcentajeIgv   = 0.0;

    // ── Pago ──────────────────────────────────────────────────
    public int   $idFormasPago = 1;
    public array $pagos        = []; // [['id_tipo_pago' => x, 'monto' => ''], ...]
    public array $tiposPago    = [];
    public bool  $esGratuita   = false;

    // ── Rectificar comprobante ────────────────────────────────
    public int    $rectVentaId    = 0;
    public int    $rectVendedor   = 0;
    public int    $rectCobrador   = 0;
    public int    $rectFormasPago = 1;
    public array  $rectMedios            = []; // [['id_tipo_pago'=>x,'marca'=>'','label'=>'','monto'=>'0.00'], ...]
    public array  $rectUsuariosVendedor  = [];
    public array  $rectUsuariosCobrador  = [];
    public ?int   $rectIdPedido          = null;
    public ?int   $rectIdProfo    = null;
    public float  $rectTotalVenta = 0;

    private $logs;

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    public function mount(): void
    {
        abort_if(!auth()->user()->can('caja_pedidos.listar'), 403);

        $this->expirarPedidosVencidos();

        $caja = (new CajaModel())->buscar_apertura_caja();
        if ($caja) {
            $this->validarCaja  = true;
            $this->idCaja       = (int) $caja->id_caja;
            $this->idCajaNumero = (int) $caja->id_caja_numero;

            $cn = DB::table('caja_numero')->where('id_caja_numero', $this->idCajaNumero)->first();
            $this->idTienda   = (int) ($cn->id_tienda ?? 0);
            $this->nombreCaja = $cn->caja_numero_nombre ?? '';

            if ($this->idTienda) {
                $tienda = DB::table('tiendas')->where('id_tienda', $this->idTienda)->first();
                $this->idEmpresa    = (int) ($tienda->id_empresa ?? 1);
                $this->nombreTienda = $tienda->tienda_nombre ?? '';
            }
        }

        $this->tiposPago = DB::table('tipo_pago')
            ->where('tipo_pago_estado', 1)
            ->where('tipo_pago_nombre', 'not like', '%QR%')
            ->where('tipo_pago_nombre', 'not like', '%POS%')
            ->orderBy('id_tipo_pago')
            ->get()
            ->toArray();

        if (!$this->validarCaja) {
            $this->cargarCajasDisponibles();
        }
    }

    // ── Totales (computed) ─────────────────────────────────────

    public function getTotalesProperty(): array
    {
        $calc = (new CalcularMontosVenta())->calcularMontos($this->items, $this->porcentajeIgv, $this->idTienda, $this->esGratuita);
        return [
            'gravada'   => round($calc['gravada'],    2),
            'igv'       => round($calc['igv'],        2),
            'exonerada' => round($calc['exonerada'],  2),
            'inafecta'  => round($calc['inafectada'], 2),
            'gratuita'  => round($calc['gratuito'],   2),
            'impuesto'  => round($calc['impuesto'],   2),
            'total'     => round($calc['total'],      2),
        ];
    }

    public function getVueltoProperty(): float
    {
        $pagado = collect($this->pagos)->sum(fn($p) => (float)($p['monto'] ?? 0));
        return round(max(0, $pagado - $this->totales['total']), 2);
    }

    public function updatedEsGratuita(): void
    {
        if ($this->esGratuita) {
            $this->idFormasPago = 1;
            $this->pagos = [];
        } else {
            $total = $this->totales['total'];
            $this->pagos = [['id_tipo_pago' => $this->idEfectivo(), 'monto' => $total > 0 ? (string) $total : '', 'marca_tarjeta' => '']];
        }
    }

    // ── Ver detalle ───────────────────────────────────────────

    public function verDetalle(int $id): void
    {
        $pedido = DB::table('pedidos')->where('id_pedido', $id)->first();
        if (!$pedido) return;

        $this->idDetalle           = $id;
        $this->detallePedidoNumero = $pedido->pedido_numero;

        $this->detalleItems = DB::table('pedidos_detalle as pd')
            ->join('productos as p', 'p.id_pro', '=', 'pd.id_pro')
            ->where('pd.id_pedido', $id)
            ->where('pd.pedido_deta_estado', 1)
            ->select(
                'p.pro_nombre', 'p.pro_codigo',
                'pd.pedido_deta_cantidad as cantidad',
                'pd.pedido_deta_precio   as precio',
                'pd.pres_nombre'
            )
            ->get()
            ->toArray();

        $this->dispatch('abrirModalDetalleCaja');
    }

    // ── Seleccionar pedido ─────────────────────────────────────

    public function seleccionarPedido(int $idPedido): void
    {
        $pedido = DB::table('pedidos')
            ->where('id_pedido', $idPedido)
            ->where('pedido_estado', 0)
            ->first();

        if (!$pedido) {
            session()->flash('error', 'Pedido no encontrado o ya fue procesado.');
            return;
        }

        // Productos (con stock y configuración de tienda)
        $detallesProducto = DB::table('pedidos_detalle as pd')
            ->join('productos as p', 'p.id_pro', '=', 'pd.id_pro')
            ->join('producto_sucursal as ps', function ($join) {
                $join->on('ps.id_pro', '=', 'pd.id_pro')
                    ->where('ps.id_tienda', $this->idTienda);
            })
            ->leftJoin('tipo_afectacion as ta', 'ta.id_tipo_afectacion', '=', 'ps.id_tipo_afectacion')
            ->where('pd.id_pedido', $idPedido)
            ->where('pd.pedido_deta_estado', 1)
            ->whereNotNull('pd.id_pro')
            ->select(
                'pd.id_pro', 'p.pro_nombre', 'p.pro_codigo',
                'ps.id_tipo_afectacion', 'p.impuesto_bolsa', 'p.id_medida',
                'ps.ps_precio_uni   as pro_precio_uni',
                'ps.ps_precio_uni_2 as pro_precio_uni_2',
                'ps.ps_precio_uni_3 as pro_precio_uni_3',
                'ps.ps_stock        as pro_stock',
                'ta.descripcion     as descripcion_afectacion',
                'pd.pedido_deta_cantidad as cantidad',
                'pd.pedido_deta_precio   as precio_venta',
                'pd.pres_nombre',
                'pd.pres_factor'
            )
            ->get();

        // Servicios (sin producto asociado)
        $detallesServicio = DB::table('pedidos_detalle as pd')
            ->where('pd.id_pedido', $idPedido)
            ->where('pd.pedido_deta_estado', 1)
            ->whereNull('pd.id_pro')
            ->select('pd.pedido_deta_nombre as nombre', 'pd.pedido_deta_cantidad as cantidad', 'pd.pedido_deta_precio as precio_venta')
            ->get();

        if ($detallesProducto->isEmpty() && $detallesServicio->isEmpty()) {
            session()->flash('error', 'El pedido no tiene productos configurados para esta tienda.');
            return;
        }

        $this->idPedido     = $idPedido;
        $this->pedidoNumero = $pedido->pedido_numero;
        $this->items        = [];

        foreach ($detallesProducto as $det) {
            $listaPrecios = array_values(array_filter(
                [(float)$det->pro_precio_uni, (float)$det->pro_precio_uni_2, (float)$det->pro_precio_uni_3],
                fn($p) => $p > 0
            ));
            if (empty($listaPrecios)) $listaPrecios = [(float) $det->precio_venta];

            $this->items[] = [
                'id_pro'                 => (int)    $det->id_pro,
                'pro_nombre'             => (string) $det->pro_nombre,
                'pro_codigo'             => (string) ($det->pro_codigo ?? ''),
                'id_tipo_afectacion'     => (int)    ($det->id_tipo_afectacion ?? 1),
                'impuesto_bolsa'         => (int)    $det->impuesto_bolsa,
                'id_medida'              => (int)    $det->id_medida,
                'pro_stock'              => (float)  $det->pro_stock,
                'descripcion_afectacion' => (string) ($det->descripcion_afectacion ?? ''),
                'lista_precios'          => $listaPrecios,
                'precio_ref'             => (float)  $det->precio_venta,
                'precio_venta'           => (float)  $det->precio_venta,
                'cantidad'               => (float)  $det->cantidad,
                'pres_nombre'            => (string) ($det->pres_nombre ?? ''),
                'pres_factor'            => (float)  ($det->pres_factor ?? 1.0),
            ];
        }

        foreach ($detallesServicio as $det) {
            $precio = (float) $det->precio_venta;
            $this->items[] = [
                'id_pro'                 => null,
                'pro_nombre'             => (string) $det->nombre,
                'pro_codigo'             => '',
                'id_tipo_afectacion'     => 1,
                'impuesto_bolsa'         => 0,
                'id_medida'              => 0,
                'pro_stock'              => 9999,
                'descripcion_afectacion' => 'Gravado - Operación Onerosa',
                'lista_precios'          => [$precio],
                'precio_ref'             => $precio,
                'precio_venta'           => $precio,
                'cantidad'               => (float) $det->cantidad,
            ];
        }

        if ($pedido->pedido_cliente_nombre) {
            $this->nombreCliente           = $pedido->pedido_cliente_nombre;
            $this->pedidoClienteNombreOrig = $pedido->pedido_cliente_nombre;
        }
        if ($pedido->pedido_cliente_doc) {
            $this->numDocumento         = $pedido->pedido_cliente_doc;
            $this->pedidoClienteDocOrig = $pedido->pedido_cliente_doc;
            $this->idTipoDocumento      = strlen($pedido->pedido_cliente_doc) === 11 ? '4' : '2';
        }

        // Pre-cargar tipo comprobante guardado en el pedido
        $this->tipoComprobante = $pedido->pedido_tipo_comprobante ?? '03';
        if ($this->tipoComprobante === '01' && strlen(trim($this->numDocumento)) !== 11) {
            $this->idTipoDocumento = '4';
            $this->numDocumento    = '';
            $this->nombreCliente   = '';
        }

        $this->esGratuita = false;
        $this->cargarSeries();
        $total       = $this->totales['total'];
        $this->pagos = [['id_tipo_pago' => $this->idEfectivo(), 'monto' => $total > 0 ? (string) $total : '', 'marca_tarjeta' => '']];
        $this->vista = 'cobrar';
        $this->dispatch('cerrarModalSeleccion');
        $this->dispatch('vistaCobrando');
    }

    // ── Seleccionar proforma ───────────────────────────────────

    public function seleccionarProforma(int $idProfo): void
    {
        $proforma = DB::table('proformas')
            ->where('id_profo', $idProfo)
            ->where('profo_estado', 1)
            ->where('profo_acti_estado', 0)
            ->first();

        if (!$proforma) {
            session()->flash('error', 'Proforma no encontrada o ya fue procesada.');
            return;
        }

        $detalles = DB::table('proformas_detalles as pd')
            ->join('productos as p', 'p.id_pro', '=', 'pd.id_pro')
            ->join('producto_sucursal as ps', function ($join) {
                $join->on('ps.id_pro', '=', 'pd.id_pro')
                    ->where('ps.id_tienda', $this->idTienda);
            })
            ->leftJoin('tipo_afectacion as ta', 'ta.id_tipo_afectacion', '=', 'ps.id_tipo_afectacion')
            ->where('pd.id_profo', $idProfo)
            ->where('pd.profo_deta_estado', 1)
            ->select(
                'pd.id_pro', 'p.pro_nombre', 'p.pro_codigo',
                'ps.id_tipo_afectacion', 'p.impuesto_bolsa', 'p.id_medida',
                'ps.ps_precio_uni   as pro_precio_uni',
                'ps.ps_precio_uni_2 as pro_precio_uni_2',
                'ps.ps_precio_uni_3 as pro_precio_uni_3',
                'ps.ps_stock        as pro_stock',
                'ta.descripcion     as descripcion_afectacion',
                'pd.profo_deta_cantidad as cantidad',
                'pd.profo_deta_precio   as precio_venta'
            )
            ->get();

        if ($detalles->isEmpty()) {
            session()->flash('error', 'La proforma no tiene productos configurados para esta tienda.');
            return;
        }

        $this->idProforma     = $idProfo;
        $this->esProforma     = true;
        $this->proformaNumero = 'PRO-' . str_pad($proforma->profo_correlativo, 6, '0', STR_PAD_LEFT);
        $this->items          = [];

        foreach ($detalles as $det) {
            $listaPrecios = array_values(array_filter(
                [(float)$det->pro_precio_uni, (float)$det->pro_precio_uni_2, (float)$det->pro_precio_uni_3],
                fn($p) => $p > 0
            ));
            if (empty($listaPrecios)) $listaPrecios = [(float)$det->precio_venta];

            $this->items[] = [
                'id_pro'                 => (int)    $det->id_pro,
                'pro_nombre'             => (string) $det->pro_nombre,
                'pro_codigo'             => (string) ($det->pro_codigo ?? ''),
                'id_tipo_afectacion'     => (int)    ($det->id_tipo_afectacion ?? 1),
                'impuesto_bolsa'         => (int)    $det->impuesto_bolsa,
                'id_medida'              => (int)    $det->id_medida,
                'pro_stock'              => (float)  $det->pro_stock,
                'descripcion_afectacion' => (string) ($det->descripcion_afectacion ?? ''),
                'lista_precios'          => $listaPrecios,
                'precio_ref'             => (float)  $det->precio_venta,
                'precio_venta'           => (float)  $det->precio_venta,
                'cantidad'               => (float)  $det->cantidad,
            ];
        }

        $cliente = DB::table('clientes')->where('id_clientes', $proforma->id_clientes)->first();
        if ($cliente) {
            $this->nombreCliente   = $cliente->cliente_razonsocial ?? $cliente->cliente_nombre ?? 'CLIENTE GENERAL';
            $this->numDocumento    = $cliente->cliente_numero ?? '00000000';
            $this->idTipoDocumento = strlen($this->numDocumento) === 11 ? '4' : '2';
        }

        $this->esGratuita      = false;
        $this->tipoComprobante = '03';
        $this->cargarSeries();
        $total       = $this->totales['total'];
        $this->pagos = [['id_tipo_pago' => $this->idEfectivo(), 'monto' => $total > 0 ? (string)$total : '', 'marca_tarjeta' => '']];
        $this->vista = 'cobrar';
        $this->dispatch('cerrarModalSeleccion');
        $this->dispatch('vistaCobrando');
    }

    public function volverLista(): void
    {
        $this->resetCobrar();
        $this->dispatch('abrirModalSeleccion');
    }

    // ── Series ─────────────────────────────────────────────────

    public function updatedTipoComprobante(): void
    {
        if ($this->tipoComprobante === '01') {
            // Si el cliente ya tiene RUC, conservar sus datos
            if (strlen(trim($this->numDocumento)) === 11) {
                $this->idTipoDocumento = '4';
            } else {
                $this->idTipoDocumento = '4';
                $this->numDocumento    = '';
                $this->nombreCliente   = '';
            }
        } else {
            // Al volver a Boleta/N.Venta restaurar datos originales del pedido si existen
            if ($this->pedidoClienteNombreOrig) {
                $this->nombreCliente   = $this->pedidoClienteNombreOrig;
                $this->numDocumento    = $this->pedidoClienteDocOrig ?: '00000000';
                $docLen = strlen($this->numDocumento);
                $this->idTipoDocumento = $docLen === 11 ? '4' : '2';
            } else {
                $this->idTipoDocumento = '2';
                $this->numDocumento    = '00000000';
                $this->nombreCliente   = 'CLIENTE GENERAL';
            }
        }
        $this->cargarSeries();
    }

    // ── Rectificar comprobante ────────────────────────────────

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

        $pagosActuales = DB::table('ventas_detalle_pagos')
            ->where('id_venta', $idVenta)
            ->get()
            ->keyBy(fn($p) => $p->id_tipo_pago . ':' . ($p->marca_tarjeta ?? ''));

        $medios = [];
        $tarjetaAgregada = false;
        foreach ($this->tiposPago as $tp) {
            $esTarjeta = str_contains(strtoupper((string)($tp->tipo_pago_nombre ?? '')), 'TARJETA');
            if ($esTarjeta && !$tarjetaAgregada) {
                $tarjetaAgregada = true;
                foreach (['Visa', 'Mastercard', 'American Express', 'UnionPay'] as $m) {
                    $pago = $pagosActuales->get($tp->id_tipo_pago . ':' . $m);
                    $medios[] = [
                        'id_tipo_pago' => (int) $tp->id_tipo_pago,
                        'marca'        => $m,
                        'label'        => 'Tarjeta - ' . $m,
                        'monto'        => $pago ? number_format((float)$pago->venta_detalle_pago_monto, 2, '.', '') : '0.00',
                    ];
                }
            } elseif (!$esTarjeta) {
                $pago = $pagosActuales->first(fn($p) => $p->id_tipo_pago == $tp->id_tipo_pago && empty($p->marca_tarjeta));
                $medios[] = [
                    'id_tipo_pago' => (int) $tp->id_tipo_pago,
                    'marca'        => '',
                    'label'        => (string)($tp->tipo_pago_nombre ?? ''),
                    'monto'        => $pago ? number_format((float)$pago->venta_detalle_pago_monto, 2, '.', '') : '0.00',
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

        // Validar suma de medios == total venta (solo Contado)
        if ($this->rectFormasPago == 1) {
            $totalVenta = (float) DB::table('ventas')
                ->where('id_venta', $this->rectVentaId)
                ->value('venta_total');
            $sumaMedios = collect($this->rectMedios)
                ->sum(fn($m) => (float) str_replace(',', '.', $m['monto'] ?? '0'));
            if (round($sumaMedios, 2) !== round($totalVenta, 2)) {
                $this->dispatch('rectificar-error',
                    mensaje: 'Los medios de pago (S/ ' . number_format($sumaMedios, 2) . ') no coinciden con el total del comprobante (S/ ' . number_format($totalVenta, 2) . ').'
                );
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
                DB::table('pedidos')->where('id_pedido', $this->rectIdPedido)
                    ->update(['id_users' => $this->rectVendedor, 'updated_at' => now()]);
            } elseif ($this->rectIdProfo) {
                DB::table('proformas')->where('id_profo', $this->rectIdProfo)
                    ->update(['id_users' => $this->rectVendedor, 'updated_at' => now()]);
            }

            // Solo actualizar medios de pago en ventas al contado
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
            $this->cargarVentasResumen();
            $this->dispatch('cerrarModalRectificar');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
        }
    }

    private function cargarUsuariosRectificar(): void
    {
        // Vendedor: Desarrollador(1), Superadmin(2), Ventas(5) + el vendedor actual
        $idsVendedor = DB::table('model_has_roles')
            ->whereIn('role_id', [1, 2, 5])
            ->pluck('model_id')->unique()->values()->toArray();
        if ($this->rectVendedor > 0 && !in_array($this->rectVendedor, $idsVendedor)) {
            $idsVendedor[] = $this->rectVendedor;
        }

        $this->rectUsuariosVendedor = DB::table('users as u')
            ->where('u.users_estado', 1)
            ->whereIn('u.id_users', $idsVendedor)
            ->select('u.id_users', 'u.nombre_users')
            ->orderBy('u.nombre_users')
            ->get()->toArray();

        // Cobrador: Desarrollador(1), Superadmin(2), Cajero(4) + el cobrador actual
        $idsCobrador = DB::table('model_has_roles')
            ->whereIn('role_id', [1, 2, 4])
            ->pluck('model_id')->unique()->values()->toArray();
        if ($this->rectCobrador > 0 && !in_array($this->rectCobrador, $idsCobrador)) {
            $idsCobrador[] = $this->rectCobrador;
        }

        $this->rectUsuariosCobrador = DB::table('users as u')
            ->where('u.users_estado', 1)
            ->whereIn('u.id_users', $idsCobrador)
            ->select('u.id_users', 'u.nombre_users')
            ->orderBy('u.nombre_users')
            ->get()->toArray();
    }

    private function expirarPedidosVencidos(): void
    {
        $vencidos = DB::table('pedidos')
            ->where('pedido_estado', 0)
            ->whereDate('created_at', '<', now()->toDateString())
            ->get(['id_pedido', 'id_tienda', 'pedido_stock_reservado']);

        foreach ($vencidos as $pedido) {
            DB::beginTransaction();
            try {
                $locked = DB::table('pedidos')
                    ->where('id_pedido', $pedido->id_pedido)
                    ->where('pedido_estado', 0)
                    ->lockForUpdate()
                    ->first();

                if (!$locked) { DB::rollBack(); continue; }

                if ($locked->pedido_stock_reservado) {
                    $detalles = DB::table('pedidos_detalle')
                        ->where('id_pedido', $pedido->id_pedido)
                        ->where('pedido_deta_estado', 1)
                        ->get();

                    foreach ($detalles as $det) {
                        if (!$det->id_pro) continue;
                        $factor = (float)($det->pres_factor ?? 1.0);
                        DB::table('producto_sucursal')
                            ->where('id_pro', (int) $det->id_pro)
                            ->where('id_tienda', $locked->id_tienda)
                            ->increment('ps_stock', (float) $det->pedido_deta_cantidad * $factor);
                    }
                }

                DB::table('pedidos')
                    ->where('id_pedido', $pedido->id_pedido)
                    ->update(['pedido_estado' => 3, 'updated_at' => now()]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->logs->insertarLog($e);
            }
        }
    }

    private function cargarSeries(): void
    {
        $this->series = (new Serie())
            ->listarSerie_caja($this->tipoComprobante, $this->idCajaNumero)
            ->toArray();

        $this->idSerie = !empty($this->series) ? $this->series[0]->id_serie : null;
        $this->actualizarCorrelativo();
    }

    private function actualizarCorrelativo(): void
    {
        if ($this->idSerie) {
            $serie             = DB::table('serie')->where('id_serie', $this->idSerie)->first();
            $this->correlativo = $serie ? (int) $serie->correlativo + 1 : 0;
        } else {
            $this->correlativo = 0;
        }
    }

    public function updatedIdSerie(): void
    {
        $this->actualizarCorrelativo();
    }

    // ── Guardar venta ──────────────────────────────────────────

    protected function permisoCrear(): string
    {
        return 'caja_pedidos.crear';
    }

    public function guardar(): void
    {
        if (!auth()->user()->can($this->permisoCrear())) {
            session()->flash('error', 'No tienes permiso para registrar ventas.');
            return;
        }

        $aperturaCaja = (new CajaModel())->buscar_apertura_caja();
        if (!$aperturaCaja) {
            session()->flash('error', 'No hay caja abierta para hoy. Apertura la caja antes de registrar ventas.');
            return;
        }

        if (!$this->idSerie) {
            session()->flash('error', 'No hay serie disponible. Configure series para esta caja.');
            return;
        }

        $total = $this->totales['total'];

        if ($this->idFormasPago === 1 && !$this->esGratuita) {
            foreach ($this->pagos as $pago) {
                if (empty($pago['id_tipo_pago'])) {
                    session()->flash('error', 'Seleccione el medio de pago en cada línea.');
                    return;
                }
                if ((float)($pago['monto'] ?? 0) <= 0) {
                    session()->flash('error', 'Ingrese un monto mayor a cero en cada línea de pago.');
                    return;
                }
                if ($this->esTipoPagoTarjeta((int)$pago['id_tipo_pago']) && empty($pago['marca_tarjeta'])) {
                    session()->flash('error', 'Seleccione la marca de la tarjeta (Visa, Mastercard, etc.).');
                    return;
                }
            }
            $totalPagado = collect($this->pagos)->sum(fn($p) => (float)($p['monto'] ?? 0));
            if ($totalPagado < $total) {
                session()->flash('error', 'El monto recibido (S/ ' . number_format($totalPagado, 2) . ') es menor al total (S/ ' . number_format($total, 2) . ').');
                return;
            }
        }

        DB::beginTransaction();
        try {
            $informacionSerie = DB::table('serie')
                ->where('id_serie', $this->idSerie)
                ->lockForUpdate()
                ->first();

            if (!$informacionSerie) throw new \Exception('Serie no encontrada.');

            // Resolver cliente
            $idCliente  = $this->resolverCliente();
            $calc       = (new CalcularMontosVenta())->calcularMontos($this->items, $this->porcentajeIgv, $this->idTienda, $this->esGratuita);
            $pagoClient = $this->esGratuita ? 0 : (
                $this->idFormasPago === 1
                    ? collect($this->pagos)->sum(fn($p) => (float)($p['monto'] ?? 0))
                    : $calc['total']
            );

            $venta                           = new Ventas();
            $venta->id_caja                  = $aperturaCaja->id_caja;
            $venta->id_caja_numero           = $aperturaCaja->id_caja_numero;
            $venta->id_empresa               = $this->idEmpresa;
            $venta->id_sucursal              = $this->idTienda ?: null;
            $venta->id_users                 = auth()->user()->id_users;
            $venta->id_clientes              = $idCliente;
            $venta->id_moneda                = 1;
            $venta->venta_tipo_campo         = 0;
            $venta->venta_condicion_resumen  = 1;
            $venta->venta_tipo_envio         = 0;
            $venta->venta_tipo               = $this->tipoComprobante;
            $venta->venta_serie              = $informacionSerie->serie;
            $venta->venta_correlativo        = $informacionSerie->correlativo + 1;
            $venta->venta_totalgratuita      = $calc['gratuito'];
            $venta->venta_totalexonerada     = $calc['exonerada'];
            $venta->venta_totalinafecta      = $calc['inafectada'];
            $venta->venta_totalgravada       = $calc['gravada'];
            $venta->venta_totaligv           = $calc['igv'];
            $venta->venta_incluye_igv        = 1;
            $venta->venta_porcentaje_igv     = $this->porcentajeIgv;
            $venta->venta_totaldescuento     = 0;
            $venta->venta_icbper             = $calc['impuesto'];
            $venta->venta_total              = $calc['total'];
            $venta->venta_pago_cliente       = $pagoClient;
            $venta->venta_vuelto             = ($this->esGratuita || $this->idFormasPago !== 1) ? 0 : max(0, $pagoClient - $calc['total']);
            $venta->venta_fecha              = now()->format('Y-m-d H:i:s');
            $venta->tipo_documento_modificar = '';
            $venta->serie_modificar          = null;
            $venta->correlativo_modificar    = '';
            $venta->venta_estado_sunat       = 0;
            $venta->id_formas_pago           = $this->esGratuita ? 1 : $this->idFormasPago;
            $venta->venta_estado_pago        = ($this->esGratuita || $this->idFormasPago !== 2) ? 2 : 0;
            $venta->venta_observacion        = $this->esGratuita ? 'TRANSFERENCIA A TÍTULO GRATUITO' : null;
            $venta->venta_codigo             = microtime(true);
            $venta->id_profo                 = $this->esProforma ? $this->idProforma : null;
            $venta->id_pedido                = $this->esProforma ? null : $this->idPedido;
            $venta->save();
            $idVenta = $venta->id_venta;

            Serie::where('id_serie', $informacionSerie->id_serie)
                ->update(['correlativo' => $informacionSerie->correlativo + 1]);

            foreach ($this->items as $item) {
                $idPro    = $item['id_pro'] !== null ? (int) $item['id_pro'] : null;
                $precio   = (float) $item['precio_venta'];
                $cantidad = (float) $item['cantidad'];
                $tasa     = $this->porcentajeIgv / 100;
                $tipoAfec = $this->esGratuita ? 4 : (int) ($item['id_tipo_afectacion'] ?? 1);

                $precioSinIGV = (!$this->esGratuita && $item['impuesto_bolsa'] == 1) ? 0 : round($precio, 2);
                $igvItem      = ($tipoAfec === 1 && $item['impuesto_bolsa'] == 0) ? round($precioSinIGV * $tasa, 2) : 0;
                $precioConIGV = $tipoAfec === 1 ? round($precioSinIGV + $igvItem, 2) : $precioSinIGV;
                $icbper       = (!$this->esGratuita && $item['impuesto_bolsa'] == 1) ? 0.50 * $cantidad : 0;

                $det = new Venta_detalle();
                $det->id_venta                     = $idVenta;
                $det->id_pro                       = $idPro;
                $det->venta_detalle_precio_ref      = round($precio, 2);
                $det->venta_detalle_valor_unitario  = $precioSinIGV;
                $det->venta_detalle_precio_unitario = $precioConIGV;
                $det->venta_detalle_nombre_producto = $item['pro_nombre'];
                $det->pres_nombre                   = !empty($item['pres_nombre']) ? $item['pres_nombre'] : null;
                $det->pres_factor                   = (float) ($item['pres_factor'] ?? 1.0);
                $det->venta_detalle_cantidad        = $cantidad;
                $det->venta_detalle_total_igv       = $igvItem * $cantidad;
                $det->venta_detalle_porcentaje_igv  = $tipoAfec === 1 ? $this->porcentajeIgv : 0;
                $det->venta_detalle_total_icbper    = $icbper;
                $det->venta_detalle_valor_total     = $precioSinIGV * $cantidad;
                $det->venta_detalle_importe_total   = $precioConIGV * $cantidad;
                $det->save();
            }

            if ($this->idFormasPago === 1 && !$this->esGratuita) {
                foreach ($this->pagos as $lineaPago) {
                    $p = new Ventas_detalle_pago();
                    $p->id_venta                  = $idVenta;
                    $p->id_tipo_pago              = (int)   $lineaPago['id_tipo_pago'];
                    $p->marca_tarjeta             = $lineaPago['marca_tarjeta'] ?: null;
                    $p->venta_detalle_pago_monto  = (float) $lineaPago['monto'];
                    $p->venta_detalle_pago_estado = 1;
                    $p->save();
                }
            }

            // Si es crédito (y no gratuita), registrar una cuota para que aparezca en CxC
            if ($this->idFormasPago === 2 && !$this->esGratuita) {
                DB::table('ventas_cuotas')->insert([
                    'id_venta'            => $idVenta,
                    'id_tipo_pago'        => null,
                    'id_formas_pago'      => 2,
                    'venta_cuota_numero'  => '1',
                    'venta_cuota_importe' => $calc['total'],
                    'venta_cuota_fecha'   => now()->toDateString(),
                    'venta_cuota_estado'  => 1,
                    'venta_cuota_pago'    => 0,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                // Si el comprador es una empresa vinculada, crear cuenta por pagar espejo
                $this->crearCxpVinculada(
                    $calc['total'],
                    $informacionSerie->serie . '-' . str_pad($informacionSerie->correlativo + 1, 8, '0', STR_PAD_LEFT),
                    $this->tipoComprobante === '01' ? 'Factura' : 'Boleta'
                );
            }

            if ($this->esProforma) {
                foreach ($this->items as $item) {
                    if (!$item['id_pro']) continue;
                    $ps = DB::table('producto_sucursal')
                        ->where('id_pro', (int)$item['id_pro'])
                        ->where('id_tienda', $this->idTienda)
                        ->lockForUpdate()
                        ->first();
                    if ($ps && (float)$ps->ps_stock >= (float)$item['cantidad']) {
                        DB::table('producto_sucursal')
                            ->where('id_pro', (int)$item['id_pro'])
                            ->where('id_tienda', $this->idTienda)
                            ->decrement('ps_stock', (float)$item['cantidad']);
                    }
                }
                DB::table('proformas')
                    ->where('id_profo', $this->idProforma)
                    ->update(['profo_acti_estado' => 1, 'updated_at' => now()]);
            } else {
                DB::table('pedidos')
                    ->where('id_pedido', $this->idPedido)
                    ->update([
                        'pedido_estado'    => 1,
                        'pedido_tipo_pago' => $this->idFormasPago,
                        'updated_at'       => now(),
                    ]);
            }

            DB::commit();

            $this->dispatch('abrirComprobanteCaja', idVenta: $idVenta);

            if ($this->esProforma) {
                $this->resetCobrar();
                $this->dispatch('abrirModalSeleccion');
                session()->flash('success', 'Proforma cobrada y stock actualizado correctamente.');
            } else {
                $idPedidoDespacho = $this->idPedido;
                $this->resetCobrar();
                $this->idPedidoPendienteDespacho = $idPedidoDespacho;
                $this->despacharPedido();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al registrar la venta: ' . $e->getMessage());
        }
    }

    private function crearCxpVinculada(float $total, string $numDoc, string $tipoDoc): void
    {
        $rucComprador = trim($this->numDocumento);
        if (strlen($rucComprador) !== 11) return;

        $empresaVendedora = DB::table('empresa')->where('id_empresa', $this->idEmpresa)->first();
        if (!$empresaVendedora || !$empresaVendedora->id_grupo || !$empresaVendedora->empresa_ruc) return;

        $empresaCompradora = DB::table('empresa')
            ->where('empresa_ruc', $rucComprador)
            ->where('id_grupo', $empresaVendedora->id_grupo)
            ->where('id_empresa', '!=', $this->idEmpresa)
            ->first();

        if (!$empresaCompradora) return;

        // Buscar o crear proveedor para la empresa vendedora en contexto del comprador
        $idProveedor = DB::table('proveedores')
            ->where('proveedores_numero_documento', $empresaVendedora->empresa_ruc)
            ->where('id_empresa', $empresaCompradora->id_empresa)
            ->value('id_proveedores');

        if (!$idProveedor) {
            $idProveedor = DB::table('proveedores')->insertGetId([
                'id_empresa'                   => $empresaCompradora->id_empresa,
                'id_sede'                      => 1,
                'id_tipo_documento'            => 4,
                'proveedores_nombre'           => $empresaVendedora->empresa_nombrecomercial,
                'proveedores_numero_documento' => $empresaVendedora->empresa_ruc,
                'proveedores_estado'           => '1',
                'created_at'                   => now(),
                'updated_at'                   => now(),
            ]);
        }

        $monto = round($total, 2);
        DB::table('cuentas_pagar')->insert([
            'id_orden_compra'      => null,
            'id_proveedores'       => $idProveedor,
            'id_empresa'           => $empresaCompradora->id_empresa,
            'id_sucursal'          => null,
            'id_users_registro'    => auth()->user()->id_users,
            'cp_numero_doc'        => $numDoc,
            'cp_tipo_doc'          => $tipoDoc,
            'cp_fecha_emision'     => now()->toDateString(),
            'cp_fecha_vencimiento' => now()->toDateString(),
            'cp_monto_total'       => $monto,
            'cp_monto_pagado'      => 0,
            'cp_saldo'             => $monto,
            'cp_estado'            => 1,
            'cp_observacion'       => 'Generado automáticamente desde venta al crédito.',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }

    // ── Despacho desde caja ────────────────────────────────────

    public function despacharPedido(): void
    {
        if (!$this->idPedidoPendienteDespacho) {
            $this->dispatch('abrirModalSeleccion');
            return;
        }

        DB::beginTransaction();
        try {
            $pedido = DB::table('pedidos')
                ->where('id_pedido', $this->idPedidoPendienteDespacho)
                ->where('pedido_estado', 1)
                ->first();

            if (!$pedido) {
                DB::rollBack();
                $this->limpiarDespacho();
                session()->flash('error', 'El pedido no está disponible para despacho.');
                $this->dispatch('abrirModalSeleccion');
                return;
            }

            $detalles = DB::table('pedidos_detalle')
                ->where('id_pedido', $this->idPedidoPendienteDespacho)
                ->where('pedido_deta_estado', 1)
                ->get();

            // Solo descontar stock si el pedido NO tiene reserva previa (pedidos creados antes del sistema de reservas)
            if (!$pedido->pedido_stock_reservado) {
                foreach ($detalles as $det) {
                    $idPro    = (int)   $det->id_pro;
                    $cantidad = (float) $det->pedido_deta_cantidad;

                    $ps = DB::table('producto_sucursal')
                        ->where('id_pro',    $idPro)
                        ->where('id_tienda', $this->idTienda)
                        ->lockForUpdate()
                        ->first();

                    if ($ps && (float) $ps->ps_stock >= $cantidad) {
                        DB::table('producto_sucursal')
                            ->where('id_pro',    $idPro)
                            ->where('id_tienda', $this->idTienda)
                            ->decrement('ps_stock', $cantidad);
                    }
                }
            }
            // Si pedido_stock_reservado = 1, el stock ya fue descontado al crear el pedido

            DB::table('pedidos')
                ->where('id_pedido', $this->idPedidoPendienteDespacho)
                ->update(['pedido_estado' => 2, 'updated_at' => now()]);

            DB::commit();

            $this->limpiarDespacho();
            $this->dispatch('abrirModalSeleccion');
            session()->flash('success', 'Pedido despachado y stock actualizado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logs->insertarLog($e);
            session()->flash('error', 'Error al despachar: ' . $e->getMessage());
            $this->limpiarDespacho();
        }
    }

    public function saltarDespacho(): void
    {
        $this->limpiarDespacho();
        $this->dispatch('abrirModalSeleccion');
    }

    public function despacharDesdeLista(int $idPedido): void
    {
        $this->idPedidoPendienteDespacho = $idPedido;
        $this->despacharPedido();
    }

    public function reimprimir(int $idVenta): void
    {
        $this->dispatch('abrirComprobanteCaja', idVenta: $idVenta);
    }

    private function limpiarDespacho(): void
    {
        $this->idPedidoPendienteDespacho = null;
        $this->idVentaReciente           = null;
        $this->ventaNumeroReciente       = '';
        $this->itemsDespacho             = [];
    }

    // ── Helpers ────────────────────────────────────────────────

    private function resolverCliente(): int
    {
        $numDoc     = trim($this->numDocumento);
        $nombreReal = trim($this->nombreCliente);

        // Cliente con documento real
        if ($numDoc && !in_array($numDoc, ['00000000', '11111111', '00000000000', '11111111111'])) {
            $cliente = DB::table('clientes')
                ->where('cliente_numero', $numDoc)
                ->where('cliente_estado', 1)
                ->first();

            if ($cliente) return (int) $cliente->id_clientes;

            $microtime = microtime(true);
            DB::table('clientes')->insert([
                'id_tipo_documento'   => $this->idTipoDocumento,
                'cliente_razonsocial' => $nombreReal,
                'cliente_nombre'      => $nombreReal,
                'cliente_numero'      => $numDoc,
                'cliente_fecha'       => now(),
                'cliente_estado'      => 1,
                'cliente_codigo'      => $microtime,
                'id_empresa'          => $this->idEmpresa,
            ]);
            return (int) DB::table('clientes')->where('cliente_codigo', $microtime)->value('id_clientes');
        }

        // Cliente con nombre pero sin documento real: buscar o crear por nombre
        if ($nombreReal && strtoupper($nombreReal) !== 'CLIENTE GENERAL') {
            $existe = DB::table('clientes')
                ->where('cliente_nombre', $nombreReal)
                ->where('cliente_numero', '00000000')
                ->where('id_empresa', $this->idEmpresa)
                ->first();

            if ($existe) return (int) $existe->id_clientes;

            $microtime = microtime(true);
            DB::table('clientes')->insert([
                'id_tipo_documento'   => 2,
                'cliente_razonsocial' => $nombreReal,
                'cliente_nombre'      => $nombreReal,
                'cliente_numero'      => '00000000',
                'cliente_fecha'       => now(),
                'cliente_estado'      => 1,
                'cliente_codigo'      => $microtime,
                'id_empresa'          => $this->idEmpresa,
            ]);
            return (int) DB::table('clientes')->where('cliente_codigo', $microtime)->value('id_clientes');
        }

        // Cliente genérico: buscar específicamente por nombre CLIENTE GENERAL
        $generico = DB::table('clientes')
            ->where('cliente_numero', '00000000')
            ->where('id_empresa', $this->idEmpresa)
            ->where(function ($q) {
                $q->where('cliente_nombre', 'CLIENTE GENERAL')
                  ->orWhere('cliente_razonsocial', 'CLIENTE GENERAL');
            })
            ->first();

        if ($generico) return (int) $generico->id_clientes;

        $microtime = microtime(true);
        DB::table('clientes')->insert([
            'id_tipo_documento'   => 2,
            'cliente_razonsocial' => 'CLIENTE GENERAL',
            'cliente_nombre'      => 'CLIENTE GENERAL',
            'cliente_numero'      => '00000000',
            'cliente_fecha'       => now(),
            'cliente_estado'      => 1,
            'cliente_codigo'      => $microtime,
            'id_empresa'          => $this->idEmpresa,
        ]);
        return (int) DB::table('clientes')->where('cliente_codigo', $microtime)->value('id_clientes');
    }

    // ── Edición de items en cobrar ─────────────────────────────

    public function quitarItemCobrar(int $index): void
    {
        if (count($this->items) <= 1) return;
        array_splice($this->items, $index, 1);
        $this->items = array_values($this->items);
    }

    public function updated(string $name): void
    {
        if (preg_match('/^items\.(\d+)\.cantidad$/', $name, $m)) {
            $idx = (int) $m[1];
            $val = (float) ($this->items[$idx]['cantidad'] ?? 0);
            if ($val < 1) {
                $this->items[$idx]['cantidad'] = 1;
            }
        }
    }

    private function resetCobrar(): void
    {
        $this->idPedido        = null;
        $this->pedidoNumero    = '';
        $this->idProforma      = null;
        $this->esProforma      = false;
        $this->proformaNumero  = '';
        $this->items           = [];
        $this->idTipoDocumento = '2';
        $this->numDocumento    = '00000000';
        $this->nombreCliente   = 'CLIENTE GENERAL';
        $this->tipoComprobante = '03';
        $this->series          = [];
        $this->idSerie         = null;
        $this->correlativo     = 0;
        $this->idFormasPago            = 1;
        $this->pagos                   = [['id_tipo_pago' => null, 'monto' => '', 'marca_tarjeta' => '']];
        $this->esGratuita              = false;
        $this->pedidoClienteNombreOrig = '';
        $this->pedidoClienteDocOrig    = '';
        $this->resetErrorBag();
    }

    public function cambiarFormaPago(int $id): void
    {
        if ($this->esGratuita) return;
        $this->idFormasPago = $id;
        if ($id === 1) {
            $total       = $this->totales['total'];
            $this->pagos = [['id_tipo_pago' => $this->idEfectivo(), 'monto' => $total > 0 ? (string) $total : '', 'marca_tarjeta' => '']];
        } else {
            $this->pagos = [];
        }
    }

    private function idEfectivo(): ?int
    {
        $tp = collect($this->tiposPago)
            ->first(fn($t) => stripos((string)($t->tipo_pago_nombre ?? $t['tipo_pago_nombre'] ?? ''), 'efectivo') !== false);
        return $tp ? (int)($tp->id_tipo_pago ?? $tp['id_tipo_pago']) : null;
    }

    public function cambiarTipoPago(int $index, string $valor): void
    {
        if (!isset($this->pagos[$index])) return;

        if (str_contains($valor, ':')) {
            [$id, $marca] = explode(':', $valor, 2);
            $this->pagos[$index]['id_tipo_pago']  = (int) $id;
            $this->pagos[$index]['marca_tarjeta'] = $marca;
        } else {
            $this->pagos[$index]['id_tipo_pago']  = (int) $valor;
            $this->pagos[$index]['marca_tarjeta'] = '';
        }
    }

    public function agregarPago(): void
    {
        $defaultId = !empty($this->tiposPago)
            ? (int)($this->tiposPago[0]->id_tipo_pago ?? $this->tiposPago[0]['id_tipo_pago'] ?? null) ?: null
            : null;
        $this->pagos[] = ['id_tipo_pago' => $defaultId, 'monto' => '', 'marca_tarjeta' => ''];
    }

    private function esTipoPagoTarjeta(?int $idTipoPago): bool
    {
        if (!$idTipoPago) return false;
        foreach ($this->tiposPago as $t) {
            $id     = (int)($t->id_tipo_pago ?? $t['id_tipo_pago'] ?? 0);
            $nombre = strtoupper((string)($t->tipo_pago_nombre ?? $t['tipo_pago_nombre'] ?? ''));
            if ($id === $idTipoPago) {
                return str_contains($nombre, 'TARJETA');
            }
        }
        return false;
    }

    public function quitarPago(int $index): void
    {
        array_splice($this->pagos, $index, 1);
        $this->pagos = array_values($this->pagos);
    }

    public function updatedNumDocumento(): void
    {
        $doc = trim($this->numDocumento);
        $len = strlen($doc);

        if ($len !== 8 && $len !== 11) return;

        $cliente = DB::table('clientes')
            ->where('cliente_numero', $doc)
            ->where('cliente_estado', 1)
            ->first();

        if ($cliente) {
            $this->nombreCliente   = $cliente->cliente_nombre ?? $cliente->cliente_razonsocial ?? '';
            $this->idTipoDocumento = $len === 11 ? '4' : '2';
            return;
        }

        $tipo      = $len === 11 ? '4' : '2';
        $resultado = (new General())->consultar_documento_migo($tipo, $doc);

        if (($resultado['success'] ?? false) && !empty($resultado['data']['nombre'])) {
            $this->nombreCliente   = $resultado['data']['nombre'];
            $this->idTipoDocumento = $tipo;
        }
    }

    public function updatedBuscarModal(): void { }

    public function cambiarTab(string $tab): void
    {
        $this->tabModal = $tab;

        if ($tab === 'resumen_ventas') {
            $this->cargarVentasResumen();
        }
        if ($tab === 'cierre_caja') {
            $this->cargarResumenCierre();
        }
    }

    private function cargarVentasResumen(): void
    {
        if (!$this->idCaja) {
            $this->ventasResumen = [];
            return;
        }

        $this->ventasResumen = DB::table('ventas as v')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->leftJoin('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->where('v.id_caja', $this->idCaja)
            ->whereNull('va.id_venta')
            ->orderBy('v.id_venta', 'desc')
            ->select(
                'v.id_venta',
                'v.id_users',
                'v.id_pedido',
                'v.id_profo',
                'v.id_formas_pago',
                'v.venta_serie',
                'v.venta_correlativo',
                'v.venta_tipo',
                'v.venta_total',
                'v.created_at',
                DB::raw("COALESCE(c.cliente_razonsocial, c.cliente_nombre, 'Sin cliente') as cliente_nombre"),
                DB::raw("COALESCE(c.cliente_numero, '') as cliente_doc")
            )
            ->get()
            ->toArray();
    }

    // ── Apertura desde modal ───────────────────────────────────

    private function cargarCajasDisponibles(): void
    {
        $hoy    = now()->toDateString();
        $userId = auth()->user()->id_users;

        // Si el usuario ya tuvo una caja hoy y la cerró, bloquear nueva apertura
        $this->cajaCerradaHoy = DB::table('caja')
            ->where('id_users_apertura', $userId)
            ->where('caja_fecha', $hoy)
            ->where('caja_estado', 0)
            ->exists();

        if ($this->cajaCerradaHoy) {
            $this->cajasDisponibles = [];
            $this->idCajaParaAbrir  = '';
            return;
        }

        $idTienda = DB::table('user_tienda')
            ->where('id_users', $userId)
            ->value('id_tienda');

        $query = DB::table('caja_numero as cn')
            ->leftJoin('caja as c', function ($j) use ($hoy) {
                $j->on('c.id_caja_numero', '=', 'cn.id_caja_numero')
                  ->where('c.caja_fecha', $hoy)
                  ->where('c.caja_estado', 1);
            })
            ->where('cn.caja_numero_estado', 1)
            ->orderBy('cn.caja_numero_nombre')
            ->select('cn.id_caja_numero', 'cn.caja_numero_nombre',
                     DB::raw('CASE WHEN c.id_caja IS NOT NULL THEN 1 ELSE 0 END as ya_abierta'));

        if ($idTienda) {
            $query->where('cn.id_tienda', $idTienda);
        }

        $this->cajasDisponibles = $query->get()
            ->map(fn($c) => [
                'id_caja_numero'     => $c->id_caja_numero,
                'caja_numero_nombre' => $c->caja_numero_nombre,
                'ya_abierta'         => (bool) $c->ya_abierta,
            ])->toArray();

        $this->idCajaParaAbrir = '';
        foreach ($this->cajasDisponibles as $cn) {
            if (!$cn['ya_abierta']) {
                $this->idCajaParaAbrir = (string) $cn['id_caja_numero'];
                break;
            }
        }
    }

    public function aperturarCajaDesdeModal(): void
    {
        $this->validate([
            'idCajaParaAbrir'   => 'required',
            'montoAperturaForm' => 'required|numeric|min:0',
        ], [
            'idCajaParaAbrir.required'   => 'Seleccione una caja.',
            'montoAperturaForm.required' => 'Ingrese el monto de apertura.',
            'montoAperturaForm.numeric'  => 'El monto debe ser un número válido.',
            'montoAperturaForm.min'      => 'El monto no puede ser negativo.',
        ]);

        try {
            if (DB::table('caja')->where('id_users_apertura', auth()->id())->where('caja_fecha', now()->toDateString())->where('caja_estado', 1)->exists()) {
                session()->flash('errorCaja', 'Ya tienes una caja aperturada hoy.');
                return;
            }
            if (DB::table('caja')->where('id_caja_numero', $this->idCajaParaAbrir)->where('caja_fecha', now()->toDateString())->where('caja_estado', 1)->exists()) {
                session()->flash('errorCaja', 'Esta caja ya se encuentra aperturada.');
                return;
            }

            DB::table('caja')->insert([
                'id_caja_numero'      => $this->idCajaParaAbrir,
                'caja_fecha'          => now()->toDateString(),
                'id_users_apertura'   => auth()->id(),
                'caja_apertura'       => $this->montoAperturaForm,
                'caja_fecha_apertura' => now()->toDateTimeString(),
                'caja_estado'         => 1,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $caja = (new CajaModel())->buscar_apertura_caja();
            if ($caja) {
                $this->validarCaja  = true;
                $this->idCaja       = (int) $caja->id_caja;
                $this->idCajaNumero = (int) $caja->id_caja_numero;

                $cn = DB::table('caja_numero')->where('id_caja_numero', $this->idCajaNumero)->first();
                $this->idTienda   = (int) ($cn->id_tienda ?? 0);
                $this->nombreCaja = $cn->caja_numero_nombre ?? '';

                if ($this->idTienda) {
                    $tienda = DB::table('tiendas')->where('id_tienda', $this->idTienda)->first();
                    $this->idEmpresa    = (int) ($tienda->id_empresa ?? 1);
                    $this->nombreTienda = $tienda->tienda_nombre ?? '';
                }
            }

            $this->montoAperturaForm = '';
            $this->cajasDisponibles  = [];
            $this->tabModal          = 'pedidos';
            session()->flash('successCaja', 'Caja aperturada correctamente.');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('errorCaja', 'Error al aperturar la caja. Intente nuevamente.');
        }
    }

    // ── Cierre de caja ─────────────────────────────────────────

    private function cargarResumenCierre(): void
    {
        if (!$this->idCaja) {
            $this->resumenCierre = [];
            return;
        }

        $tiposPago   = DB::table('tipo_pago')->where('tipo_pago_estado', 1)->get(['id_tipo_pago', 'tipo_pago_nombre']);
        $idsEfectivo = $tiposPago->filter(fn($t) => stripos($t->tipo_pago_nombre, 'efectivo') !== false)->pluck('id_tipo_pago')->toArray();

        $baseQuery = fn() => DB::table('ventas_detalle_pagos as vdp')
            ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->where('v.id_caja', $this->idCaja)
            ->where('vdp.venta_detalle_pago_estado', 1);

        $efectivo = !empty($idsEfectivo)
            ? (float) $baseQuery()->whereIn('vdp.id_tipo_pago', $idsEfectivo)->sum('vdp.venta_detalle_pago_monto')
            : 0.0;

        $ventasPorMedio = DB::table('ventas_detalle_pagos as vdp')
            ->select('tp.tipo_pago_nombre', DB::raw('SUM(vdp.venta_detalle_pago_monto) as total'))
            ->join('ventas as v', 'v.id_venta', '=', 'vdp.id_venta')
            ->join('tipo_pago as tp', 'tp.id_tipo_pago', '=', 'vdp.id_tipo_pago')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->where('v.id_caja', $this->idCaja)
            ->where('vdp.venta_detalle_pago_estado', 1)
            ->groupBy('tp.id_tipo_pago', 'tp.tipo_pago_nombre')
            ->get();

        $nc = (float) DB::table('ventas as v')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->where('v.venta_tipo', '07')
            ->where('v.id_caja', $this->idCaja)
            ->sum('v.venta_total');

        $gastos = (float) DB::table('gastos')
            ->where('gasto_estado', 1)->where('gasto_tipo', 1)
            ->where('id_caja_numero', $this->idCajaNumero)
            ->whereDate('gasto_fecha', now()->toDateString())
            ->sum('gasto_monto');

        $ingresosGastos = (float) DB::table('gastos')
            ->where('gasto_estado', 1)->where('gasto_tipo', 2)
            ->where('id_caja_numero', $this->idCajaNumero)
            ->whereDate('gasto_fecha', now()->toDateString())
            ->sum('gasto_monto');

        $cuotas   = (float) DB::table('pagos_cuotas as pc')
            ->join('ventas_cuotas as vc', 'vc.id_ventas_cuotas', '=', 'pc.id_ventas_cuotas')
            ->join('ventas as v', 'v.id_venta', '=', 'vc.id_venta')
            ->whereNull('pc.deleted_at')
            ->where('v.id_caja', $this->idCaja)
            ->sum('pc.pagos_cuota_monto');

        $ingresos = (float) DB::table('caja_movimientos')->whereNull('deleted_at')->where('id_caja', $this->idCaja)->where('tipo', 1)->sum('monto');
        $egresos  = (float) DB::table('caja_movimientos')->whereNull('deleted_at')->where('id_caja', $this->idCaja)->where('tipo', 2)->sum('monto');

        $apertura     = (float) DB::table('caja')->where('id_caja', $this->idCaja)->value('caja_apertura');
        $totalVentas  = (float) $ventasPorMedio->sum('total');
        $totalSistema = round($apertura + $efectivo + $cuotas + $ingresos + $ingresosGastos - $egresos - $nc - $gastos, 2);
        $numVentas    = DB::table('ventas as v')
            ->leftJoin('ventas_anulados as va', 'va.id_venta', '=', 'v.id_venta')
            ->whereNull('va.id_venta')
            ->whereIn('v.venta_tipo', ['01', '03', '20'])
            ->where('v.id_caja', $this->idCaja)
            ->count();

        $this->resumenCierre = [
            'apertura'       => $apertura,
            'total_ventas'   => round($totalVentas, 2),
            'efectivo'       => round($efectivo, 2),
            'nc'             => round($nc, 2),
            'gastos'         => round($gastos, 2),
            'cobros'         => round($cuotas, 2),
            'ingresos'       => round($ingresos, 2),
            'egresos'        => round($egresos, 2),
            'total_sistema'  => $totalSistema,
            'num_ventas'     => $numVentas,
            'ventasPorMedio' => $ventasPorMedio->map(fn($v) => [
                'tipo_pago_nombre' => $v->tipo_pago_nombre,
                'total'            => (float) $v->total,
            ])->toArray(),
        ];
    }

    public function cerrarCaja(): void
    {
        if (!$this->idCaja) {
            session()->flash('errorCaja', 'No hay caja abierta.');
            return;
        }

        $this->validate([
            'montoCierreForm' => 'required|numeric|min:0',
        ], [
            'montoCierreForm.required' => 'Ingrese el monto de cierre.',
            'montoCierreForm.numeric'  => 'El monto debe ser un número válido.',
            'montoCierreForm.min'      => 'El monto no puede ser negativo.',
        ]);

        try {
            DB::table('caja')->where('id_caja', $this->idCaja)->update([
                'id_users_cierre'   => auth()->id(),
                'caja_cierre'       => $this->montoCierreForm,
                'caja_fecha_cierre' => now()->toDateTimeString(),
                'caja_estado'       => 0,
                'updated_at'        => now(),
            ]);

            $this->validarCaja       = false;
            $this->idCaja            = 0;
            $this->idCajaNumero      = 0;
            $this->idTienda          = 0;
            $this->idEmpresa         = 0;
            $this->nombreCaja        = '';
            $this->nombreTienda      = '';
            $this->montoCierreForm   = '';
            $this->resumenCierre     = [];
            $this->tabModal          = 'pedidos';
            $this->items             = [];

            $this->cargarCajasDisponibles();
            session()->flash('successCaja', 'Caja cerrada correctamente.');
            $this->dispatch('cerrarModalConfirmCierre');

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('errorCaja', 'Error al cerrar la caja.');
        }
    }

    // ── Render ─────────────────────────────────────────────────

    public function render(): \Illuminate\View\View
    {
        $pedidos   = collect();
        $proformas = collect();
        $t         = trim($this->buscarModal);

        if ($this->tabModal === 'pedidos') {
            $query = DB::table('pedidos as p')
                ->join('users as u', 'u.id_users', '=', 'p.id_users')
                ->where('p.pedido_estado', 0)
                ->whereDate('p.created_at', now()->toDateString())
                ->select(
                    'p.id_pedido', 'p.pedido_numero', 'p.pedido_cliente_nombre',
                    'p.pedido_cliente_doc', 'p.created_at', 'p.pedido_tipo_pago',
                    'u.nombre_users',
                    DB::raw('(SELECT COUNT(*) FROM pedidos_detalle pd WHERE pd.id_pedido = p.id_pedido AND pd.pedido_deta_estado = 1) as total_items'),
                    DB::raw('(SELECT SUM(pd.pedido_deta_precio * pd.pedido_deta_cantidad) FROM pedidos_detalle pd WHERE pd.id_pedido = p.id_pedido AND pd.pedido_deta_estado = 1) as total_pedido')
                );

            if ($this->idTienda) {
                $query->where('p.id_tienda', $this->idTienda);
            } else {
                $query->whereRaw('0 = 1');
            }

            if ($t !== '') {
                $query->where(function ($q) use ($t) {
                    $q->where('p.pedido_numero',          'like', "%{$t}%")
                      ->orWhere('p.pedido_cliente_nombre', 'like', "%{$t}%")
                      ->orWhere('p.pedido_cliente_doc',    'like', "%{$t}%");
                });
            }

            $pedidos = $query->orderBy('p.id_pedido', 'desc')->limit(25)->get();

        } elseif ($this->tabModal === 'proformas') {
            $qp = DB::table('proformas as pf')
                ->join('clientes as c', 'c.id_clientes', '=', 'pf.id_clientes')
                ->join('users as u', 'u.id_users', '=', 'pf.id_users')
                ->leftJoin('sucursals as s', 's.id_sucursal', '=', 'pf.id_sucursal')
                ->where('pf.profo_estado', 1)
                ->where('pf.profo_acti_estado', 0)
                ->whereDate('pf.created_at', now()->toDateString())
                ->where(function ($q) {
                    $q->where('s.id_empresa', $this->idEmpresa)
                      ->orWhere('pf.id_sucursal', '<=', 0);
                })
                ->select(
                    'pf.id_profo', 'pf.profo_serie', 'pf.profo_correlativo',
                    'pf.profo_forma_pago', 'pf.created_at',
                    'c.cliente_nombre', 'c.cliente_numero', 'c.cliente_razonsocial',
                    'u.nombre_users',
                    DB::raw('(SELECT COUNT(*) FROM proformas_detalles pd WHERE pd.id_profo = pf.id_profo AND pd.profo_deta_estado = 1) as total_items'),
                    DB::raw('(SELECT SUM(pd.profo_deta_precio * pd.profo_deta_cantidad) FROM proformas_detalles pd WHERE pd.id_profo = pf.id_profo AND pd.profo_deta_estado = 1) as total_proforma')
                );

            if ($t !== '') {
                $qp->where(function ($q) use ($t) {
                    $q->where('pf.profo_correlativo',  'like', "%{$t}%")
                      ->orWhere('c.cliente_nombre',      'like', "%{$t}%")
                      ->orWhere('c.cliente_razonsocial', 'like', "%{$t}%")
                      ->orWhere('c.cliente_numero',      'like', "%{$t}%");
                });
            }

            $proformas = $qp->orderBy('pf.id_profo', 'desc')->limit(25)->get();
        }

        return view('livewire.gestion-ventas.caja', [
            'pedidos'   => $pedidos,
            'proformas' => $proformas,
        ]);
    }
}
