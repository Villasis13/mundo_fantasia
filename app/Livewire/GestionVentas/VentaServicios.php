<?php

namespace App\Livewire\GestionVentas;

use App\Models\Caja;
use App\Models\Cliente;
use App\Models\General;
use App\Models\Logs;
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

/**
 * Venta de Servicios — venta directa con líneas escritas a mano.
 * A diferencia de RealizarVenta, NO selecciona productos del catálogo
 * ni mueve stock: cada línea se escribe (descripción, cantidad, precio)
 * y elige su afectación de IGV (1=Gravado, 2=Exonerado, 3=Inafecto).
 */
class VentaServicios extends Component
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

    // ── Líneas manuales ──────────────────────────────────────────
    // Cada línea: ['descripcion' => '', 'cantidad' => 1, 'precio' => '', 'id_tipo_afectacion' => 1]
    public array $lineas = [];

    // ── Pago / Crédito ───────────────────────────────────────────
    public int    $idFormasPago = 1;
    public ?int   $idTipoPago   = null;
    public string $pagoCliente  = '0';

    // ── Cuotas ───────────────────────────────────────────────────
    public int   $numeroCuotas = 0;
    public array $cuotas       = [];

    private $logs;

    public function boot(): void
    {
        $this->logs = new Logs();
    }

    // =========================================================
    //  MOUNT
    // =========================================================
    public function mount(): void
    {
        abort_if(!auth()->user()->can('ventas_servicios.listar'), 403);

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
        $this->agregarLinea();
    }

    // =========================================================
    //  PROPIEDADES COMPUTADAS
    // =========================================================
    private function itemsParaCalculo(): array
    {
        return array_map(fn($l) => [
            'id_pro'             => null,
            'pro_nombre'         => (string) ($l['descripcion'] ?? ''),
            'nombre_producto'    => (string) ($l['descripcion'] ?? ''),
            'precio_venta'       => (float)  ($l['precio'] ?? 0),
            'cantidad'           => (float)  ($l['cantidad'] ?? 0),
            'id_tipo_afectacion' => (int)    ($l['id_tipo_afectacion'] ?? 1),
            'impuesto_bolsa'     => 0,
            'id_medida'          => 0,
        ], $this->lineas);
    }

    public function getTotalesProperty(): array
    {
        $calc = (new CalcularMontosVenta())->calcularMontos($this->itemsParaCalculo(), $this->porcentajeIgv, $this->idSucursal);
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
    //  LÍNEAS MANUALES
    // =========================================================
    public function agregarLinea(): void
    {
        $this->lineas[] = [
            'descripcion'        => '',
            'cantidad'           => 1,
            'precio'             => '',
            'id_tipo_afectacion' => 1,
        ];
    }

    public function quitarLinea(int $index): void
    {
        if (!isset($this->lineas[$index])) return;
        unset($this->lineas[$index]);
        $this->lineas = array_values($this->lineas);
        if (empty($this->lineas)) $this->agregarLinea();
        $this->recalcularCuotasAuto();
    }

    public function updated(string $name): void
    {
        // Normalizar cantidad/precio y recalcular cuotas en crédito
        if (preg_match('/^lineas\.(\d+)\.(cantidad|precio|id_tipo_afectacion)$/', $name, $m)) {
            $idx = (int) $m[1];
            if (isset($this->lineas[$idx])) {
                $cant = (float) ($this->lineas[$idx]['cantidad'] ?? 0);
                if ($cant < 0) $this->lineas[$idx]['cantidad'] = 0;
                $precio = (float) ($this->lineas[$idx]['precio'] ?? 0);
                if ($precio < 0) $this->lineas[$idx]['precio'] = 0;
            }
            $this->recalcularCuotasAuto();
        }
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

        $this->idTipoDocumento  = (string) $idTipoDoc;
        $this->numDocumento     = (string) $cliente->cliente_numero;
        $this->nombreCliente    = $idTipoDoc === 4
            ? (string) ($cliente->cliente_razonsocial ?? $cliente->cliente_nombre ?? '')
            : (string) ($cliente->cliente_nombre ?? '');
        $this->direccionCliente = (string) ($cliente->cliente_direccion ?? '');
        $this->telefonoCliente  = (string) ($cliente->cliente_telefono  ?? '');
        $this->mensajeConsulta  = '';

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
    //  PAGO / CUOTAS
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
    //  GUARDAR VENTA (servicios — sin stock)
    // =========================================================
    public function guardar(): void
    {
        if (!auth()->user()->can('ventas_servicios.crear')) {
            session()->flash('error', 'No tienes permiso para registrar ventas de servicios.');
            $this->dispatch('ventaError');
            return;
        }

        try {
            // Solo líneas con descripción y cantidad > 0
            $lineasValidas = array_values(array_filter($this->lineas, fn($l) =>
                trim((string) ($l['descripcion'] ?? '')) !== '' && (float) ($l['cantidad'] ?? 0) > 0
            ));

            if (empty($lineasValidas)) {
                session()->flash('error', 'Agregue al menos una línea con descripción y cantidad.');
                $this->dispatch('ventaError');
                return;
            }

            foreach ($lineasValidas as $i => $l) {
                if ((float) ($l['precio'] ?? 0) < 0) {
                    session()->flash('error', 'El precio no puede ser negativo (línea ' . ($i + 1) . ').');
                    $this->dispatch('ventaError');
                    return;
                }
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

            $idTipoDoc    = (int)  $this->idTipoDocumento;
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

            $idFormasPago = (int) $this->idFormasPago;
            $pagoCliente  = (float) $this->pagoCliente;

            if ($idFormasPago === 1 && !$this->idTipoPago) {
                session()->flash('error', 'Seleccione el tipo de pago.');
                $this->dispatch('ventaError');
                return;
            }

            $aperturaCaja = (new Caja())->buscar_apertura_caja();
            if (!$aperturaCaja) {
                session()->flash('error', 'Para completar el pago, es necesario abrir una caja.');
                $this->dispatch('ventaError');
                return;
            }

            // Items para cálculo
            $itemsCalc = array_map(fn($l) => [
                'id_pro'             => null,
                'precio_venta'       => (float) ($l['precio'] ?? 0),
                'cantidad'           => (float) ($l['cantidad'] ?? 0),
                'id_tipo_afectacion' => (int)   ($l['id_tipo_afectacion'] ?? 1),
                'impuesto_bolsa'     => 0,
                'nombre_producto'    => (string) ($l['descripcion'] ?? ''),
            ], $lineasValidas);

            $calc = (new CalcularMontosVenta())->calcularMontos($itemsCalc, $this->porcentajeIgv, $this->idSucursal);

            if ($idFormasPago === 1 && $pagoCliente < (float) $calc['total']) {
                session()->flash('error', 'El monto ingresado no puede ser menor al total de la venta.');
                $this->dispatch('ventaError');
                return;
            }

            $cuotas = $idFormasPago === 2 ? $this->cuotas : [];
            if ($idFormasPago === 2) {
                if (empty($cuotas)) {
                    session()->flash('error', 'Debe registrar al menos 1 cuota para venta a crédito.');
                    $this->dispatch('ventaError');
                    return;
                }
                $totalCuotas = array_sum(array_map(fn($c) => (float) ($c['monto'] ?? 0), $cuotas));
                if (abs(round($totalCuotas, 2) - round((float) $calc['total'], 2)) > 0.01) {
                    session()->flash('error', 'La suma de las cuotas debe ser igual al total de la venta.');
                    $this->dispatch('ventaError');
                    return;
                }
            }

            $idVentaGenerada = DB::transaction(function () use (
                $aperturaCaja, $lineasValidas, $calc, $idFormasPago, $pagoCliente,
                $cuotas, $idTipoDoc, $numDocumento
            ) {
                $informacionSerie = DB::table('serie')
                    ->where('id_serie', $this->idSerie)
                    ->lockForUpdate()
                    ->first();

                if (!$informacionSerie) {
                    throw new \Exception('La serie seleccionada no está disponible.');
                }

                $idCliente = $this->resolverCliente($idTipoDoc, $numDocumento);

                $venta                           = new Ventas();
                $venta->id_caja                  = $aperturaCaja->id_caja;
                $venta->id_caja_numero           = $aperturaCaja->id_caja_numero;
                $venta->id_empresa               = $this->idEmpresa;
                $venta->id_sucursal              = $this->idSucursal ?: null;
                $venta->id_users                 = Auth::id();
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
                $venta->venta_pago_cliente       = $idFormasPago === 1 ? $pagoCliente : $calc['total'];
                $venta->venta_vuelto             = $idFormasPago === 1 ? max(0, $pagoCliente - $calc['total']) : 0;
                $venta->venta_fecha              = now()->format('Y-m-d H:i:s');
                $venta->tipo_documento_modificar = '';
                $venta->serie_modificar          = null;
                $venta->correlativo_modificar    = '';
                $venta->venta_estado_sunat       = 0;
                $venta->id_formas_pago           = $idFormasPago;
                $venta->venta_estado_pago        = $idFormasPago !== 2 ? 2 : 0;
                $venta->venta_codigo             = microtime(true);
                $venta->save();
                $idVenta = $venta->id_venta;

                Serie::where('id_serie', $informacionSerie->id_serie)
                    ->update(['correlativo' => $informacionSerie->correlativo + 1]);

                $tasa = $this->porcentajeIgv / 100;
                foreach ($lineasValidas as $l) {
                    $precio   = (float) ($l['precio'] ?? 0);
                    $cantidad = (float) ($l['cantidad'] ?? 0);
                    $tipoAfec = (int)   ($l['id_tipo_afectacion'] ?? 1);

                    $precioSinIGV = round($precio, 2);
                    $igvItem      = $tipoAfec === 1 ? round($precioSinIGV * $tasa, 2) : 0;
                    $precioConIGV = $tipoAfec === 1 ? round($precioSinIGV + $igvItem, 2) : $precioSinIGV;

                    $det = new Venta_detalle();
                    $det->id_venta                     = $idVenta;
                    $det->id_pro                       = null;
                    $det->venta_detalle_precio_ref      = $precioSinIGV;
                    $det->venta_detalle_valor_unitario  = $precioSinIGV;
                    $det->venta_detalle_precio_unitario = $precioConIGV;
                    $det->venta_detalle_nombre_producto = (string) $l['descripcion'];
                    $det->venta_detalle_cantidad        = $cantidad;
                    $det->venta_detalle_total_igv       = $igvItem * $cantidad;
                    $det->venta_detalle_porcentaje_igv  = $tipoAfec === 1 ? $this->porcentajeIgv : 0;
                    $det->venta_detalle_total_icbper    = 0;
                    $det->venta_detalle_valor_total     = $precioSinIGV * $cantidad;
                    $det->venta_detalle_importe_total   = $precioConIGV * $cantidad;
                    $det->save();
                }

                if ($idFormasPago === 1) {
                    $pago                            = new Ventas_detalle_pago();
                    $pago->id_venta                  = $idVenta;
                    $pago->id_tipo_pago              = $this->idTipoPago;
                    $pago->venta_detalle_pago_monto  = $calc['total'];
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
                }

                return $idVenta;
            });

            // Reset
            $this->lineas       = [];
            $this->agregarLinea();
            $this->cuotas       = [];
            $this->numeroCuotas = 0;
            $this->pagoCliente  = '0';

            $this->dispatch('ventaGuardada', ventaId: $idVentaGenerada);

        } catch (\Exception $e) {
            $this->logs->insertarLog($e);
            session()->flash('error', $e->getMessage() ?: 'Ocurrió un error interno al registrar la venta.');
            $this->dispatch('ventaError');
        }
    }

    private function resolverCliente(int $idTipoDoc, string $numDoc): int
    {
        $nombreReal = trim($this->nombreCliente);

        if ($numDoc && !in_array($numDoc, ['00000000', '11111111', '00000000000', '11111111111'])) {
            $cliente = DB::table('clientes')
                ->where('cliente_numero', $numDoc)
                ->where('id_empresa', $this->idEmpresa)
                ->where('cliente_estado', 1)
                ->first();
            if ($cliente) return (int) $cliente->id_clientes;

            $nuevo                      = new Cliente();
            $nuevo->id_empresa          = $this->idEmpresa;
            $nuevo->id_tipo_documento   = $idTipoDoc;
            $nuevo->cliente_razonsocial = $nombreReal;
            $nuevo->cliente_nombre      = $nombreReal;
            $nuevo->cliente_numero      = $numDoc;
            $nuevo->cliente_direccion   = trim($this->direccionCliente);
            $nuevo->cliente_telefono    = trim($this->telefonoCliente);
            $nuevo->cliente_fecha       = now()->format('Y-m-d H:i:s');
            $nuevo->cliente_estado      = 1;
            $nuevo->save();
            return (int) $nuevo->id_clientes;
        }

        // Cliente genérico de la empresa
        $generico = DB::table('clientes')
            ->where('cliente_numero', '00000000')
            ->where('id_empresa', $this->idEmpresa)
            ->where(function ($q) {
                $q->where('cliente_nombre', 'CLIENTE GENERAL')
                  ->orWhere('cliente_razonsocial', 'CLIENTE GENERAL');
            })
            ->first();
        if ($generico) return (int) $generico->id_clientes;

        $micro = microtime(true);
        DB::table('clientes')->insert([
            'id_tipo_documento'   => 2,
            'cliente_razonsocial' => 'CLIENTE GENERAL',
            'cliente_nombre'      => 'CLIENTE GENERAL',
            'cliente_numero'      => '00000000',
            'cliente_fecha'       => now(),
            'cliente_estado'      => 1,
            'cliente_codigo'      => $micro,
            'id_empresa'          => $this->idEmpresa,
        ]);
        return (int) DB::table('clientes')->where('cliente_codigo', $micro)->value('id_clientes');
    }

    // =========================================================
    //  RENDER
    // =========================================================
    public function render(): \Illuminate\View\View
    {
        return view('livewire.gestion-ventas.venta-servicios', [
            'clientes' => $this->mostrarModalClientes ? $this->clientes : null,
        ]);
    }
}
