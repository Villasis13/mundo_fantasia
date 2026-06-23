<?php

namespace App\Livewire\Facturacion;

use App\Models\Cliente;
use App\Models\General;
use App\Models\Logs;
use App\Models\Productos;
use App\Models\Serie;
use App\Models\Tipo_ncredito;
use App\Models\Tipo_ndebito;
use App\Models\Venta_detalle;
use App\Models\Ventas;
use App\Service\CalcularMontosVenta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class NotaElectronica extends Component
{
    private $ventas;
    // ── Venta afectada ───────────────────────────────────────────
    public int    $idVenta          = 0;
    public string $ventaTipo        = '';   // '01' factura | '03' boleta
    public string $ventaSerie       = '';
    public int    $ventaCorrelativo = 0;
    public float  $porcentajeIgv    = 18.0;
    public int    $idEmpresa        = 1;
    public int    $idSucursal       = 0;
    public int    $idCajaNumero     = 0;
    public int    $ventaTipoEnvio   = 0;
    public int    $ventaEstadoPago  = 2;

    // ── Cliente ──────────────────────────────────────────────────
    public string $idTipoDocumento  = '2';
    public string $numDocumento     = '';
    public string $nombreCliente    = '';
    public string $direccionCliente = '';
    public string $mensajeConsulta     = '';
    public string $tipoMensajeConsulta = '';

    // ── Nota electrónica ─────────────────────────────────────────
    public string $tipoNota    = '';   // '07' crédito | '08' débito
    public array  $series      = [];
    public        $idSerie     = null;
    public int    $correlativo = 0;
    public string $motivoNota  = '';
    public array  $motivosNota = [];

    // ── Items ────────────────────────────────────────────────────
    public array  $items               = [];
    public string $buscarProducto      = '';
    public array  $resultadosProductos = [];

    public function __construct()
    {
        $this->ventas = new Ventas();
    }
    // =========================================================
    //  MOUNT — recibe id_venta desde ruta
    // =========================================================
    public function mount(int $idVenta): void
    {
        abort_if(!auth()->user()->can('generar_nota.listar'), 403);
        $venta = DB::table('ventas as v')
            ->join('clientes as c', 'c.id_clientes', '=', 'v.id_clientes')
            ->where('v.id_venta', $idVenta)
            ->select(
                'v.id_venta', 'v.venta_tipo', 'v.venta_serie', 'v.venta_correlativo',
                'v.venta_porcentaje_igv', 'v.id_empresa', 'v.id_sucursal',
                'v.id_caja_numero', 'v.venta_tipo_envio', 'v.venta_estado_pago',
                'c.id_tipo_documento', 'c.cliente_numero', 'c.cliente_nombre',
                'c.cliente_razonsocial', 'c.cliente_direccion'
            )
            ->first();

        if (!$venta) {
            session()->flash('error', 'Venta no encontrada.');
            return;
        }

        // Datos de la venta afectada — id_empresa e id_sucursal vienen de aquí
        $this->idVenta          = (int)    $venta->id_venta;
        $this->ventaTipo        = (string) $venta->venta_tipo;
        $this->ventaSerie       = (string) $venta->venta_serie;
        $this->ventaCorrelativo = (int)    $venta->venta_correlativo;
        $this->porcentajeIgv    = (float)  $venta->venta_porcentaje_igv;
        $this->idEmpresa        = (int)   ($venta->id_empresa     ?? 1);
        $this->idSucursal       = (int)   ($venta->id_sucursal    ?? 0);
        $this->idCajaNumero     = (int)   ($venta->id_caja_numero ?? 0);
        $this->ventaTipoEnvio   = (int)   ($venta->venta_tipo_envio  ?? 0);
        $this->ventaEstadoPago  = (int)   ($venta->venta_estado_pago ?? 2);

        // Cliente precargado
        // Tipo de documento fijado según el comprobante afectado:
        // Factura (01) → siempre RUC (4) | Boleta (03) → siempre DNI (2)
        $this->idTipoDocumento  = $venta->venta_tipo === '01' ? '4' : '2';
        $this->numDocumento     = (string) $venta->cliente_numero;
        $this->nombreCliente    = $venta->id_tipo_documento == 4
            ? (string) $venta->cliente_razonsocial
            : (string) $venta->cliente_nombre;
        $this->direccionCliente = (string) ($venta->cliente_direccion ?? '');

        $this->cargarItemsVenta();
    }

    // =========================================================
    //  ITEMS DE LA VENTA AFECTADA
    //  Join idéntico al de listar_venta_detalle_x_id_venta()
    // =========================================================
    private function cargarItemsVenta(): void
    {
        if (!$this->idVenta || !$this->idSucursal) return;

        $detalles = DB::table('ventas_detalle as vd')
            ->join('productos as p',           'p.id_pro',              '=', 'vd.id_pro')
            ->join('producto_sucursal as ps',   'ps.id_pro',             '=', 'p.id_pro')
            ->join('tipo_afectacion as ta',     'ta.id_tipo_afectacion', '=', 'ps.id_tipo_afectacion')
            ->join('medida as m',               'm.id_medida',           '=', 'p.id_medida')
            ->where('vd.id_venta',    $this->idVenta)
            ->where('ps.id_sucursal', $this->idSucursal)
            ->where('p.pro_estado',   1)
            ->select(
                'vd.id_pro',
                'vd.venta_detalle_nombre_producto as nombre_producto',
                'vd.venta_detalle_valor_unitario  as precio_venta',
                'vd.venta_detalle_cantidad        as cantidad',
                'ps.id_tipo_afectacion',
                'ps.ps_stock                      as pro_stock',
                'p.impuesto_bolsa',
                'p.id_medida',
                'ta.descripcion                   as descripcion_afectacion',
                'm.medida_nombre'
            )
            ->get();

        $this->items = [];
        foreach ($detalles as $d) {
            $this->items[] = [
                'id_pro'                 => (int)    $d->id_pro,
                'nombre_producto'        => (string) $d->nombre_producto,
                'id_tipo_afectacion'     => (int)    $d->id_tipo_afectacion,
                'descripcion_afectacion' => (string) $d->descripcion_afectacion,
                'impuesto_bolsa'         => (int)    $d->impuesto_bolsa,
                'id_medida'              => (int)    $d->id_medida,
                'medida'                 => (int)    $d->id_medida,
                'pro_stock'              => (float)  $d->pro_stock,
                'precio_venta'           => (float)  $d->precio_venta,
                'cantidad'               => (float)  $d->cantidad,
            ];
        }
    }

    // =========================================================
    //  TOTALES — mismo servicio que ventas
    // =========================================================
    public function getTotalesProperty(): array
    {
        $itemsCalculo = array_map(fn($i) => array_merge($i, [
            'nombre_producto' => $i['nombre_producto'],
            'medida'          => $i['id_medida'],
        ]), $this->items);

        $calc = (new CalcularMontosVenta())->calcularMontos(
            $itemsCalculo,
            $this->porcentajeIgv,
            $this->idSucursal
        );

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

    // =========================================================
    //  TIPO NOTA → series + motivos
    // =========================================================
    public function updatedTipoNota(): void
    {
        $this->idSerie     = null;
        $this->correlativo = 0;
        $this->motivoNota  = '';
        $this->motivosNota = [];
        $this->series      = [];
        $this->cargarSeries();
        $this->cargarMotivos();
    }

    public function updatedIdSerie(): void
    {
        $this->actualizarCorrelativo();
    }

    // ── Series dinámicas por índice fijo ─────────────────────
    // Orden en BD por id_serie (siempre igual):
    // [0] NC Factura 07+01 | [1] NC Boleta 07+03
    // [2] ND Factura 08+01 | [3] ND Boleta 08+03
    private function cargarSeries(): void
    {
        if (!$this->tipoNota || !$this->idEmpresa) {
            $this->series = [];
            return;
        }

        $todasSeries = Serie::where('id_empresa', $this->idEmpresa)
            ->whereIn('tipocomp', ['07', '08'])
            ->where('estado', 1)
            ->orderBy('id_serie')
            ->get();

        $indice = match (true) {
            $this->tipoNota === '07' && $this->ventaTipo === '01' => 0,
            $this->tipoNota === '07' && $this->ventaTipo === '03' => 1,
            $this->tipoNota === '08' && $this->ventaTipo === '01' => 2,
            $this->tipoNota === '08' && $this->ventaTipo === '03' => 3,
            default => null,
        };

        if ($indice !== null && $todasSeries->has($indice)) {
            $serie         = $todasSeries->values()->get($indice);
            $this->series  = [$serie->toArray()];
            $this->idSerie = $serie->id_serie;
            $this->actualizarCorrelativo();
        } else {
            $this->series  = [];
            $this->idSerie = null;
        }
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

    private function cargarMotivos(): void
    {
        if (!$this->tipoNota) {
            $this->motivosNota = [];
            return;
        }

        // tabla tipo_ncreditos → estado = 0 activo (igual que el controlador original)
        // tabla tipo_ndebitos  → estado = 0 activo
        if ($this->tipoNota === '07') {
            $this->motivosNota = Tipo_ncredito::listar_descripcion_segun_nota_credito()
                ->toArray();
        } else {
            $this->motivosNota = Tipo_ndebito::listar_descripcion_segun_nota_debito()
                ->toArray();
        }

        if (!empty($this->motivosNota)) {
            $this->motivoNota = $this->motivosNota[0]->codigo ?? '';
        }
    }

    // =========================================================
    //  PERMISOS DE EDICIÓN POR MOTIVO
    // =========================================================
    public function getPermisosProperty(): array
    {
        $editarPrecio    = true;
        $editarCantidad  = true;
        $eliminarItem    = true;
        $agregarProducto = true;

        if ($this->tipoNota === '07') {
            switch ($this->motivoNota) {
                case '01': case '02': case '06':
                // Bloqueo total — tampoco se pueden agregar productos
                $editarPrecio    = false;
                $editarCantidad  = false;
                $eliminarItem    = false;
                $agregarProducto = false;
                break;
                case '07':
                    $editarPrecio    = false;
                    $editarCantidad  = false;
                    $eliminarItem    = true;
                    $agregarProducto = true;
                    break;
                case '09':
                    $editarPrecio    = true;
                    $editarCantidad  = false;
                    $eliminarItem    = true;
                    $agregarProducto = true;
                    break;
            }
        } elseif ($this->tipoNota === '08') {
            switch ($this->motivoNota) {
                case '01': case '02': case '03':
                $editarPrecio    = true;
                $editarCantidad  = false;
                $eliminarItem    = true;
                $agregarProducto = true;
                break;
            }
        }

        return compact('editarPrecio', 'editarCantidad', 'eliminarItem', 'agregarProducto');
    }

    // =========================================================
    //  BÚSQUEDA DE PRODUCTOS (igual que ventas)
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
            ->join('productos as p',        'p.id_pro',              '=', 'ps.id_pro')
            ->join('tipo_afectacion as ta',  'ta.id_tipo_afectacion', '=', 'ps.id_tipo_afectacion')
            ->join('medida as m',            'm.id_medida',           '=', 'p.id_medida')
            ->where('ps.id_sucursal', $this->idSucursal)
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
                'ps.ps_precio_uni as pro_precio_uni',
                'ps.ps_stock      as pro_stock',
                'ta.descripcion   as descripcion_afectacion',
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
    //  ITEMS — agregar / editar precio / editar cantidad / eliminar
    // =========================================================
    public function agregarProducto(int $idPro): void
    {
        if (collect($this->items)->contains('id_pro', $idPro)) {
            session()->flash('info', 'El producto ya está en la lista.');
            $this->limpiarBusqueda();
            return;
        }

        $producto = DB::table('producto_sucursal as ps')
            ->join('productos as p',        'p.id_pro',              '=', 'ps.id_pro')
            ->join('tipo_afectacion as ta',  'ta.id_tipo_afectacion', '=', 'ps.id_tipo_afectacion')
            ->join('medida as m',            'm.id_medida',           '=', 'p.id_medida')
            ->where('ps.id_sucursal', $this->idSucursal)
            ->where('ps.ps_estado',   1)
            ->where('p.id_pro',       $idPro)
            ->select(
                'p.id_pro', 'p.pro_nombre',
                'ps.id_tipo_afectacion', 'p.impuesto_bolsa', 'p.id_medida',
                'ps.ps_precio_uni as pro_precio_uni',
                'ps.ps_stock      as pro_stock',
                'ta.descripcion   as descripcion_afectacion'
            )
            ->first();

        if (!$producto) {
            session()->flash('error', 'Producto no encontrado para esta sucursal.');
            $this->limpiarBusqueda();
            return;
        }

        $this->items[] = [
            'id_pro'                 => (int)    $producto->id_pro,
            'nombre_producto'        => (string) $producto->pro_nombre,
            'id_tipo_afectacion'     => (int)    $producto->id_tipo_afectacion,
            'descripcion_afectacion' => (string) $producto->descripcion_afectacion,
            'impuesto_bolsa'         => (int)    $producto->impuesto_bolsa,
            'id_medida'              => (int)    $producto->id_medida,
            'medida'                 => (int)    $producto->id_medida,
            'pro_stock'              => (float)  $producto->pro_stock,
            'precio_venta'           => (float)  $producto->pro_precio_uni,
            'cantidad'               => 1,
        ];

        $this->limpiarBusqueda();
    }

    public function actualizarPrecio(int $index, $valor): void
    {
        if (!isset($this->items[$index])) return;
        $this->items[$index]['precio_venta'] = round((float) $valor, 2);
    }

    public function actualizarCantidad(int $index, $valor): void
    {
        if (!isset($this->items[$index])) return;
        $this->items[$index]['cantidad'] = max(0.01, (float) $valor);
    }

    public function quitarItem(int $index): void
    {
        if (!isset($this->items[$index])) return;
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    // =========================================================
    //  CONSULTA DOCUMENTO — idéntico a ventas
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
        $this->nombreCliente       = $data['nombre']    ?? '';
        $this->direccionCliente    = $data['direccion'] ?? '';
        $this->mensajeConsulta     = 'Datos encontrados correctamente.';
        $this->tipoMensajeConsulta = 'success';
    }

    // =========================================================
    //  GUARDAR NOTA — portado completamente de generar_nota_re()
    // =========================================================
    public function guardar(): void
    {
        if (!auth()->user()->can('generar_nota.crear')) {
            session()->flash('error', 'No tienes permiso para generar notas electrónicas.');
            return;
        }

        try {
            // ── Validaciones ──────────────────────────────────────────
            if (empty($this->items)) {
                session()->flash('error', 'Debe tener al menos un producto en la nota.');
                $this->dispatch('notaError');
                return;
            }

            if (!in_array($this->tipoNota, ['07', '08'])) {
                session()->flash('error', 'Seleccione el tipo de nota (Crédito o Débito).');
                $this->dispatch('notaError');
                return;
            }

            if (empty($this->motivoNota)) {
                session()->flash('error', 'Seleccione el motivo de la nota.');
                $this->dispatch('notaError');
                return;
            }

            if (!$this->idSerie) {
                session()->flash('error', 'No hay serie disponible para este tipo de nota.');
                $this->dispatch('notaError');
                return;
            }

            if (empty(trim($this->nombreCliente))) {
                session()->flash('error', 'El nombre del cliente es obligatorio.');
                $this->dispatch('notaError');
                return;
            }

            $idTipoDoc    = (int)   $this->idTipoDocumento;
            $numDocumento = trim($this->numDocumento);

            if ($idTipoDoc === 2 && strlen($numDocumento) !== 8) {
                session()->flash('error', 'El DNI debe tener exactamente 8 dígitos.');
                $this->dispatch('notaError');
                return;
            }
            if ($idTipoDoc === 4 && strlen($numDocumento) !== 11) {
                session()->flash('error', 'El RUC debe tener exactamente 11 dígitos.');
                $this->dispatch('notaError');
                return;
            }
            if ($idTipoDoc === 4 && empty(trim($this->direccionCliente))) {
                session()->flash('error', 'La dirección es obligatoria para clientes con RUC.');
                $this->dispatch('notaError');
                return;
            }

            // ── Validar HABIDO en SUNAT ───────────────────────────────
            $respCliente = (new General())->consultar_documento_migo($idTipoDoc, $numDocumento);
            if (empty($respCliente) || ($respCliente['success'] ?? false) !== true || empty($respCliente['data'])) {
                session()->flash('error', 'No se encontró información para el número de documento ingresado.');
                $this->dispatch('notaError');
                return;
            }
            if (($respCliente['data']['condicion_de_domicilio'] ?? '') !== 'HABIDO') {
                session()->flash('error', 'El cliente no se encuentra en condición HABIDO según SUNAT.');
                $this->dispatch('notaError');
                return;
            }

            // ── Serie ─────────────────────────────────────────────────
            $informacionSerie = DB::table('serie')->where('id_serie', $this->idSerie)->first();
            if (!$informacionSerie) {
                session()->flash('error', 'La serie seleccionada no existe o no está disponible.');
                $this->dispatch('notaError');
                return;
            }

            // ── Calcular montos ───────────────────────────────────────
            $itemsCalculo = array_map(fn($i) => array_merge($i, [
                'nombre_producto' => $i['nombre_producto'],
                'medida'          => $i['id_medida'],
            ]), $this->items);

            $calc = (new CalcularMontosVenta())->calcularMontos(
                $itemsCalculo,
                $this->porcentajeIgv,
                $this->idSucursal
            );

            // ── Capturar escalares para el closure ────────────────────
            $idEmpresa        = $this->idEmpresa;
            $idSucursal       = $this->idSucursal;
            $idCajaNumero     = $this->idCajaNumero;
            $ventaTipoEnvio   = $this->ventaTipoEnvio;
            $ventaEstadoPago  = $this->ventaEstadoPago;
            $tipoNota         = $this->tipoNota;
            $motivoNota       = $this->motivoNota;
            $porcentajeIgv    = $this->porcentajeIgv;
            $idVenta          = $this->idVenta;
            $ventaTipo        = $this->ventaTipo;
            $ventaSerie       = $this->ventaSerie;
            $ventaCorrelativo = $this->ventaCorrelativo;
            $items            = $this->items;
            $nombreCliente    = trim($this->nombreCliente);
            $direccionCliente = trim($this->direccionCliente);

            $idNotaGenerada = DB::transaction(function () use (
                $informacionSerie, $calc, $items, $itemsCalculo,
                $tipoNota, $motivoNota, $porcentajeIgv,
                $idVenta, $ventaTipo, $ventaSerie, $ventaCorrelativo,
                $idTipoDoc, $numDocumento, $nombreCliente, $direccionCliente,
                $idEmpresa, $idSucursal, $idCajaNumero, $ventaTipoEnvio, $ventaEstadoPago
            ) {
                // ── Buscar o crear cliente ────────────────────────────
                $cliente = Cliente::where([
                    ['cliente_numero', $numDocumento],
                    ['id_empresa',     $idEmpresa],
                    ['cliente_estado', 1],
                ])->first();

                if (!$cliente) {
                    $nuevo                      = new Cliente();
                    $nuevo->id_empresa          = $idEmpresa;
                    $nuevo->id_tipo_documento   = $idTipoDoc;
                    $nuevo->cliente_razonsocial = $nombreCliente;
                    $nuevo->cliente_nombre      = $nombreCliente;
                    $nuevo->cliente_numero      = $numDocumento;
                    $nuevo->cliente_direccion   = $direccionCliente;
                    $nuevo->cliente_fecha       = now()->format('Y-m-d H:i:s');
                    $nuevo->cliente_estado      = 1;
                    $nuevo->save();
                    $idCliente = $nuevo->id_clientes;
                } else {
                    $idCliente = $cliente->id_clientes;
                }

                // ── Guardar nota en tabla ventas ──────────────────────
                $nota                           = new Ventas();
                $nota->id_caja_numero           = $idCajaNumero ?: null;
                $nota->id_empresa               = $idEmpresa;
                $nota->id_sucursal              = $idSucursal ?: null;
                $nota->id_users                 = Auth::id();
                $nota->id_clientes              = $idCliente;
                $nota->id_moneda                = 1;
                $nota->venta_tipo_campo         = 0;
                $nota->venta_condicion_resumen  = 1;
                $nota->venta_tipo_envio         = $ventaTipoEnvio;
                $nota->venta_tipo               = $tipoNota;
                $nota->venta_serie              = $informacionSerie->serie;
                $nota->venta_correlativo        = $informacionSerie->correlativo + 1;
                $nota->venta_totalgratuita      = $calc['gratuito'];
                $nota->venta_totalexonerada     = $calc['exonerada'];
                $nota->venta_totalinafecta      = $calc['inafectada'];
                $nota->venta_totalgravada       = $calc['gravada'];
                $nota->venta_totaligv           = $calc['igv'];
                $nota->venta_incluye_igv        = 1;
                $nota->venta_porcentaje_igv     = $porcentajeIgv;
                $nota->venta_totaldescuento     = 0;
                $nota->venta_icbper             = $calc['impuesto'];
                $nota->venta_total              = $calc['total'];
                $nota->venta_pago_cliente       = $calc['total'];
                $nota->venta_vuelto             = 0;
                $nota->venta_fecha              = now()->format('Y-m-d H:i:s');
                $nota->tipo_documento_modificar = $ventaTipo;
                $nota->serie_modificar          = $ventaSerie;
                $nota->correlativo_modificar    = $ventaCorrelativo;
                $nota->venta_codigo_motivo_nota = $motivoNota;
                $nota->venta_estado_sunat       = 0;
                $nota->id_formas_pago           = 1;
                $nota->venta_estado_pago        = $ventaEstadoPago;
                $nota->venta_codigo             = microtime(true);
                $nota->save();
                $idNota = $nota->id_venta;

                // ── Actualizar correlativo de serie ───────────────────
                Serie::where('id_serie', $informacionSerie->id_serie)
                    ->update(['correlativo' => $informacionSerie->correlativo + 1]);

                // ── Guardar detalles — misma lógica que ventas ────────
                foreach ($items as $item) {
                    $idPro    = (int)   $item['id_pro'];
                    $precio   = (float) $item['precio_venta'];
                    $cantidad = (float) $item['cantidad'];
                    $tipoAfec = (int)   $item['id_tipo_afectacion'];
                    $esBolsa  = (int)   $item['impuesto_bolsa'] === 1;

                    $precioSinIGV = $esBolsa ? 0 : round($precio, 2);
                    $precioConIGV = $precioSinIGV;
                    $igvItem      = 0;
                    $porcIgv      = 0;

                    if ($tipoAfec === 1 && !$esBolsa) {
                        $tasa         = $porcentajeIgv / 100;
                        $igvItem      = round($precioSinIGV * $tasa, 2);
                        $precioConIGV = round($precioSinIGV + $igvItem, 2);
                        $porcIgv      = $porcentajeIgv;
                    }

                    $icbper = $esBolsa ? 0.50 * $cantidad : 0;

                    $det                               = new Venta_detalle();
                    $det->id_venta                     = $idNota;
                    $det->id_pro                       = $idPro;
                    $det->venta_detalle_precio_ref      = $esBolsa ? 0 : $precio;
                    $det->venta_detalle_valor_unitario  = $precioSinIGV;
                    $det->venta_detalle_precio_unitario = $precioConIGV;
                    $det->venta_detalle_nombre_producto = $item['nombre_producto'];
                    $det->venta_detalle_cantidad        = $cantidad;
                    $det->venta_detalle_total_igv       = $igvItem * $cantidad;
                    $det->venta_detalle_porcentaje_igv  = $porcIgv;
                    $det->venta_detalle_total_icbper    = $icbper;
                    $det->venta_detalle_valor_total     = $precioSinIGV * $cantidad;
                    $det->venta_detalle_importe_total   = $precioConIGV * $cantidad;
                    $det->save();
                }

                // ── Lógica de stock post-guardado ─────────────────────
                // NC 01/02 → anular venta original + devolver stock
                if ($tipoNota === '07' && in_array($motivoNota, ['01', '02'])) {
                    $ventaOriginal = Ventas::where([['id_empresa',$idEmpresa],['id_sucursal',$idSucursal],['venta_correlativo', $ventaCorrelativo],['venta_serie', $ventaSerie]])->first();

                    if ($ventaOriginal) {
                        $ventaOriginal->anulado_sunat  = 1;
                        $ventaOriginal->venta_cancelar = 0;
                        $ventaOriginal->save();

                        $detalleOriginal = $this->ventas->listar_venta_detalle_x_id_venta($ventaOriginal->id_venta);


                        (new General())->actualizarStockPorDetalle($detalleOriginal, 'sumar',$ventaOriginal->id_sucursal);
                    }
                }

                // NC 06/07 → devolver stock de la nota generada
                if ($tipoNota === '07' && in_array($motivoNota, ['06', '07'])) {

                    $detalleNota = $this->ventas->listar_venta_detalle_x_id_venta($idNota);

                    (new General())->actualizarStockPorDetalle($detalleNota, 'sumar',$idSucursal);
                }

                return $idNota;
            });

            $this->dispatch('notaGuardada', notaId: $idNotaGenerada);

        } catch (\Exception $e) {
            (new Logs())->insertarLog($e);
            session()->flash('error', $e->getMessage() ?: 'Ocurrió un error interno al registrar la nota.');
            $this->dispatch('notaError');
        }
    }

    // =========================================================
    //  RENDER
    // =========================================================
    public function render(): \Illuminate\View\View
    {
        return view('livewire.facturacion.nota-electronica');
    }
}
