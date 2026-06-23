<?php

namespace App\Livewire\GestionVentas;

use App\Models\Caja;
use App\Models\Cliente;
use App\Models\General;
use App\Models\Logs;
use App\Models\Productos;
use App\Models\Serie;
use App\Models\Venta_detalle;
use App\Models\VentaCuota;
use App\Models\Ventas;
use App\Models\Ventas_detalle_pago;
use App\Service\CalcularMontosVenta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class RealizarVenta extends Component
{
    use WithPagination;

    // ── Caja / Sucursal ──────────────────────────────────────────
    public bool   $validarCaja    = false;
    public int    $idCaja         = 0;
    public int    $idCajaNumero   = 0;
    public int    $idSucursal     = 0;
    public int    $idEmpresa      = 1;
    public string $nombreSucursal = '';
    public string $nombreCaja     = '';

    // ── Comprobante / Serie ──────────────────────────────────────
    public string $tipoComprobante = '03';
    public array  $series          = [];
    public        $idSerie         = null;
    public int    $correlativo     = 0;
    public float  $porcentajeIgv   = 18.0;
    public array  $tiposPago       = [];

    // ── Cliente ──────────────────────────────────────────────────
    public string $idTipoDocumento  = '2';
    public string $numDocumento     = '00000000';
    public string $nombreCliente    = 'CLIENTE GENERAL';
    public string $telefonoCliente  = '';
    public string $direccionCliente = '';
    public string $mensajeConsulta     = '';
    public string $tipoMensajeConsulta = '';

    // ── Modal clientes ───────────────────────────────────────────
    public bool   $mostrarModalClientes = false;
    public string $buscarCliente        = '';

    // ── Carrito ──────────────────────────────────────────────────
    public array  $items               = [];
    public string $buscarProducto      = '';
    public array  $resultadosProductos = [];

    // ── Proformas ────────────────────────────────────────────────
    public bool  $mostrarProformas   = false;
    public array $proformasAprobadas = [];
    public int   $proformasCount     = 0;
    public ?int  $idProfo            = null;

    // ── Pedidos ──────────────────────────────────────────────────
    public string $numeroPedido    = '';
    public ?int   $idPedidoCargado = null;

    // ── Pago / Crédito ───────────────────────────────────────────
    public int    $idFormasPago = 1;
    public ?int   $idTipoPago   = null;
    public string $pagoCliente  = '0';

    // ── Cuotas ───────────────────────────────────────────────────
    public int   $numeroCuotas = 0;
    public array $cuotas       = [];

    // =========================================================
    //  MOUNT
    // =========================================================
    public function mount(): void
    {
        abort_if(!auth()->user()->can('gestion_ventas.listar'), 403);

        $caja = (new Caja())->buscar_apertura_caja();
        if ($caja) {
            $this->validarCaja  = true;
            $this->idCaja       = (int) $caja->id_caja;
            $this->idCajaNumero = (int) $caja->id_caja_numero;

            $cn = DB::table('caja_numero')->where('id_caja_numero', $this->idCajaNumero)->first();
            $this->idSucursal = (int) ($cn->id_tienda ?? 0);
            $this->nombreCaja = $cn->caja_numero_nombre ?? '';

            if ($this->idSucursal) {
                $suc = DB::table('tiendas')->where('id_tienda', $this->idSucursal)->first();
                $this->idEmpresa      = (int) ($suc->id_empresa ?? 1);
                $this->nombreSucursal = $suc->tienda_nombre ?? '';
            }
        }

        $this->tiposPago = DB::table('tipo_pago')->where('tipo_pago_estado', 1)->get()->toArray();
        $this->cargarSeries();

        // Auto-cargar pedido si viene por query param (?pedido=PED-2026-0001)
        $numeroPedidoUrl = request()->get('pedido');
        if ($numeroPedidoUrl && $this->validarCaja && $this->idSucursal) {
            $this->numeroPedido = $numeroPedidoUrl;
            $this->cargarPedido();
        }

        if ($this->idSucursal) {
            $this->proformasCount = DB::table('proformas')
                ->where('id_sucursal', $this->idSucursal)
                ->where('profo_acti_estado', 1)
                ->where('profo_estado', 1)
                ->count();
        }
    }

    // =========================================================
    //  PROPIEDADES COMPUTADAS
    // =========================================================
    public function getTotalesProperty(): array
    {
        $calculoGeneral = (new CalcularMontosVenta())->calcularMontos($this->items, $this->porcentajeIgv, $this->idSucursal);
        return [
            'gravada'   => round($calculoGeneral['gravada'],    2),
            'igv'       => round($calculoGeneral['igv'],        2),
            'exonerada' => round($calculoGeneral['exonerada'],  2),
            'inafecta'  => round($calculoGeneral['inafectada'], 2),
            'gratuita'  => round($calculoGeneral['gratuito'],   2),
            'impuesto'  => round($calculoGeneral['impuesto'],   2),
            'total'     => round($calculoGeneral['total'],      2),
        ];
    }

    public function getVueltoProperty(): float
    {
        return round(max(0, (float) $this->pagoCliente - $this->totales['total']), 2);
    }

    public function getSumaCuotasProperty(): float
    {
        return round(array_sum(array_map(fn($c) => (float) ($c['monto'] ?? 0), $this->cuotas)), 2);
    }

    public function getSaldoCuotasProperty(): float
    {
        return round($this->totales['total'] - $this->sumaCuotas, 2);
    }

    // =========================================================
    //  CLIENTES — modal con paginación y búsqueda
    // =========================================================
    public function abrirModalClientes(): void
    {
        $this->mostrarModalClientes = true;
        $this->buscarCliente        = '';
        $this->resetPage('clientesPage');
    }

    public function cerrarModalClientes(): void
    {
        $this->mostrarModalClientes = false;
        $this->buscarCliente        = '';
        $this->resetPage('clientesPage');
    }

    public function updatedBuscarCliente(): void
    {
        $this->resetPage('clientesPage');
    }

    public function getClientesProperty()
    {
        $termino = trim($this->buscarCliente);

        return DB::table('clientes as c')
            ->join('tipo_documento as td', 'td.id_tipo_documento', '=', 'c.id_tipo_documento')
            ->where('c.id_empresa',    $this->idEmpresa)
            ->where('c.cliente_estado', 1)
            ->when(strlen($termino) >= 2, fn($q) =>
            $q->where(fn($q2) =>
            $q2->where('c.cliente_nombre',      'like', "%{$termino}%")
                ->orWhere('c.cliente_razonsocial','like', "%{$termino}%")
                ->orWhere('c.cliente_numero',    'like', "%{$termino}%")
            )
            )
            ->select(
                'c.id_clientes', 'c.id_tipo_documento',
                'c.cliente_nombre', 'c.cliente_razonsocial',
                'c.cliente_numero', 'c.cliente_direccion',
                'c.cliente_telefono'
            )
            ->orderBy('c.cliente_nombre')
            ->paginate(8, ['*'], 'clientesPage');
    }

    public function seleccionarCliente(int $idCliente): void
    {
        $cliente = DB::table('clientes as c')
            ->where('c.id_clientes', $idCliente)
            ->where('c.id_empresa',   $this->idEmpresa)
            ->where('c.cliente_estado', 1)
            ->first();

        if (!$cliente) return;

        $idTipoDoc = (int) $cliente->id_tipo_documento;

        // Autocompletar datos del cliente
        $this->idTipoDocumento  = (string) $idTipoDoc;
        $this->numDocumento     = (string) $cliente->cliente_numero;
        $this->nombreCliente    = $idTipoDoc === 4
            ? (string) ($cliente->cliente_razonsocial ?? $cliente->cliente_nombre ?? '')
            : (string) ($cliente->cliente_nombre ?? '');
        $this->direccionCliente = (string) ($cliente->cliente_direccion ?? '');
        $this->telefonoCliente  = (string) ($cliente->cliente_telefono  ?? '');
        $this->mensajeConsulta  = '';

        // Ajustar tipo de comprobante según tipo de documento
        $nuevoTipo = $idTipoDoc === 4 ? '01' : '03';
        if ($this->tipoComprobante !== $nuevoTipo) {
            $this->tipoComprobante = $nuevoTipo;
            $this->cargarSeries();
        }

        $this->cerrarModalClientes();
        session()->flash('success', 'Cliente cargado correctamente.');
    }

    // =========================================================
    //  SERIES
    // =========================================================
    public function updatedTipoComprobante(): void
    {
        if ($this->tipoComprobante === '01') {
            $this->idTipoDocumento = '4';
            $this->numDocumento    = '';
            $this->nombreCliente   = '';
        } else {
            if ($this->idTipoDocumento === '4') {
                $this->idTipoDocumento = '2';
            }
            $this->numDocumento  = '00000000';
            $this->nombreCliente = 'CLIENTE GENERAL';
        }
        $this->mensajeConsulta = '';
        $this->cargarSeries();
    }

    public function updatedIdTipoDocumento(): void
    {
        if ($this->idTipoDocumento === '4') {
            $this->tipoComprobante = '01';
            $this->cargarSeries();
        } elseif ($this->tipoComprobante === '01') {
            $this->tipoComprobante = '03';
            $this->cargarSeries();
        }
        $this->numDocumento    = '';
        $this->nombreCliente   = '';
        $this->mensajeConsulta = '';
    }

    public function updatedIdSerie(): void
    {
        $this->actualizarCorrelativo();
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

    // =========================================================
    //  BÚSQUEDA DE PRODUCTOS
    // =========================================================
    public function updatedBuscarProducto(): void
    {
        $this->resultadosProductos = $this->consultarProductos();
    }

    public function abrirBusqueda(): void
    {
        if (empty(trim($this->buscarProducto))) {
            $this->resultadosProductos = $this->consultarProductos();
        }
    }

    private function consultarProductos(): array
    {
        if (!$this->idSucursal) return [];

        $termino = trim($this->buscarProducto);

        return DB::table('producto_sucursal as ps')
            ->join('productos as p',             'p.id_pro',              '=', 'ps.id_pro')
            ->leftJoin('tipo_afectacion as ta',   'ta.id_tipo_afectacion', '=', 'ps.id_tipo_afectacion')
            ->leftJoin('medida as m',             'm.id_medida',           '=', 'p.id_medida')
            ->where('ps.id_tienda', $this->idSucursal)
            ->where('ps.ps_estado',   1)
            ->where('p.pro_estado',   1)
            ->when(strlen($termino) >= 2, fn($q) =>
            $q->where(fn($q2) =>
            $q2->where('p.pro_nombre', 'like', "%{$termino}%")
                ->orWhere('p.pro_codigo', 'like', "%{$termino}%")
            )
            )
            ->select(
                'p.id_pro', 'p.pro_nombre', 'p.pro_codigo',
                'ps.id_tipo_afectacion', 'p.impuesto_bolsa', 'p.id_medida',
                'ps.ps_precio_uni   as pro_precio_uni',
                'ps.ps_precio_uni_2 as pro_precio_uni_2',
                'ps.ps_precio_uni_3 as pro_precio_uni_3',
                'ps.ps_stock        as pro_stock',
                'ta.descripcion     as descripcion_afectacion',
                'm.medida_nombre'
            )
            ->orderBy('p.pro_nombre')
            ->limit(30)
            ->get()
            ->toArray();
    }

    public function limpiarBusqueda(): void
    {
        $this->buscarProducto      = '';
        $this->resultadosProductos = [];
    }

    // =========================================================
    //  CARRITO — agregar / editar / eliminar
    // =========================================================
    public function agregarProducto(int $idPro): void
    {
        if (collect($this->items)->contains('id_pro', $idPro)) {
            session()->flash('info', 'El producto ya está en el carrito.');
            $this->limpiarBusqueda();
            return;
        }

        $producto = DB::table('producto_sucursal as ps')
            ->join('productos as p',             'p.id_pro',              '=', 'ps.id_pro')
            ->leftJoin('tipo_afectacion as ta',   'ta.id_tipo_afectacion', '=', 'ps.id_tipo_afectacion')
            ->leftJoin('medida as m',             'm.id_medida',           '=', 'p.id_medida')
            ->where('ps.id_tienda', $this->idSucursal)
            ->where('ps.ps_estado',   1)
            ->where('p.id_pro',       $idPro)
            ->select(
                'p.id_pro', 'p.pro_nombre', 'p.pro_codigo',
                'ps.id_tipo_afectacion', 'p.impuesto_bolsa', 'p.id_medida',
                'ps.ps_precio_uni   as pro_precio_uni',
                'ps.ps_precio_uni_2 as pro_precio_uni_2',
                'ps.ps_precio_uni_3 as pro_precio_uni_3',
                'ps.ps_stock        as pro_stock',
                'ta.descripcion     as descripcion_afectacion',
                'm.medida_nombre'
            )
            ->first();

        if (!$producto) {
            session()->flash('error', 'Producto no encontrado.');
            $this->limpiarBusqueda();
            return;
        }

        $esBolsa = (int) $producto->impuesto_bolsa === 1;
        $esBien  = (int) $producto->id_medida === 58;

        if ($esBien && !$esBolsa && (float) $producto->pro_stock < 1) {
            session()->flash('error', 'Sin stock disponible para este producto.');
            $this->limpiarBusqueda();
            return;
        }

        $listaPrecios = array_values(array_filter(
            [(float)$producto->pro_precio_uni, (float)$producto->pro_precio_uni_2, (float)$producto->pro_precio_uni_3],
            fn($p) => $p > 0
        ));
        if (empty($listaPrecios)) $listaPrecios = [0.0];

        $this->items[] = [
            'id_pro'                 => (int)    $producto->id_pro,
            'pro_nombre'             => (string) $producto->pro_nombre,
            'pro_codigo'             => (string) ($producto->pro_codigo ?? ''),
            'id_tipo_afectacion'     => (int)    $producto->id_tipo_afectacion,
            'impuesto_bolsa'         => (int)    $producto->impuesto_bolsa,
            'id_medida'              => (int)    $producto->id_medida,
            'pro_stock'              => (float)  $producto->pro_stock,
            'descripcion_afectacion' => (string) $producto->descripcion_afectacion,
            'lista_precios'          => $listaPrecios,
            'precio_ref'             => (float)  $listaPrecios[0],
            'precio_venta'           => (float)  $listaPrecios[0],
            'cantidad'               => 1,
        ];

        $this->limpiarBusqueda();
        $this->recalcularCuotasAuto();
    }

    public function cambiarPrecioRef(int $index, float $valor): void
    {
        if (!isset($this->items[$index])) return;
        $this->items[$index]['precio_ref']   = $valor;
        $this->items[$index]['precio_venta'] = $valor;
        $this->recalcularCuotasAuto();
    }

    public function actualizarPrecio(int $index, $valor): void
    {
        if (!isset($this->items[$index])) return;
        $this->items[$index]['precio_venta'] = round((float) $valor, 2);
        $this->recalcularCuotasAuto();
    }

    /**
     * Cantidad decimal — igual que nota electrónica.
     * Si viene vacío o 0, se fija en 0 automáticamente.
     */
    public function actualizarCantidad(int $index, $valor): void
    {
        if (!isset($this->items[$index])) return;

        $cant = (float) $valor;

        // Si viene vacío o inválido → 0
        if ($cant < 0 || !is_numeric($valor) || trim((string)$valor) === '') {
            $this->items[$index]['cantidad'] = 0;
            $this->recalcularCuotasAuto();
            return;
        }

        $item    = $this->items[$index];
        $esBolsa = (int) ($item['impuesto_bolsa'] ?? 0) === 1;
        $esBien  = (int) ($item['id_medida']      ?? 0) === 58;

        if ($esBien && !$esBolsa && $cant > (float) ($item['pro_stock'] ?? 0)) {
            session()->flash('error', "Stock insuficiente. Disponible: {$item['pro_stock']}.");
            return;
        }

        $this->items[$index]['cantidad'] = $cant;
        $this->recalcularCuotasAuto();
    }

    public function quitarItem(int $index): void
    {
        if (!isset($this->items[$index])) return;
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->recalcularCuotasAuto();
    }

    // =========================================================
    //  CONSULTA DE DOCUMENTO
    // =========================================================
    public function consultarDocumento(): void
    {
        $this->mensajeConsulta     = '';
        $this->tipoMensajeConsulta = '';

        if (!in_array((int) $this->idTipoDocumento, [2, 4])) {
            $this->mensajeConsulta     = 'Solo aplica para DNI o RUC.';
            $this->tipoMensajeConsulta = 'warning';
            return;
        }

        $num = trim($this->numDocumento);
        if (empty($num)) {
            $this->mensajeConsulta     = 'Ingrese el número de documento.';
            $this->tipoMensajeConsulta = 'error';
            return;
        }

        $resp = (new General())->consultar_documento_migo((int) $this->idTipoDocumento, $num);

        if (empty($resp) || ($resp['success'] ?? false) !== true) {
            $this->mensajeConsulta     = 'No se encontró información del documento.';
            $this->tipoMensajeConsulta = 'error';
            return;
        }

        $data = $resp['data'] ?? [];
        $this->nombreCliente    = $data['nombre']    ?? '';
        $this->direccionCliente = $data['direccion'] ?? '';
        $this->mensajeConsulta     = 'Datos encontrados correctamente.';
        $this->tipoMensajeConsulta = 'success';
    }

    // =========================================================
    //  PROFORMAS
    // =========================================================
    public function toggleProformas(): void
    {
        $this->mostrarProformas = !$this->mostrarProformas;
        if ($this->mostrarProformas) {
            $this->cargarListaProformas();
        }
    }

    private function cargarListaProformas(): void
    {
        if (!$this->idSucursal) {
            $this->proformasAprobadas = [];
            $this->proformasCount     = 0;
            return;
        }

        $this->proformasAprobadas = DB::table('proformas as p')
            ->join('clientes as c', 'c.id_clientes', '=', 'p.id_clientes')
            ->where('p.id_sucursal',     $this->idSucursal)
            ->where('p.profo_acti_estado', 1)
            ->where('p.profo_estado',    1)
            ->select(
                'p.id_profo', 'p.profo_serie', 'p.profo_correlativo',
                'p.profo_fecha_emision', 'c.cliente_nombre', 'c.cliente_razonsocial',
                'c.id_tipo_documento as cli_tipo_doc',
                DB::raw('(SELECT SUM(pd.profo_deta_cantidad * pd.profo_deta_precio)
                          FROM proformas_detalles pd WHERE pd.id_profo = p.id_profo) as total')
            )
            ->orderByDesc('p.id_profo')
            ->limit(20)
            ->get()
            ->toArray();

        $this->proformasCount = count($this->proformasAprobadas);
    }

    public function seleccionarProforma(int $idProfo): void
    {
        $proforma = DB::table('proformas as p')
            ->join('clientes as c', 'c.id_clientes', '=', 'p.id_clientes')
            ->where('p.id_profo',         $idProfo)
            ->where('p.profo_acti_estado', 1)
            ->where('p.profo_estado',      1)
            ->select('p.*', 'c.*')
            ->first();

        if (!$proforma) {
            session()->flash('error', 'Proforma no encontrada o no disponible.');
            return;
        }

        $productoSinSucursal = DB::table('proformas_detalles as pd')
            ->join('productos as p', 'p.id_pro', '=', 'pd.id_pro')
            ->leftJoin('producto_sucursal as ps', function ($join) {
                $join->on('ps.id_pro', '=', 'pd.id_pro')
                    ->where('ps.id_tienda', $this->idSucursal);
            })
            ->where('pd.id_profo',        $idProfo)
            ->where('pd.profo_deta_estado', 1)
            ->whereNull('ps.id_ps')
            ->select('p.pro_nombre', 'p.pro_codigo', 'p.id_pro')
            ->first();

        if ($productoSinSucursal) {
            $nombre   = $productoSinSucursal->pro_nombre ?? "Producto #{$productoSinSucursal->id_pro}";
            $codigo   = $productoSinSucursal->pro_codigo ? " ({$productoSinSucursal->pro_codigo})" : '';
            $sucursal = $this->nombreSucursal ?: (string) $this->idSucursal;
            session()->flash('error', "El producto {$nombre}{$codigo} no tiene configuración para la sucursal {$sucursal}.");
            return;
        }

        $detalles = DB::table('proformas_detalles as pd')
            ->join('productos as p', 'p.id_pro', '=', 'pd.id_pro')
            ->join('producto_sucursal as ps', function ($join) {
                $join->on('ps.id_pro', '=', 'pd.id_pro')
                    ->where('ps.id_tienda', $this->idSucursal);
            })
            ->join('tipo_afectacion as ta', 'ta.id_tipo_afectacion', '=', 'ps.id_tipo_afectacion')
            ->join('medida as m',           'm.id_medida',           '=', 'p.id_medida')
            ->where('pd.id_profo',        $idProfo)
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

        $erroresStock = [];
        foreach ($detalles as $det) {
            if ((int) $det->id_medida === 58 && (int) $det->impuesto_bolsa == 0) {
                if ((float) $det->cantidad > (float) $det->pro_stock) {
                    $erroresStock[] = "{$det->pro_nombre}: solicitado {$det->cantidad}, disponible {$det->pro_stock}";
                }
            }
        }
        if (!empty($erroresStock)) {
            session()->flash('error', 'Stock insuficiente al cargar la proforma — ' . implode('; ', $erroresStock) . '.');
            return;
        }

        $idTipoDoc = (int) ($proforma->id_tipo_documento ?? 2);
        $this->idTipoDocumento  = (string) $idTipoDoc;
        $this->numDocumento     = $proforma->cliente_numero    ?? '';
        $this->nombreCliente    = $idTipoDoc === 2
            ? ($proforma->cliente_nombre      ?? '')
            : ($proforma->cliente_razonsocial ?? '');
        $this->direccionCliente = $proforma->cliente_direccion ?? '';
        $this->mensajeConsulta  = '';

        $nuevoTipo = $idTipoDoc === 4 ? '01' : '03';
        if ($this->tipoComprobante !== $nuevoTipo) {
            $this->tipoComprobante = $nuevoTipo;
            $this->cargarSeries();
        }

        $this->items = [];
        foreach ($detalles as $det) {
            $listaPrecios = array_values(array_filter(
                [(float)$det->pro_precio_uni, (float)$det->pro_precio_uni_2, (float)$det->pro_precio_uni_3],
                fn($p) => $p > 0
            ));
            if (empty($listaPrecios)) $listaPrecios = [(float) $det->precio_venta];

            $this->items[] = [
                'id_pro'                 => (int)    $det->id_pro,
                'pro_nombre'             => (string) $det->pro_nombre,
                'pro_codigo'             => (string) ($det->pro_codigo ?? ''),
                'id_tipo_afectacion'     => (int)    $det->id_tipo_afectacion,
                'impuesto_bolsa'         => (int)    $det->impuesto_bolsa,
                'id_medida'              => (int)    $det->id_medida,
                'pro_stock'              => (float)  $det->pro_stock,
                'descripcion_afectacion' => (string) $det->descripcion_afectacion,
                'lista_precios'          => $listaPrecios,
                'precio_ref'             => (float)  $det->precio_venta,
                'precio_venta'           => (float)  $det->precio_venta,
                'cantidad'               => (float)  $det->cantidad,
            ];
        }

        $this->idProfo          = $idProfo;
        $this->mostrarProformas = false;
        $this->recalcularCuotasAuto();

        $ref = $proforma->profo_serie
            ? "{$proforma->profo_serie}-" . str_pad($proforma->profo_correlativo ?? $idProfo, 5, '0', STR_PAD_LEFT)
            : "#{$idProfo}";

        session()->flash('success', "Proforma {$ref} cargada correctamente.");
    }

    public function limpiarProforma(): void
    {
        $this->items        = [];
        $this->idProfo      = null;
        $this->cuotas       = [];
        $this->numeroCuotas = 0;
    }

    // =========================================================
    //  PEDIDOS — cargar desde número
    // =========================================================
    public function cargarPedido(): void
    {
        $numero = trim($this->numeroPedido);

        if (empty($numero)) {
            session()->flash('error', 'Ingrese el número de pedido.');
            return;
        }

        $pedido = DB::table('pedidos')
            ->where('pedido_numero', $numero)
            ->where('pedido_estado', 0)
            ->first();

        if (!$pedido) {
            session()->flash('error', "No se encontró el pedido '{$numero}' o ya fue procesado.");
            $this->idPedidoCargado = null;
            return;
        }

        $detalles = DB::table('pedidos_detalle as pd')
            ->join('productos as p', 'p.id_pro', '=', 'pd.id_pro')
            ->join('producto_sucursal as ps', function ($join) {
                $join->on('ps.id_pro', '=', 'pd.id_pro')
                    ->where('ps.id_tienda', $this->idSucursal);
            })
            ->leftJoin('tipo_afectacion as ta', 'ta.id_tipo_afectacion', '=', 'ps.id_tipo_afectacion')
            ->leftJoin('medida as m', 'm.id_medida', '=', 'p.id_medida')
            ->where('pd.id_pedido', $pedido->id_pedido)
            ->where('pd.pedido_deta_estado', 1)
            ->select(
                'pd.id_pro', 'p.pro_nombre', 'p.pro_codigo',
                'ps.id_tipo_afectacion', 'p.impuesto_bolsa', 'p.id_medida',
                'ps.ps_precio_uni   as pro_precio_uni',
                'ps.ps_precio_uni_2 as pro_precio_uni_2',
                'ps.ps_precio_uni_3 as pro_precio_uni_3',
                'ps.ps_stock        as pro_stock',
                'ta.descripcion     as descripcion_afectacion',
                'pd.pedido_deta_cantidad as cantidad',
                'pd.pedido_deta_precio   as precio_venta'
            )
            ->get();

        if ($detalles->isEmpty()) {
            session()->flash('error', 'El pedido no tiene productos disponibles para esta sucursal.');
            return;
        }

        $erroresStock = [];
        foreach ($detalles as $det) {
            if ((int) $det->id_medida === 58 && (int) $det->impuesto_bolsa == 0) {
                if ((float) $det->cantidad > (float) $det->pro_stock) {
                    $erroresStock[] = "{$det->pro_nombre}: solicitado {$det->cantidad}, disponible {$det->pro_stock}";
                }
            }
        }
        if (!empty($erroresStock)) {
            session()->flash('error', 'Stock insuficiente al cargar el pedido — ' . implode('; ', $erroresStock) . '.');
            return;
        }

        $this->items = [];
        foreach ($detalles as $det) {
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
            ];
        }

        $this->idPedidoCargado = (int) $pedido->id_pedido;
        $this->recalcularCuotasAuto();

        session()->flash('success', "Pedido {$numero} cargado correctamente.");
    }

    // =========================================================
    //  PAGO
    // =========================================================
    public function updatedIdFormasPago(): void
    {
        $this->idFormasPago = (int) $this->idFormasPago;
        if ($this->idFormasPago === 1) {
            $this->cuotas       = [];
            $this->numeroCuotas = 0;
        }
    }

    public function actualizarPagoCliente(): void
    {
        $this->pagoCliente = preg_match('/^\d+(\.\d{0,2})?$/', trim($this->pagoCliente))
            ? trim($this->pagoCliente)
            : '0';
    }

    // =========================================================
    //  CUOTAS
    // =========================================================
    private function recalcularCuotasAuto(): void
    {
        if ($this->idFormasPago === 2 && $this->numeroCuotas > 0) {
            $this->generarCuotas();
        }
    }

    public function generarCuotas(): void
    {
        $n     = (int)   $this->numeroCuotas;
        $total = (float) $this->totales['total'];

        if ($n < 1 || $total <= 0) {
            $this->cuotas = [];
            return;
        }

        $montoBase = round($total / $n, 2);
        $resto     = round($total - ($montoBase * ($n - 1)), 2);

        $this->cuotas = [];
        for ($i = 0; $i < $n; $i++) {
            $this->cuotas[] = [
                'monto'      => $i === ($n - 1) ? $resto : $montoBase,
                'fecha_pago' => Carbon::today()->addDays(3 + ($i * 30))->format('Y-m-d'),
            ];
        }
    }

    public function eliminarCuota(int $index): void
    {
        if (!isset($this->cuotas[$index])) return;
        unset($this->cuotas[$index]);
        $this->cuotas       = array_values($this->cuotas);
        $this->numeroCuotas = count($this->cuotas);
    }

    // =========================================================
    //  GUARDAR VENTA
    // =========================================================
    public function guardar(): void
    {
        if (!auth()->user()->can('gestion_ventas.crear')) {
            session()->flash('error', 'No tienes permiso para registrar ventas.');
            $this->dispatch('ventaError');
            return;
        }

        try {
            $idFormasPago    = (int)   $this->idFormasPago;
            $idTipoPago      = $idFormasPago === 1 ? ($this->idTipoPago ?: null) : null;
            $pagoCliente     = (float) $this->pagoCliente;
            $cuotas          = $idFormasPago === 2 ? $this->cuotas : [];
            $items           = $this->items;
            $idProfo         = $this->idProfo;
            $idPedidoCargado = $this->idPedidoCargado;

            if (empty($items)) {
                session()->flash('error', 'Debe agregar al menos un producto a la venta.');
                $this->dispatch('ventaError');
                return;
            }

            if (!in_array($this->tipoComprobante, ['03', '01', '20'])) {
                session()->flash('error', 'Tipo de comprobante inválido.');
                $this->dispatch('ventaError');
                return;
            }

            if (empty(trim($this->nombreCliente))) {
                session()->flash('error', 'El nombre del cliente es obligatorio.');
                $this->dispatch('ventaError');
                return;
            }

            if ($idFormasPago === 1 && !$idTipoPago) {
                session()->flash('error', 'Seleccione el tipo de pago.');
                $this->dispatch('ventaError');
                return;
            }

            $idTipoDoc    = (int)   $this->idTipoDocumento;
            $numDocumento = trim($this->numDocumento);

            if ($idTipoDoc === 2 && strlen($numDocumento) !== 8) {
                session()->flash('error', 'El DNI debe tener exactamente 8 dígitos.');
                $this->dispatch('ventaError');
                return;
            }
            if ($idTipoDoc === 4 && strlen($numDocumento) !== 11) {
                session()->flash('error', 'El RUC debe tener exactamente 11 dígitos.');
                $this->dispatch('ventaError');
                return;
            }

            $aperturaCaja = (new Caja())->buscar_apertura_caja();
            if (!$aperturaCaja) {
                session()->flash('error', 'Para completar el pago, es necesario abrir una caja.');
                $this->dispatch('ventaError');
                return;
            }

            foreach ($items as $item) {
                $idPro  = (int)   ($item['id_pro']        ?? 0);
                $cant   = (float) ($item['cantidad']       ?? 0);
                $medida = (int)   ($item['id_medida']      ?? 0);
                $bolsa  = (float) ($item['impuesto_bolsa'] ?? 0);

                if ($medida === 58 && $bolsa == 0) {
                    $ps = DB::table('producto_sucursal')
                        ->where('id_pro',     $idPro)
                        ->where('id_tienda', $this->idSucursal)
                        ->first();
                    $stockDisp = (float) ($ps->ps_stock ?? 0);
                    if ($cant > $stockDisp) {
                        $nombre = $item['pro_nombre'] ?? "Producto #{$idPro}";
                        session()->flash('error', "Stock insuficiente para {$nombre}. Disponible: {$stockDisp}.");
                        $this->dispatch('ventaError');
                        return;
                    }
                }
            }

            $itemsParaCalculo = array_map(fn($i) => array_merge($i, [
                'nombre_producto' => $i['pro_nombre'],
                'medida'          => $i['id_medida'],
            ]), $items);

            $calculoGeneral = (new CalcularMontosVenta())->calcularMontos($itemsParaCalculo, $this->porcentajeIgv, $this->idSucursal);

            if ($idFormasPago === 1 && $pagoCliente < (float) $calculoGeneral['total']) {
                session()->flash('error', 'El monto ingresado no puede ser menor al total de la venta.');
                $this->dispatch('ventaError');
                return;
            }

            if ($idFormasPago === 2) {
                if (empty($cuotas)) {
                    session()->flash('error', 'Debe registrar al menos 1 cuota para venta a crédito.');
                    $this->dispatch('ventaError');
                    return;
                }
                $totalCuotas = array_sum(array_map(fn($c) => (float) ($c['monto'] ?? 0), $cuotas));
                if (abs(round($totalCuotas, 2) - round((float) $calculoGeneral['total'], 2)) > 0.01) {
                    session()->flash('error', 'La suma de las cuotas debe ser igual al total de la venta.');
                    $this->dispatch('ventaError');
                    return;
                }
                $minPermitida  = Carbon::today()->addDays(2);
                $fechaAnterior = null;
                foreach ($cuotas as $i => $cuota) {
                    $n = $i + 1;
                    try {
                        $fecha = Carbon::createFromFormat('Y-m-d', $cuota['fecha_pago'] ?? '')->startOfDay();
                    } catch (\Throwable) {
                        session()->flash('error', "Fecha de la cuota {$n} inválida.");
                        $this->dispatch('ventaError');
                        return;
                    }
                    if ($fecha->lessThanOrEqualTo($minPermitida)) {
                        session()->flash('error', "La fecha de la cuota {$n} debe ser posterior a {$minPermitida->toDateString()}.");
                        $this->dispatch('ventaError');
                        return;
                    }
                    if ($fechaAnterior && $fecha->lessThanOrEqualTo($fechaAnterior)) {
                        session()->flash('error', 'Las fechas de las cuotas deben ser estrictamente ascendentes.');
                        $this->dispatch('ventaError');
                        return;
                    }
                    $fechaAnterior = $fecha;
                }
            }

            if ($this->tipoComprobante !== '20') {
                $respDoc = (new General())->consultar_documento_migo($idTipoDoc, $numDocumento);
                if (empty($respDoc) || ($respDoc['success'] ?? false) !== true || empty($respDoc['data'])) {
                    session()->flash('error', 'No se encontró información para el número de documento ingresado.');
                    $this->dispatch('ventaError');
                    return;
                }
                if (($respDoc['data']['condicion_de_domicilio'] ?? '') !== 'HABIDO') {
                    session()->flash('error', 'El cliente no se encuentra en condición HABIDO según SUNAT.');
                    $this->dispatch('ventaError');
                    return;
                }
            }

            $informacionSerie = (new Serie())->sacar_serie($this->idSerie, $aperturaCaja->id_caja_numero);
            if (!$informacionSerie) {
                session()->flash('error', 'La serie seleccionada no está disponible para esta caja.');
                $this->dispatch('ventaError');
                return;
            }

            $nombreCliente    = trim($this->nombreCliente);
            $telefonoCliente  = trim($this->telefonoCliente);
            $direccionCliente = trim($this->direccionCliente);
            $idSucursal       = $this->idSucursal;
            $idEmpresa        = $this->idEmpresa;
            $tipoComprobante  = $this->tipoComprobante;
            $porcentajeIgv    = $this->porcentajeIgv;

            $idVentaGenerada = DB::transaction(function () use (
                $aperturaCaja, $informacionSerie, $tipoComprobante, $idFormasPago,
                $idTipoPago, $pagoCliente, $calculoGeneral, $items, $itemsParaCalculo,
                $cuotas, $porcentajeIgv, $idProfo, $idPedidoCargado, $idTipoDoc, $numDocumento,
                $nombreCliente, $telefonoCliente, $direccionCliente,
                $idSucursal, $idEmpresa
            ) {
                $cliente = Cliente::where([
                    ['cliente_numero', $numDocumento],
                    ['id_empresa',     $idEmpresa],
                    ['cliente_estado', 1],
                ])->first();

                if (!$cliente) {
                    $nuevoCliente                      = new Cliente();
                    $nuevoCliente->id_empresa          = $idEmpresa;
                    $nuevoCliente->id_tipo_documento   = $idTipoDoc;
                    $nuevoCliente->cliente_razonsocial = $nombreCliente;
                    $nuevoCliente->cliente_nombre      = $nombreCliente;
                    $nuevoCliente->cliente_numero      = $numDocumento;
                    $nuevoCliente->cliente_direccion   = $direccionCliente;
                    $nuevoCliente->cliente_telefono    = $telefonoCliente;
                    $nuevoCliente->cliente_fecha       = now()->format('Y-m-d H:i:s');
                    $nuevoCliente->cliente_estado      = 1;
                    $nuevoCliente->save();
                    $idCliente = $nuevoCliente->id_clientes;
                } else {
                    $idCliente = $cliente->id_clientes;
                }

                $docsGenericos = ['00000000','11111111','00000000000','11111111111'];
                if ((float) $calculoGeneral['total'] >= 700 && in_array($numDocumento, $docsGenericos, true)) {
                    throw new \Exception('Debe ingresar datos del cliente para ventas iguales o superiores a S/ 700.');
                }

                $venta                          = new Ventas();
                $venta->id_caja                 = $aperturaCaja->id_caja;
                $venta->id_caja_numero          = $aperturaCaja->id_caja_numero;
                $venta->id_empresa              = $idEmpresa;
                $venta->id_sucursal             = $idSucursal ?: null;
                $venta->id_users                = Auth::id();
                $venta->id_clientes             = $idCliente;
                $venta->id_moneda               = 1;
                $venta->venta_tipo_campo        = 0;
                $venta->venta_condicion_resumen = 1;
                $venta->venta_tipo_envio        = 0;
                $venta->venta_tipo              = $tipoComprobante;
                $venta->venta_serie             = $informacionSerie->serie;
                $venta->venta_correlativo       = $informacionSerie->correlativo + 1;
                $venta->venta_totalgratuita     = $calculoGeneral['gratuito'];
                $venta->venta_totalexonerada    = $calculoGeneral['exonerada'];
                $venta->venta_totalinafecta     = $calculoGeneral['inafectada'];
                $venta->venta_totalgravada      = $calculoGeneral['gravada'];
                $venta->venta_totaligv          = $calculoGeneral['igv'];
                $venta->venta_incluye_igv       = 1;
                $venta->venta_porcentaje_igv    = $porcentajeIgv;
                $venta->venta_totaldescuento    = 0;
                $venta->venta_icbper            = $calculoGeneral['impuesto'];
                $venta->venta_total             = $calculoGeneral['total'];
                $venta->venta_pago_cliente      = $idFormasPago === 1 ? $pagoCliente : $calculoGeneral['total'];
                $venta->venta_vuelto            = $idFormasPago === 1 ? max(0, $pagoCliente - $calculoGeneral['total']) : 0;
                $venta->venta_fecha             = now()->format('Y-m-d H:i:s');
                $venta->tipo_documento_modificar = '';
                $venta->serie_modificar          = null;
                $venta->correlativo_modificar    = '';
                $venta->venta_estado_sunat       = 0;
                $venta->id_formas_pago           = $idFormasPago;
                $venta->venta_estado_pago        = $idFormasPago !== 2 ? 2 : 0;
                $venta->venta_codigo             = microtime(true);
                $venta->id_profo                 = $idProfo;
                $venta->save();
                $idVenta = $venta->id_venta;

                Serie::where('id_serie', $informacionSerie->id_serie)
                    ->update(['correlativo' => $informacionSerie->correlativo + 1]);

                foreach ($items as $item) {
                    $idPro    = (int)   $item['id_pro'];
                    $precio   = (float) $item['precio_venta'];
                    $cantidad = (float) $item['cantidad'];

                    $infoP = Productos::select('ps.id_tipo_afectacion', 'productos.pro_nombre', 'productos.impuesto_bolsa')
                        ->join('producto_sucursal as ps', 'ps.id_pro', '=', 'productos.id_pro')
                        ->where([['productos.id_pro', $idPro], ['ps.id_tienda', $idSucursal]])
                        ->first();

                    if (!$infoP) {
                        throw new \Exception("El producto #{$idPro} no tiene configuración para la sucursal.");
                    }

                    $tipoAfec     = (int)   $infoP->id_tipo_afectacion;
                    $tasa         = $porcentajeIgv / 100;
                    $precioSinIGV = $infoP->impuesto_bolsa == 0 ? round($precio, 2) : 0;
                    $igvItem      = 0;
                    $porcIgv      = 0;

                    if ($tipoAfec === 1 && $infoP->impuesto_bolsa == 0) {
                        $igvItem = round($precioSinIGV * $tasa, 2);
                        $porcIgv = $porcentajeIgv;
                    }

                    $precioConIGV = $tipoAfec === 1 ? round($precioSinIGV + $igvItem, 2) : $precioSinIGV;
                    $icbper       = $infoP->impuesto_bolsa == 1 ? 0.50 * $cantidad : 0;

                    $det                               = new Venta_detalle();
                    $det->id_venta                     = $idVenta;
                    $det->id_pro                       = $idPro;
                    $det->venta_detalle_precio_ref      = $infoP->impuesto_bolsa == 1 ? 0 : (float) ($item['precio_ref'] ?? $precio);
                    $det->venta_detalle_valor_unitario  = $precioSinIGV;
                    $det->venta_detalle_precio_unitario = $precioConIGV;
                    $det->venta_detalle_nombre_producto = $item['pro_nombre'] ?? $infoP->pro_nombre;
                    $det->venta_detalle_cantidad        = $cantidad;
                    $det->venta_detalle_total_igv       = $igvItem * $cantidad;
                    $det->venta_detalle_porcentaje_igv  = $porcIgv;
                    $det->venta_detalle_total_icbper    = $icbper;
                    $det->venta_detalle_valor_total     = $precioSinIGV * $cantidad;
                    $det->venta_detalle_importe_total   = $precioConIGV * $cantidad;
                    $det->save();

                    $medidaItem = (int) ($item['id_medida'] ?? 0);
                    if ($medidaItem === 58 && $infoP->impuesto_bolsa == 0) {
                        DB::table('producto_sucursal')
                            ->where('id_pro',    $idPro)
                            ->where('id_tienda', $idSucursal)
                            ->decrement('ps_stock', $cantidad);
                    }
                }

                if ($idFormasPago === 1) {
                    $pago                           = new Ventas_detalle_pago();
                    $pago->id_venta                 = $idVenta;
                    $pago->id_tipo_pago             = $idTipoPago;
                    $pago->venta_detalle_pago_monto = $calculoGeneral['total'];
                    $pago->venta_detalle_pago_estado = 1;
                    $pago->save();
                }

                if ($idFormasPago === 2) {
                    foreach ($cuotas as $i => $cuo) {
                        $cuota                      = new VentaCuota();
                        $cuota->id_venta            = $idVenta;
                        $cuota->venta_cuota_numero  = $i + 1;
                        $cuota->venta_cuota_importe = $cuo['monto'];
                        $cuota->venta_cuota_fecha   = $cuo['fecha_pago'];
                        $cuota->venta_cuota_estado  = 1;
                        $cuota->venta_cuota_pago    = 0;
                        $cuota->save();
                    }

                    // Auto-crear CxP para empresa compradora vinculada
                    if (strlen($numDocumento) === 11) {
                        $empresaComprador = DB::table('empresa')
                            ->where('empresa_ruc', $numDocumento)
                            ->first();

                        if ($empresaComprador) {
                            $rucVendedor = DB::table('empresa')
                                ->where('id_empresa', $idEmpresa)
                                ->value('empresa_ruc');

                            $proveedor = DB::table('proveedores')
                                ->where('id_empresa', $empresaComprador->id_empresa)
                                ->where('proveedores_numero_documento', $rucVendedor)
                                ->first();

                            if ($proveedor) {
                                $tipoDocNombre = match ($tipoComprobante) {
                                    '01'    => 'Factura',
                                    '03'    => 'Boleta',
                                    default => 'Nota de Venta',
                                };
                                $numeroDoc = $venta->venta_serie . '-' . str_pad($venta->venta_correlativo, 8, '0', STR_PAD_LEFT);

                                foreach ($cuotas as $cuo) {
                                    DB::table('cuentas_pagar')->insert([
                                        'id_orden_compra'      => null,
                                        'id_proveedores'       => $proveedor->id_proveedores,
                                        'id_empresa'           => $empresaComprador->id_empresa,
                                        'id_sucursal'          => null,
                                        'id_users_registro'    => Auth::id(),
                                        'cp_numero_doc'        => $numeroDoc,
                                        'cp_tipo_doc'          => $tipoDocNombre,
                                        'cp_fecha_emision'     => now()->toDateString(),
                                        'cp_fecha_vencimiento' => $cuo['fecha_pago'],
                                        'cp_monto_total'       => (float) $cuo['monto'],
                                        'cp_monto_pagado'      => 0.00,
                                        'cp_saldo'             => (float) $cuo['monto'],
                                        'cp_estado'            => 1,
                                        'cp_observacion'       => 'Automático: venta ' . $numeroDoc,
                                        'created_at'           => now(),
                                        'updated_at'           => now(),
                                    ]);
                                }
                            }
                        }
                    }
                }

                if ($idProfo) {
                    DB::table('proformas')
                        ->where('id_profo',         $idProfo)
                        ->where('profo_acti_estado', 1)
                        ->update(['profo_acti_estado' => 2, 'updated_at' => now()]);
                }

                if ($idPedidoCargado) {
                    DB::table('pedidos')
                        ->where('id_pedido', $idPedidoCargado)
                        ->update(['pedido_estado' => 1, 'updated_at' => now()]);

                    DB::table('ventas')
                        ->where('id_venta', $idVenta)
                        ->update(['id_pedido' => $idPedidoCargado]);
                }

                return $idVenta;
            });

            $this->items           = [];
            $this->idProfo         = null;
            $this->cuotas          = [];
            $this->numeroCuotas    = 0;
            $this->pagoCliente     = 0;
            $this->numeroPedido    = '';
            $this->idPedidoCargado = null;

            $this->dispatch('ventaGuardada', ventaId: $idVentaGenerada);

        } catch (\Exception $e) {
            (new Logs())->insertarLog($e);
            session()->flash('error', $e->getMessage() ?: 'Ocurrió un error interno al registrar la venta.');
            $this->dispatch('ventaError');
        }
    }

    // =========================================================
    //  RENDER
    // =========================================================
    public function render(): \Illuminate\View\View
    {
        return view('livewire.gestion-ventas.realizar-venta', [
            'clientes' => $this->mostrarModalClientes ? $this->clientes : null,
        ]);
    }
}
